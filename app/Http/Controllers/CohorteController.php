<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CohorteController extends Controller
{
    /**
     * Afficher la liste des cohortes.
     */
    public function index()
    {
        return response()->json(Cohorte::all(), 200);
    }

    /**
     * Créer une nouvelle cohorte.
     */
    public function store(Request $request)
{
    try {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:cohortes',
            'regex:/^(?!\s*$|\d*$).*$/'],
            'description' => 'required|string',
            'horaires' => 'required|array',
            'horaires.*.jours' => 'required|array',  // Validation pour les jours
            'horaires.*.heure_debut' => 'required|date_format:H:i',
            'horaires.*.heure_fin' => 'required|date_format:H:i',
            'annee' => ['required', 'regex:/^\d{4}-\d{4}$/', function ($attribute, $value, $fail) {
                $years = explode('-', $value);
                if (count($years) !== 2 || ((int)$years[1] - (int)$years[0]) !== 1) {
                    $fail('L\'année académique doit être au format YYYY-YYYY et représenter deux années consécutives.');
                }
            }],
        ]);

        $cohorte = Cohorte::create([
            'nom' => $data['nom'],
            'description' => $data['description'],
            'horaires' => $data['horaires'],
            'annee' => $data['annee'],
        ]);

        return response()->json($cohorte, 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error: ', $e->errors());
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('General error: ', ['message' => $e->getMessage()]);
        return response()->json([
            'message' => 'Erreur lors de la création de la cohorte',
            'details' => $e->getMessage(),
        ], 500);
    }
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
            $existingDepartement = Cohorte::where('nom', $row[0])->first();

            if ($existingDepartement) {
                $errors[] = "Ligne $lineNumber : Le département '{$row[0]}' existe déjà.";
            } else {
                try {
                    Cohorte::create([
                        'nom' => $row[0],
                        'description' => $row[1],
                        'horaires' => json_decode($row[2], true),
                        'annee' => $row[3],
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
            'message' => "Importation terminée avec des erreurs : {$imported} cohortes importées.",
            'errors' => $errors
        ], 422);
    }

    return response()->json(['message' => "Importation terminée : {$imported} cohortes importés."]);
}


    /**
     * Afficher une cohorte spécifique.
     */
    public function show($id)
    {
        $cohorte = Cohorte::find($id);

        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        return response()->json($cohorte, 200);
    }

    /**
     * Mettre à jour une cohorte existante.
     */
    public function update(Request $request, $id)
    {
        $cohorte = Cohorte::find($id);

        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        $request->validate([
            'nom' => 'sometimes|required|string|max:255|unique:cohortes,nom,{$id}',
            'description' => 'required|string',
            'horaires' => 'sometimes|array',
            'horaires.*.jours' => 'sometimes|required|array',
            'horaires.*.jours.*' => 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'horaires.*.heure_debut' => 'sometimes|required|date_format:H:i',
            'horaires.*.heure_fin' => 'sometimes|required|date_format:H:i',
            'horaires.*.heure_fin' => 'sometimes|required|after:horaires.*.heure_debut',
            'annee' => 'sometimes|required|digits:4|integer|min:2000|max:2100',
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

        $cohorte->update(['nom' => $data['nom'] ?? $cohorte->nom,
            'description' => $data['description'] ?? $cohorte->description,
            'horaires' => $horaires,
            'annee' => $data['annee']?? $cohorte->annee,]);

        return response()->json($cohorte, 200);
    }

    /**
     * Supprimer une cohorte.
     */
    public function destroy($id)
    {
        $cohorte = Cohorte::find($id);

        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        $cohorte->delete();

        return response()->json(['message' => 'Cohorte supprimée avec succès'], 200);
    }
}
