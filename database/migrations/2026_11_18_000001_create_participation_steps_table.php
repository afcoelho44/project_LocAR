<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// uma linha por etapa (pergunta) de cada participação:
// started_at  -> etapa liberada (início ou acerto da anterior)
// arrived_at  -> aluno chegou ao ponto e abriu a pergunta
// completed_at-> acertou a resposta
return new class extends Migration
{
    public function up()
    {
        Schema::create('participation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('wrong_attempts')->default(0);
            $table->timestamps();

            $table->unique(['participation_id', 'question_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('participation_steps');
    }
};
