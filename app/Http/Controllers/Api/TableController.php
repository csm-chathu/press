<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $tables = Table::when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('search'), fn($q, $s) => $q->where('table_number', 'like', "%$s%"))
            ->when(request('status'), fn($q, $s) => $q->where('status', $s))
            ->orderBy('table_number')
            ->paginate(request('per_page', 50));
        return response()->json($tables);
    }

    public function all()
    {
        $user = request()->user();
        $tables = Table::when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', '!=', 'maintenance')
            ->orderBy('table_number')
            ->get();
        return response()->json($tables);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'table_number' => 'required|string|max:50|unique:tables',
            'capacity'     => 'required|integer|min:1|max:100',
            'status'       => 'nullable|in:available,occupied,reserved,maintenance',
            'notes'        => 'nullable|string',
        ]);

        $table = Table::create([
            'branch_id'    => $request->user()->branch_id,
            'table_number' => $data['table_number'],
            'capacity'     => $data['capacity'],
            'status'       => $data['status'] ?? 'available',
            'notes'        => $data['notes'] ?? null,
        ]);

        return response()->json($table, 201);
    }

    public function show(Table $table)
    {
        $this->checkBranchAccess($table);
        return response()->json($table);
    }

    public function update(Request $request, Table $table)
    {
        $this->checkBranchAccess($table);

        $data = $request->validate([
            'table_number' => 'required|string|max:50|unique:tables,table_number,' . $table->id,
            'capacity'     => 'required|integer|min:1|max:100',
            'status'       => 'nullable|in:available,occupied,reserved,maintenance',
            'notes'        => 'nullable|string',
        ]);

        $table->update([
            'table_number' => $data['table_number'],
            'capacity'     => $data['capacity'],
            'status'       => $data['status'] ?? $table->status,
            'notes'        => $data['notes'] ?? $table->notes,
        ]);

        return response()->json($table, 200);
    }

    public function destroy(Table $table)
    {
        $this->checkBranchAccess($table);
        
        $table->delete();
        return response()->json(['message' => 'Table deleted successfully'], 200);
    }

    private function checkBranchAccess(Table $table)
    {
        $user = request()->user();
        if (!$user->isAdmin() && $user->branch_id !== $table->branch_id) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
