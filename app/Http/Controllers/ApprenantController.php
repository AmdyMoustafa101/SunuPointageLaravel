<?php

namespace App\Http\Controllers;

use App\Models\Apprenant;
use App\Models\Cohorte;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApprenantsExport;
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

    public function indexB(Request $request)
    {
        // Récupération des paramètres de filtre
        $query = Apprenant::with('cohorte'); // Eager loading de la cohorte

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('adresse', 'like', "%$search%");
            });
        }

        if ($request->has('cohorte_id')) {
            $query->where('cohorte_id', $request->cohorte_id);
        }

        // Pagination de la liste avec un maximum de 10 résultats
        $apprenants = $query->paginate(10);

        return response()->json($apprenants);
    }

    public function getCohortes()
    {
        $cohortes = Cohorte::all();
        return response()->json($cohortes);
    }

     // Exporter les apprenants en CSV
     public function exportCSV()
{
    $apprenants = Apprenant::with('cohorte')->get();
    $csvData = [];

    foreach ($apprenants as $apprenant) {
        $csvData[] = [
            'Nom' => $apprenant->nom,
            'Prénom' => $apprenant->prenom,
            'Adresse' => $apprenant->adresse,
            'Matricule' => $apprenant->matricule,
            'Téléphone' => $apprenant->telephone,
            'Cohorte' => $apprenant->cohorte ? $apprenant->cohorte->nom : '',
        ];
    }

    $csvFileName = 'apprenants.csv';
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
         return Excel::download(new ApprenantsExport, 'apprenants.xlsx');
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

    public function update(Request $request, $id)
{
    $apprenant = Apprenant::findOrFail($id);

    $request->validate([
        'nom' => 'sometimes|string|max:255',
        'prenom' => 'sometimes|string|max:255',
        'adresse' => 'sometimes|string',
        'telephone' => 'sometimes|string|max:20|unique:apprenants,telephone,' . $id,
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'cohorte_id' => 'nullable|exists:cohortes,id',
    ]);

    // Mise à jour de la photo si nécessaire
    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('photos', 'public');
        $apprenant->photo = $photoPath;
    }

    $apprenant->update($request->except('photo'));

    return response()->json($apprenant, 200);
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

            if (count($row) < 5) { // Assurez-vous que toutes les colonnes obligatoires sont présentes
                //$errors[] = "Une ligne ne contient pas assez de colonnes : " . implode(',', $row);
                continue;
            }

            [$nom, $prenom, $adresse, $telephone, $cohorte_id] = $row;

            try {
                // Vérification de l'unicité du téléphone
                if (!empty($telephone) && Apprenant::where('telephone', $telephone)->exists()) {
                    $errors[] = "Le numéro de téléphone '{$telephone}' est déjà utilisé.";
                    continue;
                }

                // Vérification de l'existence de la cohorte
                $cohorte = Cohorte::find($cohorte_id);
                if (!$cohorte) {
                    $errors[] = "La cohorte avec l'ID '{$cohorte_id}' n'existe pas.";
                    continue;
                }

                // Génération du matricule (se fait automatiquement via le modèle)

                // Création de l'apprenant
                Apprenant::create([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'adresse' => $adresse,
                    'telephone' => $telephone,
                    'photo' => null, // La photo n'est pas gérée ici
                    'cohorte_id' => $cohorte_id,
                    'archivé' => false,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Erreur lors de l'importation de '{$nom} {$prenom}': " . $e->getMessage();
            }
        }
        fclose($handle);
    }

    if (!empty($errors)) {
        return response()->json([
            'message' => "Importation terminée avec des erreurs : {$imported} apprenants importés.",
            'errors' => $errors
        ], 422);
    }

    return response()->json(['message' => "Importation terminée : {$imported} apprenants importés."]);
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
}
