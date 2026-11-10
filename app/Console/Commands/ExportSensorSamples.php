<?php

namespace App\Console\Commands;

use App\Models\SensorSample;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportSensorSamples extends Command
{
    protected $signature = 'samples:export
                            {--arquivo=samples.csv : nome do arquivo dentro de storage/app}
                            {--participation= : exporta só uma participação}';

    protected $description = 'Exporta as amostras de sensores (dataset quente/frio) para CSV';

    public function handle()
    {
        $caminho = storage_path('app/' . $this->option('arquivo'));
        $colunas = ['id', 'participation_id', ...SensorSample::CAMPOS];

        $query = DB::table('sensor_samples')
            ->select($colunas)
            ->orderBy('sessao')
            ->orderBy('t_ms')
            ->orderBy('id');

        if ($p = $this->option('participation')) {
            $query->where('participation_id', $p);
        }

        $fp = fopen($caminho, 'w');
        fputcsv($fp, $colunas);

        $total = 0;
        foreach ($query->cursor() as $linha) { // cursor: lê uma linha por vez, não estoura memória
            fputcsv($fp, array_map(fn ($c) => $linha->$c, $colunas));
            $total++;
        }

        fclose($fp);

        $this->info("$total linhas exportadas para $caminho");

        return Command::SUCCESS;
    }
}
