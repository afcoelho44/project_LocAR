<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sensor_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sessao', 64);
            $table->unsignedBigInteger('t_ms');

            // alvo (posição da pergunta no momento da coleta)
            $table->double('alvo_lat', 10, 7);
            $table->double('alvo_lng', 10, 7);

            // GPS bruto
            $table->double('lat', 10, 7);
            $table->double('lng', 10, 7);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->float('gps_heading')->nullable();
            $table->unsignedBigInteger('gps_t_ms')->nullable();

            // bússola (média/desvio circular da janela)
            $table->float('bussola_media')->nullable();
            $table->float('bussola_desvio')->nullable();
            $table->unsignedSmallInteger('bussola_n')->default(0);

            // orientação relativa (último valor)
            $table->float('alpha_rel')->nullable();
            $table->float('beta')->nullable();
            $table->float('gamma')->nullable();

            // giroscópio
            $table->float('giro_alpha_soma')->nullable();
            $table->float('giro_beta_media')->nullable();
            $table->float('giro_gamma_media')->nullable();

            // acelerômetro
            $table->float('acc_mag_media')->nullable();
            $table->float('acc_mag_desvio')->nullable();

            $table->timestamps();

            $table->index(['sessao', 't_ms']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sensor_samples');
    }
};
