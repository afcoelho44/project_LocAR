<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportDicas extends Command
{
    protected $signature = 'dicas:export
                            {--arquivo=dicas.csv : nome do arquivo dentro de storage/app}
                            {--participation= : exporta só uma participação}';

    protected $description = 'Exporta as dicas de proximidade (IA x regra) para CSV';

    public function handle()
    {
        $caminho = storage_path('app/' . $this->option('arquivo'));
        $colunas = ['id', 'participation_id', 'user_id', 'question_id', 'position', 'braco', 'fonte',
                    'filtro', 'classe', 'dist_m', 'dist_bruto_m', 'dist_kalman_m', 'accuracy', 'created_at'];

        $query = DB::table('dicas')
            ->join('participations', 'participations.id', '=', 'dicas.participation_id')
            ->join('questions', 'questions.id', '=', 'dicas.question_id')
            ->select('dicas.id', 'dicas.participation_id', 'participations.user_id', 'dicas.question_id',
                     'questions.position', 'dicas.braco', 'dicas.fonte', 'dicas.filtro', 'dicas.classe',
                     'dicas.dist_m', 'dicas.dist_bruto_m', 'dicas.dist_kalman_m', 'dicas.accuracy',
                     'dicas.created_at')
            ->orderBy('dicas.participation_id')
            ->orderBy('dicas.id');

        if ($p = $this->option('participation')) {
            $query->where('dicas.participation_id', $p);
        }

        $fp = fopen($caminho, 'w');
        fputcsv($fp, $colunas);

        $total = 0;
        foreach ($query->cursor() as $linha) {
            fputcsv($fp, array_map(fn ($c) => $linha->$c, $colunas));
            $total++;
        }

        fclose($fp);
        $this->info("$total dicas exportadas para $caminho");

        return Command::SUCCESS;
    }
}
