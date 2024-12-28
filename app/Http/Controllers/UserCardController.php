<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employe;
use App\Models\Apprenant;

class UserCardController extends Controller
{
    public function listEmployes()
    {
        return response()->json(Employe::where('archived', false)->get());
    }

    public function listApprenants()
    {
        return response()->json(Apprenant::where('archivé', false)->get());
    }

    public function assignCard(Request $request)
{
    $uid = $request->input('uid'); // ID de la carte RFID
    $userType = $request->input('userType'); // 'employe' ou 'apprenant'
    $userId = $request->input('userId'); // ID de l'utilisateur

    // Vérifier si la carte est déjà attribuée à un employé ou un apprenant
    $cardAssignedToEmployee = Employe::where('cardID', $uid)->first();
    $cardAssignedToApprenant = Apprenant::where('cardID', $uid)->first();

    if ($cardAssignedToEmployee || $cardAssignedToApprenant) {
        return response()->json([
            'success' => false,
            'message' => 'Cette carte est déjà attribuée à un autre utilisateur.'
        ], 400);
    }

    // Vérifier si l'utilisateur existe dans la table correspondante
    if ($userType === 'employes') {
        $user = Employe::find($userId);
    } else {
        $user = Apprenant::find($userId);
    }

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé.'], 404);
    }

    // Attribuer la carte à l'utilisateur
    $user->cardID = $uid;
    $user->save();

    return response()->json(['success' => true, 'message' => 'Carte attribuée avec succès.']);
}

public function unassignCard(Request $request)
{
    $validated = $request->validate([
        'userType' => 'required|string|in:employes,apprenants',
        'userId' => 'required|integer',
    ]);

    $model = $validated['userType'] === 'employes' ? Employe::class : Apprenant::class;

    $user = $model::find($validated['userId']);
    if (!$user) {
        return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
    }

    $user->cardID = null;
    $user->save();

    return response()->json(['message' => 'Carte désattribuée avec succès.']);
}

public function getUserByCard($uid)
{
    $employe = Employe::where('cardID', $uid)->first();
    $apprenant = Apprenant::where('cardID', $uid)->first();

    if ($employe) {
        return response()->json($employe, 200);
    } elseif ($apprenant) {
        return response()->json($apprenant, 200);
    }

    return response()->json(['message' => 'Utilisateur non trouvé'], 404);
}




}
