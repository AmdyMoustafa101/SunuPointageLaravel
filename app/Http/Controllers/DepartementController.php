<?php

namespace App\Http\Controllers;

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
            'archive' => 'boolean',
        ]);

        // Création du département
        $departement = Departement::create([
            'nom' => $data['nom'],
            'description' => $data['description'],
            'horaires' => $data['horaires'],
            'archive' => $data['archive'] ?? false,
        ]);

        return response()->json($departement, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $departement = Departement::findOrFail($id);
        return response()->json($departement, 200);
    }

    public function getDepartementById(string $id)
    {
        // Vous pouvez réutiliser la logique de la méthode show
        return $this->show($id);
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
            'archive' => 'sometimes|boolean',
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
            'archive' => $data['archive'] ?? $departement->archive,
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

    public function archive(Request $request, string $id)
    {
        // Trouver le département
        $departement = Departement::findOrFail($id);
        
        // Inverser l'état d'archivage
        $departement->archive = !$departement->archive;
        $departement->save();
    
        // Retourner une réponse JSON claire
        return response()->json([
            'message' => 'État d\'archivage mis à jour avec succès',
            'departement' => $departement
        ], 200);
    }
}