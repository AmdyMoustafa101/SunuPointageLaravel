<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeaveRequestController extends Controller
{
    public function index()
    {
        try {
            $leaveRequests = LeaveRequest::all();
            return response()->json($leaveRequests);
        } catch (\Exception $e) {
            Log::error('Error fetching leave requests: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch leave requests'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'departement_id' => 'required|exists:departements,id', // Validation de la clé étrangère
                'type' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
            ]);

            // Vérifiez si l'employé existe
            $employe = Employe::where('nom', $request->nom)
                            ->where('prenom', $request->prenom)
                            ->first();

            if (!$employe) {
                return response()->json(['error' => 'Employee does not exist'], 404);
            }

            $leaveRequest = new LeaveRequest([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'departement_id' => $request->departement_id,
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            $leaveRequest->save();

            return response()->json($leaveRequest, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating leave request: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to create leave request: ' . $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            return response()->json($leaveRequest);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Leave request not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching leave request: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch leave request'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);

            $request->validate([
                'status' => 'required|string|in:pending,approved,rejected',
            ]);

            $leaveRequest->status = $request->status;
            $leaveRequest->save();

            return response()->json($leaveRequest);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Leave request not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating leave request: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to update leave request: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            $leaveRequest->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Leave request not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting leave request: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to delete leave request: ' . $e->getMessage()], 500);
        }
    }

    public function getPendingLeave()
    {
        try {
            $pendingLeaveRequests = LeaveRequest::where('status', 'pending')->get();
            return response()->json($pendingLeaveRequests);
        } catch (\Exception $e) {
            Log::error('Error fetching pending leave requests: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch pending leave requests: ' . $e->getMessage()], 500);
        }
    }
}