<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use App\Models\Employe;
use App\Models\Apprenant;
use App\Models\Departement;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log; // Ajoutez cette ligne
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Ajoutez cette ligne
use Illuminate\Support\Facades\Http;   // Ajoutez cette ligne


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
    
        // Définir les règles de validation conditionnelle
        $rules = [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'required|string',
            'telephone' => 'required|string|max:20',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'role' => 'required|in:simple,vigile,administrateur',
        ];
    
        // Ajouter des règles spécifiques basées sur le rôle
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
    
        // Log les données reçues
        Log::info('Données reçues : ', $request->all());
    
        // Valider les données de la requête
        try {
            $validatedData = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log les erreurs de validation
            Log::error('Validation Error', $e->errors());
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        }
    
        // Gestion des champs spécifiques pour le rôle "vigile"
        if ($role === 'vigile') {
            $validatedData['fonction'] = 'vigile'; // Attribuer "vigile" comme fonction
            $validatedData['departement_id'] = null; // Aucun département
        }
    
        $validatedData['cardID'] = null; // Initialement NULL
    
        // Upload de l'image et stockage du chemin
        if ($request->hasFile('photo')) {
            try {
                $path = $request->file('photo')->store('employe_photos', 'public');
                $validatedData['photo'] = $path;
            } catch (\Exception $e) {
                return response()->json(['error' => 'File Upload Error', 'message' => $e->getMessage()], 500);
            }
        }
    
        // Génération du matricule
        $validatedData['matricule'] = $this->generateMatricule($validatedData['role'], $validatedData['fonction']);
    
        // Hachage du mot de passe si présent
        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }
    
        // Création de l'employé
        try {
            $employe = Employe::create($validatedData);
            return response()->json(['success' => true, 'message' => 'Employé créé avec succès', 'employe' => $employe], 201);
        } catch (\Exception $e) {
            // Gérer les erreurs de création
            return response()->json(['error' => 'Erreur lors de la création de l\'employé', 'message' => $e->getMessage()], 500);
        }
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
 * Changer le mot de passe de l'utilisateur connecté.
 */
public function changePassword(Request $request, $email)
{
    // Validation des champs
    $validated = $request->validate([
        'new_password' => 'required|string|min:8|confirmed',
    ]);

    // Récupérer l'utilisateur par son email depuis l'URL
    $employe = Employe::where('email', $email)->first();

    if (!$employe) {
        return response()->json(['message' => 'Utilisateur non trouvé'], 404);
    }

    // Vérifier si l'utilisateur a un rôle administrateur ou vigile
    if (!in_array($employe->role, ['administrateur', 'vigile'])) {
        return response()->json(['message' => 'Accès refusé. Vous n\'avez pas les droits nécessaires.'], 403);
    }

    // Mettre à jour le mot de passe (le mutateur le hash automatiquement)
    $employe->password = $validated['new_password'];
    $employe->save();

    return response()->json(['message' => 'Mot de passe changé avec succès'], 200);
}



/**
 * Récupérer le nombre total d'employés et de départements.
 */
