<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\Models\Departement;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departements = Departement::all();
        return response()->json($departements, 200);
    }

    public function getDepartements()
{
    $departements = Departement::select('id', 'nom')->get();

    // Ajouter une option pour les employés sans département
    $departements->push([
        'id' => null,
        'nom' => 'Département Sécurité',
    ]);

    return response()->json($departements);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des données
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:departements',
            'description' => 'required|string',
            'horaires' => 'required|array',
            'horaires.*.jours' => 'required|array',  // Validation pour les jours
            'horaires.*.heure_debut' => 'required|date_format:H:i',
            'horaires.*.heure_fin' => 'required|date_format:H:i',
        ]);

        // Création du département
        $departement = Departement::create([
            'nom' => $data['nom'],
            'description' => $data['description'],
            'horaires' => $data['horaires'],
        ]);

        return response()->json($departement, 201);
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
    $lineNumber = 1; // Pour suivre les lignes du fichier CSV

    if ($handle) {
        $header = true;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;

            if ($header) {
                $header = false; // Ignore la première ligne (entêtes)
                continue;
            }

            // if (count($row) < 2) {
            //     $errors[] = "Ligne $lineNumber : une ligne ne contient pas assez de colonnes (attendu 3, trouvé " . count($row) . " colonnes).";
            //     continue; // Passer à la ligne suivante
            // }

            // Vérifier si le département existe déjà
            $existingDepartement = Departement::where('nom', $row[0])->first();

            if ($existingDepartement) {
                $errors[] = "Ligne $lineNumber : Le département '{$row[0]}' existe déjà.";
            } else {
                try {
                    Departement::create([
                        'nom' => $row[0],
                        'description' => $row[1],
                        'horaires' => json_decode($row[2], true),
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    // $errors[] = "Ligne $lineNumber : Erreur lors de l'importation du département '{$row[0]}': " . $e->getMessage();
                }
            }
        }
        fclose($handle);
    }

    // Si des erreurs existent, on les renvoie dans la réponse
    if (!empty($errors)) {
        return response()->json([
            'message' => "Importation terminée avec des erreurs : {$imported} départements importés.",
            'errors' => $errors
        ], 422);
    }

    return response()->json(['message' => "Importation terminée : {$imported} départements importés."]);
}






    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $departement = Departement::findOrFail($id);
        return response()->json($departement, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $departement = Departement::findOrFail($id);

        // Validation des données de mise à jour
        $data = $request->validate([
            'nom' => 'sometimes|string|max:255|unique:departements,nom,' . $id,
            'description' => 'sometimes|string',
            'horaires' => 'sometimes|array',
            'horaires.*.jours' => 'sometimes|required|array',
            'horaires.*.jours.*' => 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'horaires.*.heure_debut' => 'sometimes|required|date_format:H:i',
            'horaires.*.heure_fin' => 'sometimes|required|date_format:H:i',
            'horaires.*.heure_fin' => 'sometimes|required|after:horaires.*.heure_debut',
        ]);

        // Préparer les horaires formatés
        $horaires = [];
        if (isset($data['horaires'])) {
            foreach ($data['horaires'] as $horaire) {
                foreach ($horaire['jours'] as $jour) {
                    $horaires[$jour] = [
                        'heure_debut' => $horaire['heure_debut'],
                        'heure_fin' => $horaire['heure_fin']
                    ];
                }
            }
        }

        // Mise à jour des données
        $departement->update([
            'nom' => $data['nom'] ?? $departement->nom,
            'description' => $data['description'] ?? $departement->description,
            'horaires' => $horaires,
        ]);

        return response()->json($departement, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Departement::destroy($id);
        return response()->json(['message' => 'Département supprimé avec succès'], 200);
    }
}

