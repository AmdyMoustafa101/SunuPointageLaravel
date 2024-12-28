<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('photo')->nullable(); // URL ou chemin de la photo
            $table->string('adresse');
            $table->string('telephone');
            $table->string('cardID')->nullable()->unique(); // Attribué ultérieurement
            $table->enum('role', ['simple', 'vigile', 'administrateur']);
            $table->string('fonction')->nullable();
            $table->string('matricule')->unique();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->string('email')->nullable(); // Uniquement pour administrateur/vigile
            $table->string('password')->nullable(); // Uniquement pour administrateur/vigile
            $table->boolean('archived')->default(false);
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
        Schema::dropIfExists('employes');
    }
}
