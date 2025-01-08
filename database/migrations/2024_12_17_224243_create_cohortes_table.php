<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        $table->string('annee'); // Année académique au format YYYY-YYYY
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
