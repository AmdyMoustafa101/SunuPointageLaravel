<?php

namespace App\Exports;

use App\Models\Apprenant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApprenantsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Apprenant::with('cohorte')->get()->map(function ($apprenant) {
            return [
                'Nom' => $apprenant->nom,
                'Prénom' => $apprenant->prenom,
                'Adresse' => $apprenant->adresse,
                'Matricule' => $apprenant->matricule,
                'Téléphone' => $apprenant->telephone,
                'Cohorte' => $apprenant->cohorte ? $apprenant->cohorte->nom : '',
            ];
        });
    }

    public function headings(): array
    {
        return ['Nom', 'Prénom', 'Adresse', 'Téléphone', 'Cohorte'];
    }
}
