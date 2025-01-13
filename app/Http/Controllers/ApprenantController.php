<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use App\Exports\ApprenantsExport;
use Maatwebsite\Excel\Facades\Excel;    
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApprenantController extends Controller
{
    // Afficher la liste des apprenants
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1); // Page actuelle
        $limit = (int) $request->query('limit', 8); // Nombre d'éléments par page

        $apprenants = Apprenant::where( 'archivé', false)->get();
        
        return response()->json($apprenants, 200);
    }

   // Afficher la liste des apprenants actifs par cohorte
   public function apprenantsActifsByCohorte($cohorteId, Request $request)
   {
       $apprenants = Apprenant::where('cohorte_id', $cohorteId)->where('archivé', false)->get();
       return response()->json($apprenants, 200);
   }

   // Afficher la liste des apprenants archivés par cohorte
   public function apprenantsArchivesByCohorte($cohorteId, Request $request)
   {
       $apprenants = Apprenant::where('cohorte_id', $cohorteId)->where('archivé', true)->get();
       return response()->json($apprenants, 200);
   }


   // Afficher la liste des apprenants actifs
   public function apprenantsActifs(Request $request)
   {
       $apprenants = Apprenant::where('archivé', false)->get();
       return response()->json($apprenants, 200);
   }

   // Afficher la liste des apprenants archivés
   public function apprenantsArchives(Request $request)
   {
       $apprenants = Apprenant::where('archivé', true)->get();
       return response()->json($apprenants, 200);
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

// Récupérer les apprenants par cohorte
public function getApprenantsByCohorte($cohorteId)
{
    // Récupérer les apprenants associés à la cohorte
    $apprenants = Apprenant::where('cohorte_id', $cohorteId)->get();
    return response()->json($apprenants);
}



// Mettre à jour un apprenant spécifique
public function update(Request $request, $id)
{
    // Validation des données
    $request->validate([
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'adresse' => 'required|string',
        'telephone' => 'required|string|max:20',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'cohorte_id' => 'required|exists:cohortes,id',
    ]);

    // Trouver l'apprenant par ID
    $apprenant = Apprenant::findOrFail($id);

    // Enregistrement de la photo, si elle est fournie
    if ($request->hasFile('photo')) {
        // Supprimer l'ancienne photo si elle existe
        if ($apprenant->photo) {
            Storage::disk('public')->delete($apprenant->photo);
        }
        $photoPath = $request->file('photo')->store('photos', 'public');
    } else {
        $photoPath = $apprenant->photo; // Garder l'ancienne photo si aucune nouvelle n'est fournie
    }

    // Mettre à jour les informations de l'apprenant
    $apprenant->update([
        'nom' => $request->nom,
        'prenom' => $request->prenom,
        'adresse' => $request->adresse,
        'telephone' => $request->telephone,
        'photo' => $photoPath,
        'cohorte_id' => $request->cohorte_id,
        'archivé' => $apprenant->archivé, // Garder l'état archivé existant
    ]);

    return response()->json($apprenant, 200); // Retourner l'apprenant mis à jour
}

// Supprimer un apprenant spécifique
public function destroy($id)
{
    $apprenant = Apprenant::findOrFail($id);

    // Supprimer la photo si elle existe
    if ($apprenant->photo) {
        Storage::disk('public')->delete($apprenant->photo);
    }

    $apprenant->delete(); // Supprimer l'apprenant

    return response()->json(['message' => 'Apprenant supprimé avec succès.'], 200);
}

// Supprimer plusieurs apprenants
public function destroyMultiple(Request $request)
{
    $request->validate(['ids' => 'required|array']); // Validation des ID
    $apprenants = Apprenant::whereIn('id', $request->ids)->get();

    foreach ($apprenants as $apprenant) {
        // Supprimer la photo si elle existe
        if ($apprenant->photo) {
            Storage::disk('public')->delete($apprenant->photo);
        }
        $apprenant->delete(); // Supprimer l'apprenant
    }

    return response()->json(['message' => 'Apprenants supprimés avec succès.'], 200);
}

// Archiver un apprenant spécifique
public function archive($id) {
    $apprenant = Apprenant::find($id);
    if ($apprenant) {
        $apprenant->archivé = true;
        $apprenant->save();
        return response()->json(['message' => 'Apprenant archivé avec succès.'], 200);
    }
    return response()->json(['message' => 'Apprenant non trouvé.'], 404);
}

public function archiveMultiple(Request $request) {
    $ids = $request->input('ids');
    $apprenants = Apprenant::whereIn('id', $ids)->get();
    
    foreach ($apprenants as $apprenant) {
        $apprenant->archivé = true;
        $apprenant->save();
    }

    return response()->json(['message' => 'Apprenants archivés avec succès.'], 200);
}

// Désarchiver un apprenant spécifique
public function desarchiver($id) {
    $apprenant = Apprenant::find($id);
    if ($apprenant) {
        $apprenant->archivé = false;
        $apprenant->save();
        return response()->json(['message' => 'Apprenant désarchivé avec succès.'], 200);
    }
    return response()->json(['message' => 'Apprenant non trouvé.'], 404);
}

// Désarchiver plusieurs apprenants
public function desarchiverMultiple(Request $request) {
    $ids = $request->input('ids');
    $apprenants = Apprenant::whereIn('id', $ids)->get();
    
    foreach ($apprenants as $apprenant) {
        $apprenant->archivé = false;
        $apprenant->save();
    }

    return response()->json(['message' => 'Apprenants désarchivés avec succès.'], 200);
}









// Fonctions de Amdy

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

}
