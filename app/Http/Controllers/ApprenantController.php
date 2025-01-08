<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApprenantController extends Controller
{
    // Afficher la liste des apprenants
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1); // Page actuelle
        $limit = (int) $request->query('limit', 8); // Nombre d'éléments par page

        $apprenants = Apprenant::where('archivé', false)->get();
        
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

    // Renvoie le nombre total de cohortes et d'apprenants
public function getCounts()
{
    $nombreCohortes = Cohorte::count(); // Compte le nombre total de cohortes
    $nombreApprenants = Apprenant::count(); // Compte le nombre total d'apprenants

    return response()->json([
        'nombre_cohortes' => $nombreCohortes,
        'nombre_apprenants' => $nombreApprenants,
    ], 200);
}

// Récupérer les apprenants par cohorte
public function getApprenantsByCohorte($cohorteId)
{
    // Récupérer les apprenants associés à la cohorte
    $apprenants = Apprenant::where('cohorte_id', $cohorteId)->get();
    return response()->json($apprenants);
}



// Mettre à jour un apprenant spécifique
public function update(Request $request, $id)
{
    // Validation des données
    $request->validate([
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'adresse' => 'required|string',
        'telephone' => 'required|string|max:20',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'cohorte_id' => 'required|exists:cohortes,id',
    ]);

    // Trouver l'apprenant par ID
    $apprenant = Apprenant::findOrFail($id);

    // Enregistrement de la photo, si elle est fournie
    if ($request->hasFile('photo')) {
        // Supprimer l'ancienne photo si elle existe
        if ($apprenant->photo) {
            Storage::disk('public')->delete($apprenant->photo);
        }
        $photoPath = $request->file('photo')->store('photos', 'public');
    } else {
        $photoPath = $apprenant->photo; // Garder l'ancienne photo si aucune nouvelle n'est fournie
    }

    // Mettre à jour les informations de l'apprenant
    $apprenant->update([
        'nom' => $request->nom,
        'prenom' => $request->prenom,
        'adresse' => $request->adresse,
        'telephone' => $request->telephone,
        'photo' => $photoPath,
        'cohorte_id' => $request->cohorte_id,
        'archivé' => $apprenant->archivé, // Garder l'état archivé existant
    ]);

    return response()->json($apprenant, 200); // Retourner l'apprenant mis à jour
}

// Supprimer un apprenant spécifique
public function destroy($id)
{
    $apprenant = Apprenant::findOrFail($id);

    // Supprimer la photo si elle existe
    if ($apprenant->photo) {
        Storage::disk('public')->delete($apprenant->photo);
    }

    $apprenant->delete(); // Supprimer l'apprenant

    return response()->json(['message' => 'Apprenant supprimé avec succès.'], 200);
}

// Supprimer plusieurs apprenants
public function destroyMultiple(Request $request)
{
    $request->validate(['ids' => 'required|array']); // Validation des ID
    $apprenants = Apprenant::whereIn('id', $request->ids)->get();

    foreach ($apprenants as $apprenant) {
        // Supprimer la photo si elle existe
        if ($apprenant->photo) {
            Storage::disk('public')->delete($apprenant->photo);
        }
        $apprenant->delete(); // Supprimer l'apprenant
    }

    return response()->json(['message' => 'Apprenants supprimés avec succès.'], 200);
}

// Archiver un apprenant spécifique
public function archive($id) {
    $apprenant = Apprenant::find($id);
    if ($apprenant) {
        $apprenant->archivé = true;
        $apprenant->save();
        return response()->json(['message' => 'Apprenant archivé avec succès.'], 200);
    }
    return response()->json(['message' => 'Apprenant non trouvé.'], 404);
}

public function archiveMultiple(Request $request) {
    $ids = $request->input('ids');
    $apprenants = Apprenant::whereIn('id', $ids)->get();
    
    foreach ($apprenants as $apprenant) {
        $apprenant->archivé = true;
        $apprenant->save();
    }

    return response()->json(['message' => 'Apprenants archivés avec succès.'], 200);
}

}
