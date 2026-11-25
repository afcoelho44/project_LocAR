<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Dica;
use App\Models\Participation;
use App\Models\Question;
use App\Models\User;
use App\Support\Proximidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DicaTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = -26.2535;
    private const LNG = -48.8555;

    private function cenario(): array
    {
        $prof = User::create(['name' => 'Prof', 'username' => 'prof', 'email' => 'p@x.com', 'password' => bcrypt('x'), 'type' => 'teacher']);
        $aluno = User::create(['name' => 'Ana', 'username' => 'ana', 'email' => 'a@x.com', 'password' => bcrypt('x'), 'type' => 'student']);
        $act = Activity::create(['user_id' => $prof->id, 'name' => 'Trilha']);
        $q = Question::create([
            'activity_id' => $act->id, 'description' => 'Pergunta 1', 'clue_text' => 'Pista',
            'lat' => self::LAT, 'lng' => self::LNG, 'position' => 1, 'radius' => 5,
        ]);
        $p = Participation::create([
            'user_id' => $aluno->id, 'activity_id' => $act->id,
            'current_question_id' => $q->id, 'started_at' => now(),
        ]);

        return [$aluno, $p, $q];
    }

    /** leituras a N metros ao sul do objeto */
    private function leituras(float $metros, int $n = 5): array
    {
        $lat = self::LAT - $metros / 111320;

        return array_map(fn ($i) => ['lat' => $lat, 'lng' => self::LNG, 'accuracy' => 4, 'speed' => 0, 't_ms' => $i * 1000], range(1, $n));
    }

    private function dica(User $aluno, Participation $p, array $leituras)
    {
        return $this->actingAs($aluno)->postJson(route('play.dica', $p), ['leituras' => $leituras]);
    }

    public function test_limites_da_regra(): void
    {
        $this->assertSame('quente', Proximidade::classe(10));
        $this->assertSame('morno', Proximidade::classe(10.01));
        $this->assertSame('morno', Proximidade::classe(15));
        $this->assertSame('frio', Proximidade::classe(15.01));
        $this->assertEqualsWithDelta(111.2, Proximidade::distancia(0, 0, 0.001, 0), 0.1);
    }

    public function test_sem_ia_usa_a_regra_e_registra(): void
    {
        config(['services.ia.url' => null]);
        [$aluno, $p] = $this->cenario();

        $this->dica($aluno, $p, $this->leituras(12))
            ->assertOk()
            ->assertJson(['classe' => 'morno', 'fonte' => 'regra'])
            ->assertJsonMissing(['braco' => 'regra']);

        $d = Dica::sole();
        $this->assertSame(['regra', 'regra', 'morno'], [$d->braco, $d->fonte, $d->classe]);
        $this->assertEqualsWithDelta(12, $d->dist_m, 0.1);
    }

    public function test_com_ia_usa_a_resposta_da_ia(): void
    {
        config(['services.ia.url' => 'http://ia:5000', 'services.ia.modo' => 'ia']);
        Http::fake(['ia:5000/classificar' => Http::response($this->respostaIa('frio', 'quente', 3.0))]);
        [$aluno, $p, $q] = $this->cenario();

        // pela regra seria quente (3 m); a IA diz frio
        $this->dica($aluno, $p, $this->leituras(3))->assertOk()->assertJson(['classe' => 'frio', 'fonte' => 'ia']);

        Http::assertSent(fn ($r) => $r['objeto']['lat'] == self::LAT && count($r['leituras']) === 5
            && $r['dispositivo'] === 'outro' && $r['sessao'] === "p{$p->id}-q{$q->id}" && $r['filtro'] === 'kalman');
        $d = Dica::sole();
        $this->assertSame(['ia', 'kalman'], [$d->fonte, $d->filtro]);
        $this->assertEqualsWithDelta(3.4, $d->dist_bruto_m, 0.01);
        $this->assertEqualsWithDelta(3.0, $d->dist_kalman_m, 0.01);
    }

    public function test_ia_fora_do_ar_cai_na_regra_e_pausa(): void
    {
        config(['services.ia.url' => 'http://ia:5000', 'services.ia.modo' => 'ia']);
        Http::fake(['ia:5000/*' => Http::response(['erro' => 'modelo não treinado'], 503)]);
        [$aluno, $p] = $this->cenario();

        $this->dica($aluno, $p, $this->leituras(20))->assertOk()->assertJson(['classe' => 'frio', 'fonte' => 'regra']);
        $this->assertTrue(Cache::has('ia_fora_do_ar'));

        // pausada: nem chama a IA
        $this->dica($aluno, $p, $this->leituras(20))->assertOk();
        Http::assertSentCount(1);

        $this->assertSame(['ia', 'ia'], Dica::pluck('braco')->all());
        $this->assertSame(['regra', 'regra'], Dica::pluck('fonte')->all());
    }

    public function test_modo_alternar_divide_as_etapas(): void
    {
        config(['services.ia.url' => 'http://ia:5000', 'services.ia.modo' => 'alternar']);
        Http::fake(['ia:5000/classificar' => Http::response($this->respostaIa('quente', 'quente', 1.0))]);
        [$aluno, $p, $q] = $this->cenario();

        $this->dica($aluno, $p, $this->leituras(1))->assertOk();
        $esperado = ($p->id + $q->position) % 2 === 0 ? 'ia' : 'regra';
        $this->assertSame($esperado, Dica::sole()->braco);

        // próxima etapa: o outro braço
        $q2 = Question::create([
            'activity_id' => $q->activity_id, 'description' => 'P2', 'clue_text' => 'x',
            'lat' => self::LAT, 'lng' => self::LNG, 'position' => 2, 'radius' => 5,
        ]);
        $p->update(['current_question_id' => $q2->id]);
        $this->dica($aluno, $p->fresh(), $this->leituras(1))->assertOk();
        $this->assertNotSame($esperado, Dica::latest('id')->first()->braco);
    }

    public function test_modo_regra_usa_a_distancia_filtrada_da_api(): void
    {
        // a API é chamada também na regra: guarda o histórico do Kalman e devolve classe_regra
        config(['services.ia.url' => 'http://ia:5000', 'services.ia.modo' => 'regra']);
        Http::fake(['ia:5000/classificar' => Http::response($this->respostaIa('frio', 'morno', 12.0))]);
        [$aluno, $p] = $this->cenario();

        // bruto daria quente (5 m); filtrado (12 m) dá morno; a classe da IA é ignorada
        $this->dica($aluno, $p, $this->leituras(5))->assertOk()->assertJson(['classe' => 'morno', 'fonte' => 'regra']);
        $d = Dica::sole();
        $this->assertSame(['regra', 'regra', 'kalman'], [$d->braco, $d->fonte, $d->filtro]);
        $this->assertEqualsWithDelta(12.0, $d->dist_m, 0.01);
    }

    public function test_sem_ia_registra_filtro_bruto(): void
    {
        config(['services.ia.url' => null]);
        [$aluno, $p] = $this->cenario();

        $this->dica($aluno, $p, $this->leituras(8))->assertOk();
        $d = Dica::sole();
        $this->assertSame('bruto', $d->filtro);
        $this->assertEqualsWithDelta(8, $d->dist_bruto_m, 0.1);
        $this->assertNull($d->dist_kalman_m);
    }

    private function respostaIa(string $classe, string $classeRegra, float $distKalman): array
    {
        return [
            'classe' => $classe, 'classe_regra' => $classeRegra, 'fonte' => 'ia', 'filtro' => 'kalman',
            'dist_m' => $distKalman, 'dist_bruto_m' => $distKalman + 0.4, 'dist_kalman_m' => $distKalman,
            'probabilidades' => ['quente' => 0.3, 'morno' => 0.3, 'frio' => 0.4], 'leituras_sessao' => 5,
        ];
    }

    public function test_validacao_e_dono(): void
    {
        config(['services.ia.url' => null]);
        [$aluno, $p] = $this->cenario();

        $this->dica($aluno, $p, [])->assertStatus(422);
        $this->dica($aluno, $p, [['lat' => 200, 'lng' => 0]])->assertStatus(422);
        $this->dica($aluno, $p, $this->leituras(5, 21))->assertStatus(422);

        $outro = User::create(['name' => 'Bia', 'username' => 'bia', 'email' => 'b@x.com', 'password' => bcrypt('x'), 'type' => 'student']);
        $this->dica($outro, $p, $this->leituras(5))->assertForbidden();
        $this->assertSame(0, Dica::count());
    }

    public function test_export(): void
    {
        config(['services.ia.url' => null]);
        [$aluno, $p] = $this->cenario();
        $this->dica($aluno, $p, $this->leituras(12));

        $this->artisan('dicas:export', ['--arquivo' => 'dicas_teste.csv'])->assertSuccessful();
        $csv = file(storage_path('app/dicas_teste.csv'));
        $this->assertCount(2, $csv);
        $this->assertStringContainsString('morno', $csv[1]);
        unlink(storage_path('app/dicas_teste.csv'));
    }
}
