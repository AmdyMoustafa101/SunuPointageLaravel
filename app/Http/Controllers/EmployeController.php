<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Ajoutez cette ligne

class EmployeController extends Controller
{
    /**
     * Liste des employés.
     */
    public function index()
    {
        $employes = Employe::with('departement')->get();
        return response()->json($employes, 200);
    }

    /**
     * Création d'un employé.
     */
    public function store(Request $request)
    {
        // Récupérer le rôle
        $role = $request->input('role');

        // Validation conditionnelle
        $rules = [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'required|string',
            'telephone' => 'required|string|max:20',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'role' => 'required|in:simple,vigile,administrateur',
        ];

        if ($role === 'vigile') {
            $rules['fonction'] = 'nullable|string|in:vigile'; // Fonction fixée à "vigile"
            $rules['departement_id'] = 'nullable'; // Aucun département requis
        } else {
            $rules['fonction'] = 'required|string|max:255'; // Fonction requise pour les autres rôles
            $rules['departement_id'] = 'required|exists:departements,id';
        }

        if (in_array($role, ['administrateur', 'vigile'])) {
            $rules['email'] = 'required|email|unique:employes,email';
            $rules['password'] = 'required|string|min:8';
        }

        $validatedData = $request->validate($rules);

        // Gestion des champs spécifiques
        if ($role === 'vigile') {
            $validatedData['fonction'] = 'vigile'; // Attribuer "vigile" comme fonction
            $validatedData['departement_id'] = null; // Aucun département
        }

        $validatedData['cardID'] = null; // Initialement NULL

        // Upload de l'image
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employe_photos', 'public');
            $validatedData['photo'] = $path;
        }

        // Génération du matricule
        $validatedData['matricule'] = $this->generateMatricule($validatedData['role'], $validatedData['fonction']);


        // Création de l'employé
        $employe = Employe::create($validatedData);

        return response()->json($employe, 201);
    }

    private function generateMatricule($role, $fonction)
    {
        $prefixRole = substr($role, 0, 3);
        $prefixFonction = substr($fonction, 0, 3);
        $year = now()->year;
        $uniqueNumber = rand(1000, 9999);

        return strtoupper($prefixRole . $prefixFonction . $year . $uniqueNumber);
    }



    /**
     * Afficher un employé.
     */
    public function show($id)
    {
        $employe = Employe::with('departement')->findOrFail($id);
        return response()->json($employe, 200);
    }

    /**
     * Mise à jour d'un employé.
     */
    public function update(Request $request, $id)
    {
        $employe = Employe::findOrFail($id);

        $data = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'photo' => 'nullable|string',
            'adresse' => 'sometimes|string',
            'telephone' => 'sometimes|string|max:15|unique:employes,telephone,' . $id,
            'role' => 'sometimes|in:simple,vigile,administrateur',
            'fonction' => 'nullable|string|max:255',
            'departement_id' => 'nullable|exists:departements,id',
            'email' => 'nullable|email|unique:employes,email,' . $id,
            'password' => 'nullable|string|min:8',
            'archived' => 'boolean',
        ]);

        $employe->update($data);

        return response()->json($employe, 200);
    }

    /**
     * Suppression d'un employé.
     */
    public function destroy($id)
    {
        Employe::destroy($id);
        return response()->json(['message' => 'Employé supprimé avec succès'], 200);
    }

    public function login(Request $request)
{
    // Validation des informations envoyées
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    // Récupérer l'employé à partir de l'email
    $employe = Employe::where('email', $credentials['email'])->first();

    // Ajouter un journal pour vérifier si l'employé est trouvé
    Log::info('Employé trouvé: ', ['employe' => $employe]);

    // Si l'employé n'est pas trouvé ou si le mot de passe est incorrect
    if (!$employe) {
        Log::error('Email non trouvé: ' . $credentials['email']);
        return response()->json(['message' => 'Utilisateur introuvable'], 401);
    }

    if (!Hash::check($credentials['password'], $employe->password)) {
        Log::error('Mot de passe incorrect pour l\'employé: ' . $credentials['email']);
        return response()->json(['message' => 'Mot de passe incorrect'], 401);
    }

    // Vérification du rôle (seulement administrateur ou vigile)
    if (!in_array($employe->role, ['administrateur', 'vigile'])) {
        Log::error('Rôle non autorisé pour l\'employé: ' . $credentials['email']);
        return response()->json(['message' => 'Unauthorized role'], 403);
    }

    // Retourner une réponse avec les données de l'utilisateur
    return response()->json([
        'message' => 'Connexion réussie',
        'user' => [
            'nom' => $employe->nom,
            'prenom' => $employe->prenom,
            'email' => $employe->email,
            'role' => $employe->role,
            'fonction' => $employe->fonction,
            'matricule' => $employe->matricule,
            'adresse' => $employe->adresse,
            'departement' => $employe->departement ? $employe->departement->nom : null,
        ],
    ], 200);
}



}
