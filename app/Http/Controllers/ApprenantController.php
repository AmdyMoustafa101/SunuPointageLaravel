<?php

namespace App\Http\Controllers;

use App\Models\Apprenant;
use App\Models\Cohorte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApprenantController extends Controller
{
    // Afficher la liste des apprenants
    public function index()
    {
        $apprenants = Apprenant::all();
        return response()->json($apprenants, 200);
    }

    // Créer un nouvel apprenant
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'required|string',
            'telephone' => 'required|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cohorte_id' => 'required|exists:cohortes,id',
        ]);

        // Enregistrement de la photo
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        } else {
            $photoPath = null;
        }

        // Création de l'apprenant
        $apprenant = Apprenant::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'photo' => $photoPath,
            'cohorte_id' => $request->cohorte_id,
            'archivé' => false,  // Défini par défaut
        ]);

        return response()->json($apprenant, 201);
    }

    // Afficher un apprenant spécifique
    public function show($id)
    {
        $apprenant = Apprenant::findOrFail($id);
        return response()->json($apprenant, 200);
    }
}
