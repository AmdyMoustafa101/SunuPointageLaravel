<?php

namespace App\Exports;

use App\Models\Employe;
use Maatwebsite\Excel\Concerns\FromCollection;

class EmployeExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Employe::with('departement')->get()->map(function ($employe) {
            return [
                'Nom' => $employe->nom,
                'Prénom' => $employe->prenom,
                'Adresse' => $employe->adresse,
                'Matricule' => $employe->matricule,
                'role'=> $employe->role,
                'Téléphone' => $employe->telephone,
                'Departement' => $employe->departement ? $employe->departement->nom : '',
            ];
        });
    }

    public function headings(): array
    {
        return ['Nom', 'Prénom', 'Adresse','Matricule','role', 'Téléphone', 'Departement'];
    }
}
