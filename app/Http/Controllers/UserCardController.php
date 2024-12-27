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
        $uid = $request->input('uid');
        $userType = $request->input('userType'); // 'employe' ou 'apprenant'
        $userId = $request->input('userId'); // ID de l'utilisateur

        if ($userType === 'employes') {
            $user = Employe::find($userId);
        } else {
            $user = Apprenant::find($userId);
        }

        if ($user) {
            $user->cardID = $uid;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Carte attribuée avec succès']);
        }

        return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
    }
}
