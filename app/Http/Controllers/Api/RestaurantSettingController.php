<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\CloudinaryService;
use Illuminate\Http\Request;

class RestaurantSettingController extends Controller
{
    public function show(Request $request)
    {
        $branch = $this->resolveBranch($request);

        return response()->json([
            'id' => $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
            'address' => $branch->address,
            'city' => $branch->city,
            'country' => $branch->country,
            'logo_url' => $branch->logo_url,
        ]);
    }

    public function update(Request $request)
    {
        $this->requireAdmin($request);

        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:120',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            CloudinaryService::destroyImage($branch->logo_public_id);
            $uploaded = CloudinaryService::uploadLogoImage($request->file('logo'));
            $data['logo_path'] = $uploaded['url'];
            $data['logo_public_id'] = $uploaded['public_id'];
        }

        unset($data['branch_id'], $data['logo']);

        $branch->update($data);

        return response()->json([
            'message' => 'Restaurant settings updated',
            'settings' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'city' => $branch->city,
                'country' => $branch->country,
                'logo_url' => $branch->logo_url,
            ],
        ]);
    }

    private function requireAdmin(Request $request): void
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Admin access required');
        }
    }

    private function resolveBranch(Request $request)
    {
        $user = $request->user();

        $requestedBranchId = $request->query('branch_id') ?? $request->input('branch_id');
        if ($requestedBranchId !== null && $requestedBranchId !== '') {
            if (!$user->isAdmin()) {
                abort(403, 'Admin access required to manage other branches.');
            }

            $branch = Branch::find($requestedBranchId);
            if (!$branch) {
                abort(404, 'Branch not found.');
            }

            return $branch;
        }

        $branch = $user->branch;

        if (!$branch) {
            abort(422, 'No branch assigned for current user.');
        }

        return $branch;
    }
}
