<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const ROLE_OPTIONS = [
        'admin', 'owner', 'manager', 'cashier', 'store_keeper',
        'sales', 'estimator', 'designer', 'production_manager',
        'machine_operator', 'accountant', 'dispatch_officer', 'client',
    ];

    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) abort(403, 'Admin access required');

        $users = User::with(['branch:id,name,code', 'customer:id,name'])
            ->when($request->search, fn($q, $s) => $q->where(function($inner) use ($s) {
                $inner->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%");
            }))
            ->when($request->branch_id, fn($q, $b) => $q->where('branch_id', $b))
            ->when($request->role, fn($q, $r) => $q->where('role', $r))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) abort(403, 'Admin access required');

        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email',
            'password'               => 'required|string|min:6',
            'role'                   => 'required|in:' . implode(',', self::ROLE_OPTIONS),
            'branch_id'              => 'nullable|exists:branches,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'can_override_gold_rate' => 'boolean',
            'can_delete_transactions'=> 'boolean',
            'is_active'              => 'boolean',
        ]);

        $data = $this->normalizePermissions($data);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        AuditLog::record('user_created', "Created user: {$user->name} ({$user->email})", $user);

        return response()->json($user->load('branch:id,name,code'), 201);
    }

    public function update(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) abort(403, 'Admin access required');

        $data = $request->validate([
            'name'                   => 'sometimes|string|max:255',
            'email'                  => "sometimes|email|unique:users,email,{$user->id}",
            'password'               => 'nullable|string|min:6',
            'role'                   => 'sometimes|in:' . implode(',', self::ROLE_OPTIONS),
            'branch_id'              => 'nullable|exists:branches,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'can_override_gold_rate' => 'boolean',
            'can_delete_transactions'=> 'boolean',
            'is_active'              => 'boolean',
        ]);

        $data = $this->normalizePermissions($data);

        $old = $user->only(['name','email','role','branch_id','can_override_gold_rate','can_delete_transactions','is_active']);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        AuditLog::record('user_updated', "Updated user: {$user->name}", $user, $old, $user->fresh()->only(array_keys($old)));

        return response()->json($user->load('branch:id,name,code'));
    }

    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) abort(403, 'Admin access required');
        if ($user->id === $request->user()->id) abort(422, 'Cannot delete your own account');

        AuditLog::record('user_deleted', "Deleted user: {$user->name} ({$user->email})", $user);
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    public function branches()
    {
        return response()->json(Branch::where('is_active', true)->get(['id','name','code']));
    }

    private function normalizePermissions(array $data): array
    {
        $role = $data['role'] ?? null;

        if (in_array($role, ['admin', 'owner'], true)) {
            $data['can_override_gold_rate'] = true;
            $data['can_delete_transactions'] = true;
        }

        if ($role === 'manager' || $role === 'production_manager') {
            $data['can_override_gold_rate'] = true;
        }

        if (in_array($role, ['cashier', 'store_keeper', 'client', 'machine_operator', 'dispatch_officer'], true)) {
            $data['can_delete_transactions'] = false;
            $data['can_override_gold_rate']  = false;
        }

        // Client portal users must be linked to a customer
        if ($role !== 'client') {
            $data['customer_id'] = null;
        }

        return $data;
    }
}
