<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use Illuminate\Http\Request;

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
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:cohortes',
            'description' => 'required|string',
            'horaires' => 'required|array',
            'horaires.*.jours' => 'required|array',  // Validation pour les jours
            'horaires.*.heure_debut' => 'required|date_format:H:i',
            'horaires.*.heure_fin' => 'required|date_format:H:i',
            'annee' => 'required|digits:4|integer|min:2000|max:2100',
        ]);

        $cohorte = Cohorte::create([
            'nom' => $data['nom'],
            'description' => $data['description'],
            'horaires' => $data['horaires'],
            'annee'=>$data['annee'],]);

        return response()->json($cohorte, 201);
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
