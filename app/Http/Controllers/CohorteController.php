<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use App\Models\Apprenant;
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
            'archive' => 'boolean'
        ]);

        $cohorte = Cohorte::create([
            'nom' => $data['nom'],
            'description' => $data['description'],
            'horaires' => $data['horaires'],
            'annee' => $data['annee'],
            'archive' => $data['archive'] ?? false,  // Par défaut, archive est false
        ]);

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

        // Valider les données de la requête entrante
        $data = $request->validate([
            'nom' => 'sometimes|required|string|max:255|unique:cohortes,nom,' . $id,
            'description' => 'sometimes|required|string',
            'horaires' => 'sometimes|array',
            'horaires.*.jours' => 'sometimes|required|array',
            'horaires.*.jours.*' => 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'horaires.*.heure_debut' => 'sometimes|required|date_format:H:i',
            'horaires.*.heure_fin' => 'sometimes|required|date_format:H:i|after:horaires.*.heure_debut',
            'annee' => 'sometimes|required|digits:4|integer|min:2000|max:2100',
            'archive' => 'boolean'
        ]);

        // Préparer les horaires formatés
        $horaires = [];
        if (isset($data['horaires'])) {
            foreach ($data['horaires'] as $horaire) {
                foreach ($horaire['jours'] as $jour) {
                    // Vérifier que les heures sont définies
                    if (isset($horaire['heure_debut']) && isset($horaire['heure_fin'])) {
                        $horaires[$jour] = [
                            'heure_debut' => $horaire['heure_debut'],
                            'heure_fin' => $horaire['heure_fin']
                        ];
                    }
                }
            }
        }

        // Mise à jour des informations de la cohorte
        $cohorte->update([
            'nom' => $data['nom'] ?? $cohorte->nom,
            'description' => $data['description'] ?? $cohorte->description,
            'horaires' => $horaires,
            'annee' => $data['annee'] ?? $cohorte->annee,
            'archive' => $data['archive'] ?? $cohorte->archive,
        ]);

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

    /**
     * Archiver une cohorte.
     */
    public function archive($id)
    {
        // Trouver la cohorte par ID
        $cohorte = Cohorte::find($id);

        // Vérifier si la cohorte existe
        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        // Vérifier s'il y a des apprenants associés à la cohorte
        if ($cohorte->apprenants()->count() > 0) {
            return response()->json(['message' => 'Impossible d\'archiver la cohorte, il y a des apprenants associés.'], 400);
        }

        // Mettre à jour la colonne 'archive' à true
        $cohorte->update(['archive' => true]);

        return response()->json(['message' => 'Cohorte archivée avec succès'], 200);
    }

    public function getApprenantsByCohorte($cohorteId)
    {
        // Récupérer les apprenants associés à la cohorte
        $apprenants = Apprenant::where('cohorte_id', $cohorteId)->get();
        return response()->json($apprenants);
    }

    public function archiveMultiple(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return response()->json(['message' => 'Aucun ID de cohorte fourni.'], 400);
        }

        $cohortes = Cohorte::whereIn('id', $ids)->get();
        
        foreach ($cohortes as $cohorte) {
            // Vérifiez s'il y a des apprenants associés à la cohorte
            if ($cohorte->apprenants()->count() > 0) {
                return response()->json(['message' => "Impossible d'archiver la cohorte ID {$cohorte->id}, il y a des apprenants associés."], 400);
            }

            // Mettre à jour la colonne 'archive' à true (ou 1)
            $cohorte->update(['archive' => true]);
        }

        return response()->json(['message' => 'Cohortes archivées avec succès.'], 200);
    }
}