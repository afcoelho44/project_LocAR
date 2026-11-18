<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Answer;
use App\Models\Participation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcompanhamentoTest extends TestCase
{
    use RefreshDatabase;

    private function cenario(): array
    {
        $prof = User::create(['name' => 'Prof', 'username' => 'prof', 'email' => 'p@x.com', 'password' => bcrypt('x'), 'type' => 'teacher']);
        $aluno = User::create(['name' => 'Ana', 'username' => 'ana', 'email' => 'a@x.com', 'password' => bcrypt('x'), 'type' => 'student']);
        $act = Activity::create(['user_id' => $prof->id, 'name' => 'Trilha']);

        $qs = collect([1, 2])->map(function ($pos) use ($act) {
            $q = Question::create([
                'activity_id' => $act->id, 'description' => "Pergunta $pos", 'clue_text' => "Pista $pos",
                'lat' => -26.3, 'lng' => -48.8, 'position' => $pos, 'radius' => 20,
            ]);
            $certa = Answer::create(['question_id' => $q->id, 'alternative' => 'A', 'description' => 'certa', 'is_correct' => true]);
            $errada = Answer::create(['question_id' => $q->id, 'alternative' => 'B', 'description' => 'errada', 'is_correct' => false]);

            return compact('q', 'certa', 'errada');
        });

        return [$prof, $aluno, $act, $qs];
    }

    public function test_fluxo_do_aluno_registra_etapas_tentativas_e_relatorio(): void
    {
        $this->withoutVite();
        [$prof, $aluno, $act, $qs] = $this->cenario();

        $this->actingAs($aluno)->post(route('play.start', $act));
        $p = Participation::first();
        $this->assertCount(1, $p->steps);

        // chega ao ponto 1, erra uma vez e acerta
        $this->get(route('play.question', $p))->assertOk()->assertSee('Pergunta 1');
        $this->from(route('play.question', $p))
            ->post(route('play.answer', $p), ['answer_id' => $qs[0]['errada']->id])
            ->assertSessionHasErrors('answer_id');
        $this->post(route('play.answer', $p), ['answer_id' => $qs[0]['certa']->id])
            ->assertRedirect(route('play.question', $p));
        $this->get(route('play.question', $p))->assertSee('Pista 1');

        // etapa 2 de primeira
        $this->get(route('play.question', $p))->assertSee('Pergunta 2');
        $this->post(route('play.answer', $p), ['answer_id' => $qs[1]['certa']->id]);

        $p->refresh()->load('steps', 'attempts');
        $this->assertTrue($p->isFinished());
        $this->assertCount(2, $p->steps);
        $this->assertCount(3, $p->attempts);
        $this->assertSame(1, $p->steps->firstWhere('question_id', $qs[0]['q']->id)->wrong_attempts);
        $this->assertNotNull($p->steps->firstWhere('question_id', $qs[1]['q']->id)->completed_at);

        // aluno não acessa o acompanhamento
        $this->get(route('admin.reports.activity', $act))->assertForbidden();

        // professor
        $this->actingAs($prof);
        $this->get(route('admin.reports.activity', $act))->assertOk()->assertSee('Ana')->assertSee('Concluiu');
        $this->get(route('admin.reports.participation', $p))->assertOk()->assertSee('B) errada');
        $csv = $this->get(route('admin.reports.csv', $act))->assertOk()->streamedContent();
        $this->assertStringContainsString('Pergunta 1', $csv);
    }

    public function test_tela_inicial_e_cadastro_so_de_aluno(): void
    {
        $this->withoutVite();
        $this->get('/')->assertOk()->assertSee('Caça ao Tesouro em RA');

        $this->post('/register', [
            'name' => 'Beto', 'username' => 'beto', 'email' => 'b@x.com',
            'password' => 'senha12345', 'password_confirmation' => 'senha12345', 'type' => 'teacher',
        ]);
        $this->assertSame('student', User::where('username', 'beto')->value('type'));
    }
}