public function getCounts()
{
    $nombreEmployes = Employe::count(); // Nombre total d'employés
    $nombreDepartements = Departement::count(); // Nombre total de départements

    return response()->json([
        'nombre_employes' => $nombreEmployes,
        'nombre_departements' => $nombreDepartements,
    ], 200);
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

    // Générer le token JWT
    $token = auth('api')->attempt($credentials);

    if (!$token) {
        Log::error('Impossible de générer un token pour l\'email: ' . $credentials['email']);
        return response()->json(['message' => 'Impossible de créer un token'], 500);
    }

    // Enregistrer les logs d'accès dans MongoDB
    $logData = [
        'utilisateur_id' => $employe->id, // L'ID de l'employé
        'card_id' => $employe->cardID,    // Le card_id (peut être NULL)
        'statut_acces' => 'login',         // Statut d'accès
        'nom' => $employe->nom,           // Nom de l'employé
        'prenom' => $employe->prenom,     // Prénom de l'employé
        'fonction' => $employe->fonction,     // Fonction de l'employé
    ];

    // Envoi d'un log à Node.js
    $this->sendLogToNode($logData);

    // Retourner une réponse avec les données de l'utilisateur
    // Retourner une réponse avec les données de l'utilisateur
    return response()->json([
        'message' => 'Connexion réussie',
        'user' => $employe,
        'token' => $token,
    ], 200);
}

 // Fonction de déconnexion
 public function logout(Request $request)
{
    try {
        // Récupérer l'utilisateur actuellement authentifié
        $employe = auth('api')->user();

        if (!$employe) {
            return response()->json(['message' => 'Aucun utilisateur authentifié'], 401);
        }

        // Enregistrer les logs de déconnexion dans MongoDB
        $logData = [
            'utilisateur_id' => $employe->id, // ID de l'employé
            'card_id' => $employe->cardID,    // Le card_id (peut être NULL)
            'statut_acces' => 'logout',       // Indique une déconnexion
            'nom' => $employe->nom,           // Nom de l'employé
            'prenom' => $employe->prenom,     // Prénom de l'employé
            'fonction' => $employe->fonction, // Fonction de l'employé
            'deconnexion_a' => now(),         // Heure de la déconnexion
        ];

        // Envoi d'un log à Node.js ou MongoDB
        $this->sendLogToNode($logData);

        // Invalider le token JWT
        auth('api')->logout();

        // Retourner une réponse de confirmation
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    } catch (\Exception $e) {
        // Gérer les exceptions
        Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());
        return response()->json([
            'message' => 'Erreur lors de la déconnexion',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function sendLogToNode(array $logData)
{
    $nodeUrl = env('NODE_API_URL', 'http://localhost:3005/api/log-access');

    try {
        $response = Http::post($nodeUrl, $logData);
        if ($response->failed()) {
            Log::error('Échec de l\'enregistrement du log dans Node.js', ['response' => $response->body()]);
        }
    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'envoi du log à Node.js', ['exception' => $e->getMessage()]);
    }
}

public function enregistrerPointage(Request $request)
{
    try {
        // Récupérer les informations du vigile authentifié
        $vigile = Auth::user();

        if (!$vigile) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Validation des données envoyées
        $data = $request->validate([
            'userID' => 'required|integer',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'matricule' => 'required|string',
            'telephone' => 'required|string',
            'role' => 'required|string',
            'date' => 'required|date',
            'heure_arrivee' => 'nullable|string',
            'heure_depart' => 'nullable|string',
        ]);
        // Formatage de la date (YYYY-MM-DD)
        $data['date'] = date('Y-m-d', strtotime($data['date']));

        // Ajouter les informations du vigile
        $data['vigile_nom'] = $vigile->nom;
        $data['vigile_matricule'] = $vigile->matricule;

        // Envoi des données au backend Node.js
        $response = Http::post('http://localhost:3005/api/pointages', $data);

        if ($response->successful()) {
            return response()->json(['message' => 'Pointage enregistré avec succès'], 201);
        } else {
            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement du pointage',
                'error' => $response->json(),
            ], $response->status());
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erreur serveur', 'error' => $e->getMessage()], 500);
    }
}

public function getStatistics()
{
    $totalEmployees = Employe::count(); // Nombre total d'employés
    $totalLearners = Apprenant::count(); // Ajouter le modèle Apprenant
    $totalDepartments = Departement::count(); // Nombre total de départements
    $totalCohorts = Cohorte::count(); // Ajouter le modèle Cohorte

    return response()->json([
        'totalEmployees' => $totalEmployees,
        'totalLearners' => $totalLearners,
        'totalDepartments' => $totalDepartments,
        'totalCohorts' => $totalCohorts,
    ], 200);
}

public function getEmployeesByDepartement($departementId)
    {
        $employes = Employe::where('departement_id', $departementId)->get();
        return response()->json($employes, 200);
    }

    public function archive($id)
    {
        $employe = Employe::findOrFail($id);
        $employe->archived = true;
        $employe->save();

        return response()->json(['message' => 'Employé archivé avec succès'], 200);
    }

    /**
     * Désarchiver un employé.
     */
    public function unarchive($id)
    {
        $employe = Employe::findOrFail($id);
        $employe->archived = false;
        $employe->save();

        return response()->json(['message' => 'Employé désarchivé avec succès'], 200);
    }

    /**
     * Archiver plusieurs employés.
     */
    public function archiveMultiple(Request $request)
    {
        $ids = $request->input('ids');
        Employe::whereIn('id', $ids)->update(['archived' => true]);

        return response()->json(['message' => 'Employés archivés avec succès'], 200);
    }

    /**
     * Désarchiver plusieurs employés.
     */
    public function unarchiveMultiple(Request $request)
    {
        $ids = $request->input('ids');
        Employe::whereIn('id', $ids)->update(['archived' => false]);

        return response()->json(['message' => 'Employés désarchivés avec succès'], 200);
    }

    /**
     * Bloquer un employé.
     */
    public function block($id)
    {
        $employe = Employe::findOrFail($id);
        $employe->blocked = true;
        $employe->save();

        return response()->json(['message' => 'Employé bloqué avec succès'], 200);
    }

    public function getEmployeById($id)
{
    // Récupérer l'employé avec ses détails et le département associé
    $employe = Employe::with('departement')->findOrFail($id);

    // Retourner les détails de l'employé sous forme de réponse JSON
    return response()->json([
        'id' => $employe->id,
        'nom' => $employe->nom,
        'prenom' => $employe->prenom,
        'adresse' => $employe->adresse,
        'telephone' => $employe->telephone,
        'email' => $employe->email,
        'role' => $employe->role,
        'fonction' => $employe->fonction,
        'departement' => $employe->departement, // Inclure les détails du département
        'photo' => $employe->photo,
        'matricule' => $employe->matricule,
        'created_at' => $employe->created_at,
        'updated_at' => $employe->updated_at,
    ], 200);
}



}
