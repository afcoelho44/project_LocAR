<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// filtro usado na distância da dica e as duas distâncias (bruta e com Kalman), para comparar
return new class extends Migration
{
    public function up()
    {
        Schema::table('dicas', function (Blueprint $table) {
            $table->string('filtro', 10)->default('bruto')->after('fonte'); // bruto | kalman
            $table->decimal('dist_bruto_m', 8, 2)->nullable()->after('dist_m');
            $table->decimal('dist_kalman_m', 8, 2)->nullable()->after('dist_bruto_m');
        });
    }

    public function down()
    {
        Schema::table('dicas', function (Blueprint $table) {
            $table->dropColumn(['filtro', 'dist_bruto_m', 'dist_kalman_m']);
        });
    }
};
