<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrepressTask;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrepressTaskController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = PrepressTask::with(['jobCard.customer'])
            ->when(!$user->isAdmin(), fn ($q) => $q->whereHas('jobCard', fn ($q2) => $q2->where('branch_id', $user->branch_id)))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->job_card_id, fn ($q) => $q->where('job_card_id', $request->job_card_id));

        return response()->json($query->latest()->get());
    }

    public function show(PrepressTask $prepressTask)
    {
        $this->authorizeBranch($prepressTask);
        return response()->json($prepressTask->load('jobCard.customer'));
    }

    public function update(Request $request, PrepressTask $prepressTask)
    {
        $this->authorizeBranch($prepressTask);

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['pending','artwork_received','proof_sent','revision_requested','proof_approved','plates_ready'])],
            'plate_status'   => ['sometimes', Rule::in(['not_started','in_progress','completed'])],
            'plate_count'    => 'sometimes|integer|min:0',
            'revision_count' => 'sometimes|integer|min:0',
            'notes'          => 'sometimes|nullable|string|max:2000',
            'artwork_file_path' => 'sometimes|nullable|string|max:500',
            'proof_file_path'   => 'sometimes|nullable|string|max:500',
        ]);

        if (isset($data['status'])) {
            if ($data['status'] === 'artwork_received' && !$prepressTask->artwork_uploaded_at) {
                $data['artwork_uploaded_at'] = now();
            }
            if ($data['status'] === 'proof_sent' && !$prepressTask->proof_sent_at) {
                $data['proof_sent_at'] = now();
            }
            if ($data['status'] === 'proof_approved' && !$prepressTask->proof_approved_at) {
                $data['proof_approved_at'] = now();
            }
            if ($data['status'] === 'plates_ready' && !$prepressTask->plate_completed_at) {
                $data['plate_completed_at'] = now();
            }
        }

        $prepressTask->update($data);

        AuditLog::record('prepress_task_updated', "Prepress task #{$prepressTask->id} updated", $prepressTask);

        return response()->json($prepressTask->fresh()->load('jobCard.customer'));
    }

    private function authorizeBranch(PrepressTask $task): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && $task->jobCard?->branch_id !== $user->branch_id) {
            abort(403, 'Access denied');
        }
    }
}
