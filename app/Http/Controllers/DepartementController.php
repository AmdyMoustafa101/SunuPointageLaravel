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
            'horaires' => $horaires ? json_encode($horaires) : $departement->horaires,
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

    public function archive(string $id)
    {
        // Trouver le département
        $departement = Departement::findOrFail($id);
        
        // Définir l'état d'archivage à true
        if (!$departement->archive) {
            $departement->archive = true;
            $departement->save();
        }
        
        // Retourner une réponse JSON claire
        return response()->json([
            'message' => $departement->archive ? 'Département archivé avec succès' : 'Département est déjà archivé',
            'departement' => $departement
        ], 200);
    }

    public function desarchiver(string $id)
    {
        // Trouver le département
        $departement = Departement::findOrFail($id);
        
        // Définir l'état d'archivage à false
        if ($departement->archive) {
            $departement->archive = false;
            $departement->save();
        }
        
        // Retourner une réponse JSON claire
        return response()->json([
            'message' => !$departement->archive ? 'Département désarchivé avec succès' : 'Département est déjà désarchivé',
            'departement' => $departement
        ], 200);
    }

    public function getArchivedDepartements()
    {
        $departements = Departement::where('archive', true)->get();
        return response()->json($departements, 200);
    }

    public function getDepartementsCount()
    {
        $totalDepartements = Departement::count();
        $totalArchivedDepartements = Departement::where('archive', true)->count();
        $totalActiveDepartements = Departement::where('archive', false)->count();

        return response()->json([
            'total' => $totalDepartements,
            'archived' => $totalArchivedDepartements,
            'active' => $totalActiveDepartements,
        ], 200);
    }

    public function getDepartementsByHoraire(Request $request)
    {
        $request->validate([
            'jour' => 'required|string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i',
        ]);

        $jour = $request->input('jour');
        $heureDebut = $request->input('heure_debut');
        $heureFin = $request->input('heure_fin');

        $departements = Departement::where('archive', false)
            ->whereJsonContains('horaires->' . $jour, [
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin
            ])
            ->get();

        return response()->json($departements, 200);
    }

    public function archiveMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:departements,id',
        ]);

        Departement::whereIn('id', $request->ids)->update(['archive' => true]);

        return response()->json(['message' => 'Départements archivés avec succès'], 200);
    }

    public function unarchiveMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:departements,id',
        ]);

        Departement::whereIn('id', $request->ids)->update(['archive' => false]);

        return response()->json(['message' => 'Départements désarchivés avec succès'], 200);
    }
}