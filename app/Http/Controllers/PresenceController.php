<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Apprenant;
use App\Models\Employe;

class PresenceController extends Controller
{
    public function horaires(Request $request)
    {
        // Récupérer les apprenants et employés avec leurs relations
        $apprenants = Apprenant::with('cohorte')->get();
        $employes = Employe::with('departement')->get();

        // Retourner les données en JSON
        return response()->json([
            'apprenants' => $apprenants,
            'employes' => $employes,
        ]);
    }
}
