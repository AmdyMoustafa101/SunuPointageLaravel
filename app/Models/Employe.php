<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;



class Employe extends Authenticatable implements JWTSubject
{


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

    protected $hidden = [
        'password', 'remember_token',
    ];

    // Relation avec le modèle Département
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    // Mutateur pour le hashage du mot de passe
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

     // Implémentation de JWTSubject
     public function getJWTIdentifier()
     {
         return $this->getKey();
     }

     public function getJWTCustomClaims()
     {
         return [];
     }

}
