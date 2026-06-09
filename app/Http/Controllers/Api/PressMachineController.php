<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PressMachine;
use Illuminate\Http\Request;

class PressMachineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $machines = PressMachine::withCount(['jobCards', 'productionJobs' => fn($q) => $q->where('status', 'in_progress')])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('type'), fn($q) => $q->where('machine_type', $request->input('type')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('name')
            ->get();

        return response()->json($machines);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'machine_type'       => 'required|in:' . implode(',', PressMachine::types()),
            'model_number'       => 'nullable|string|max:100',
            'manufacturer'       => 'nullable|string|max:150',
            'capacity_per_hour'  => 'nullable|integer|min:1',
            'status'             => 'nullable|in:active,maintenance,inactive',
            'notes'              => 'nullable|string',
        ]);
        $data['branch_id'] = $request->user()->branch_id;
        $data['status']    = $data['status'] ?? 'active';

        return response()->json(PressMachine::create($data), 201);
    }

    public function show(PressMachine $pressMachine)
    {
        $this->authorizeBranch($pressMachine->branch_id);
        return response()->json($pressMachine->load([
            'jobCards' => fn($q) => $q->whereNotIn('status', ['delivered'])->with('customer:id,name')->latest()->take(10),
        ]));
    }

    public function update(Request $request, PressMachine $pressMachine)
    {
        $this->authorizeBranch($pressMachine->branch_id);

        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'machine_type'      => 'required|in:' . implode(',', PressMachine::types()),
            'model_number'      => 'nullable|string|max:100',
            'manufacturer'      => 'nullable|string|max:150',
            'capacity_per_hour' => 'nullable|integer|min:1',
            'status'            => 'nullable|in:active,maintenance,inactive',
            'notes'             => 'nullable|string',
        ]);

        $pressMachine->update($data);
        return response()->json($pressMachine);
    }

    public function destroy(PressMachine $pressMachine)
    {
        $this->authorizeBranch($pressMachine->branch_id);

        if ($pressMachine->jobCards()->whereNotIn('status', ['delivered'])->exists()) {
            return response()->json(['message' => 'Cannot delete a machine with active jobs'], 422);
        }

        $pressMachine->delete();
        return response()->json(['message' => 'Machine deleted']);
    }

    public function all(Request $request)
    {
        $user = $request->user();
        $machines = PressMachine::where('status', 'active')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get(['id', 'name', 'machine_type']);

        return response()->json($machines);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
