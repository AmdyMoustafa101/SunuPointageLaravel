<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCohortesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cohortes', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description');
            $table->json('horaires'); // Stockage des horaires (JSON pour les jours de la semaine)
            $table->year('annee');    // Année de la cohorte
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cohortes');
    }
}
