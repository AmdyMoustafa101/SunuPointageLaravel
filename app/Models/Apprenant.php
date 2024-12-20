<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apprenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'prenom', 'adresse', 'telephone', 'photo', 'cardID', 'matricule', 'archivé', 'cohorte_id'
    ];

    // Lien avec la table Cohorte
    public function cohorte()
    {
        return $this->belongsTo(Cohorte::class);
    }

    // Générer automatiquement le matricule lors de la création
    protected static function booted()
    {
        static::creating(function ($apprenant) {
            $cohorte = $apprenant->cohorte()->first();
            $apprenant->matricule = strtoupper(substr($cohorte->nom, 0, 2)) . $cohorte->annee . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        });
    }
}
