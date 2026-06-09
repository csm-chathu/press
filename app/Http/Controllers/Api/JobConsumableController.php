<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\JobConsumable;
use Illuminate\Http\Request;

class JobConsumableController extends Controller
{
    public function index(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);
        return response()->json($jobCard->consumables()->latest()->get());
    }

    public function store(Request $request, JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $data = $request->validate([
            'type'        => 'required|in:plate,ink,paper,other',
            'description' => 'required|string|max:255',
            'quantity'    => 'required|numeric|min:0',
            'unit'        => 'required|string|max:20',
            'unit_cost'   => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $data['total_cost'] = round($data['quantity'] * $data['unit_cost'], 2);

        $consumable = $jobCard->consumables()->create([
            ...$data,
            'branch_id'  => $request->user()->branch_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($consumable, 201);
    }

    public function destroy(JobConsumable $consumable)
    {
        $this->authorizeBranch($consumable->branch_id);
        $consumable->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
