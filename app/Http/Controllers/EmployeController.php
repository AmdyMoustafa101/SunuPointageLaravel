<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\Departement;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\EmployeExport;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Ajoutez cette ligne
use Illuminate\Support\Facades\Http;


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

    public function indexB(Request $request)
    {
        // Récupération des paramètres de filtre
        $query = Employe::with('departement'); // Eager loading de la cohorte

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('adresse', 'like', "%$search%");
            });
        }

        if ($request->has('departement_id')) {
            $query->where('departement_id', $request->departement_id);
        }

        // Pagination de la liste avec un maximum de 10 résultats
        $employes = $query->paginate(10);

        return response()->json($employes);
    }

    public function getDepartements()
    {
        $departements = Departement::all();
        return response()->json($departements);
    }

    public function getEmployes(Request $request)
{
    $search = $request->query('search', null);
    $departementId = $request->query('departement_id', null);

    $query = Employe::query();

    // Appliquer la recherche si le paramètre 'search' est présent
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%$search%")
              ->orWhere('prenom', 'LIKE', "%$search%")
              ->orWhere('adresse', 'LIKE', "%$search%");
        });
    }

    // Filtrer par département si un ID est fourni
    if (!empty($departementId)) {
        $query->where('departement_id', $departementId);
    }

    // Charger les relations pour inclure le département
    $employes = $query->with('departement')->paginate(10);

    // Remplacer les départements NULL par 'Département Sécurité'
    foreach ($employes as $employe) {
        if (is_null($employe->departement)) {
            $employe->departement = (object) ['nom' => 'Département Sécurité'];
        }
    }

    return response()->json($employes);
}




     // Exporter les apprenants en CSV
     public function exportCSV()
{
    $employes = Employe::with('departement')->get();
    $csvData = [];

    foreach ($employes as $employe) {
        $csvData[] = [
            'Nom' => $employe->nom,
            'Prénom' => $employe->prenom,
            'Adresse' => $employe->adresse,
            'Matricule' => $employe->matricule,
            'role'=> $employe->role,
            'Téléphone' => $employe->telephone,
            'Departement' => $employe->departement ? $employe->departement->nom : '',
        ];
    }

    $csvFileName = 'employés.csv';
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$csvFileName\"",
    ];

    return response()->stream(function () use ($csvData) {
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($csvData[0])); // Ajouter les en-têtes du CSV

        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
    }, 200, $headers);
}


     // Exporter les apprenants en Excel
     public function exportExcel()
     {
         return Excel::download(new EmployeExport, 'apprenants.xlsx');
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

    public function getPhotoAttribute($value)
{
    if ($value) {
        return asset('storage/' . $value);
    }
    return null;
}

public function updatePhoto(Request $request, $id)
{
    $user = Employe::findOrFail($id);

    if ($request->hasFile('photo')) {
        $file = $request->file('photo');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('employe_photos', $fileName, 'public');

        $user->photo = '' . $filePath;
        $user->save();

        return response()->json(['updatedPhotoUrl' => $user->photo], 200);
    }

    return response()->json(['message' => 'Photo non fournie.'], 400);
}

public function changePassword1(Request $request, $id)
{
    $validatedData = $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:8',
        'confirm_password' => 'required|same:new_password',
    ]);

    $employe = Employe::findOrFail($id);

    if (!Hash::check($validatedData['current_password'], $employe->password)) {
        return response()->json(['message' => 'Mot de passe actuel incorrect'], 422);
    }

    $employe->password = $validatedData['new_password'];
    $employe->save();

    return response()->json(['message' => 'Mot de passe changé avec succès']);
}


    public function importCsv(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:csv,txt',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Erreur de validation des données.',
            'errors' => $validator->errors()
        ], 422);
    }

    $file = $request->file('file');
    $handle = fopen($file->getRealPath(), 'r');
    $errors = [];
    $imported = 0;

    if ($handle) {
        $header = true;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if ($header) {
                $header = false; // Ignore la première ligne (entêtes)
                continue;
            }

            if (count($row) < 9) { // Assurez-vous que toutes les colonnes obligatoires sont présentes
                //$errors[] = "Une ligne ne contient pas assez de colonnes : " . implode(',', $row);
                continue;
            }

            [$nom, $prenom, $photo, $adresse, $telephone, $role, $fonction, $departement_id, $email] = $row;

            try {

                // Vérification de l'unicité de l'email
                if (!empty($email) && Employe::where('email', $email)->exists()) {
                    $errors[] = "L'email '{$email}' est déjà utilisé.";
                    continue;
                }

                // Vérification de l'unicité du telephone
                if (!empty($telephone) && Employe::where('telephone', $telephone)->exists()) {
                    $errors[] = "Le telephone '{$telephone}' est déjà utilisé.";
                    continue;
                }
                // Validation des champs
                $data = [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'adresse' => $adresse,
                    'telephone' => $telephone,
                    'role' => $role,
                    'fonction' => $fonction,
                    'departement_id' => $role === 'vigile' ? null : $departement_id,
                    'email' => $email,
                    'password' => $role === 'vigile' || $role === 'administrateur' ? 'MotDePasseTemporaire' : null,
                    'cardID' => null,
                    'photo' => null, // Supposons qu'il sera géré ultérieurement
                ];

                if ($role !== 'vigile' && !Departement::find($departement_id)) {
                    $errors[] = "Le département avec l'ID '{$departement_id}' n'existe pas pour '{$nom} {$prenom}'.";
                    continue;
                }

                // Génération du matricule
                $data['matricule'] = $this->generateMatricule($role, $fonction);

                // Création de l'employé
                Employe::create($data);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Erreur lors de l'importation de '{$nom} {$prenom}': " . $e->getMessage();
            }
        }
        fclose($handle);
    }

    if (!empty($errors)) {
        return response()->json([
            'message' => "Importation terminée avec des erreurs : {$imported} employés importés.",
            'errors' => $errors
        ], 422);
    }

    return response()->json(['message' => "Importation terminée : {$imported} employés importés."]);
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

public function loginByCard(Request $request)
{
    $validated = $request->validate([
        'cardID' => 'required|string',
    ]);

    $employe = Employe::where('cardID', $validated['cardID'])->first();

    if (!$employe) {
        return response()->json(['message' => 'Carte non attribuée'], 401);
    }
    // Vérification du rôle (seulement administrateur ou vigile)
    if (!in_array($employe->role, ['administrateur', 'vigile'])) {

        return response()->json(['message' => 'role non autorisée'], 403);
    }

    // Générer un token JWT
    $token = auth('api')->login($employe);

    // Enregistrer l'historique de connexion
    $logData = [
        'utilisateur_id' => $employe->id,
        'card_id' => $employe->cardID,
        'statut_acces' => 'login',
        'nom' => $employe->nom,
        'prenom' => $employe->prenom,
        'fonction' => $employe->fonction,
    ];
    $this->sendLogToNode($logData);

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

public function getCounts()
{
    $nombreEmployes = Employe::count(); // Nombre total d'employés
    $nombreDepartements = Departement::count(); // Nombre total de départements

    return response()->json([
        'nombre_employes' => $nombreEmployes,
        'nombre_departements' => $nombreDepartements,
    ], 200);
}



}
