<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\User as Authenticatable;



class Employe extends Authenticatable
{
    use HasFactory;

    protected $table = 'employes';

    protected $fillable = [
        'nom',
        'prenom',
        'photo',
        'adresse',
        'telephone',
        'cardID',
        'role',
        'fonction',
        'matricule',
        'departement_id',
        'email',
        'password',
        'archived',
    ];

    // Relation avec le modèle Département
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    // Mutateur pour hashage du mot de passe
    protected function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
}
