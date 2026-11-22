<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// cada dica de proximidade mostrada ao aluno (quente/morno/frio)
return new class extends Migration
{
    public function up()
    {
        Schema::create('dicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('braco', 10);    // quem deveria classificar nesta etapa: ia | regra
            $table->string('fonte', 10);    // quem classificou de fato (a IA pode ter caído na regra)
            $table->string('classe', 10);   // quente | morno | frio
            $table->decimal('dist_m', 8, 2);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['participation_id', 'question_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dicas');
    }
};
