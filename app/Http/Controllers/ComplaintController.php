<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function submitComplaint(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $user->customer->id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Done!',
            'complaint_id' => $complaint->id
        ], 201);
    }
    public function getCustomerComplaints()
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $complaints = Complaint::where('customer_id', $user->customer->id)->get();

        return response()->json([
            'message' => 'complaints:',
            'complaints' => $complaints
        ], 200);
    }
}
