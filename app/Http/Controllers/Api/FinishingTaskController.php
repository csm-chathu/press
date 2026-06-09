<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinishingTask;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinishingTaskController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = FinishingTask::with(['jobCard.customer'])
            ->when(!$user->isAdmin(), fn ($q) => $q->whereHas('jobCard', fn ($q2) => $q2->where('branch_id', $user->branch_id)))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->job_card_id, fn ($q) => $q->where('job_card_id', $request->job_card_id));

        return response()->json($query->latest()->get());
    }

    public function show(FinishingTask $finishingTask)
    {
        $this->authorizeBranch($finishingTask);
        return response()->json($finishingTask->load('jobCard.customer'));
    }

    public function update(Request $request, FinishingTask $finishingTask)
    {
        $this->authorizeBranch($finishingTask);

        $data = $request->validate([
            'status'           => ['sometimes', Rule::in(['pending','in_progress','completed'])],
            'cutting'          => 'sometimes|boolean',
            'folding'          => 'sometimes|boolean',
            'binding'          => 'sometimes|boolean',
            'lamination'       => 'sometimes|boolean',
            'uv_coating'       => 'sometimes|boolean',
            'foiling'          => 'sometimes|boolean',
            'die_cutting'      => 'sometimes|boolean',
            'packaging'        => 'sometimes|boolean',
            'other_instructions' => 'sometimes|nullable|string|max:2000',
            'notes'            => 'sometimes|nullable|string|max:2000',
        ]);

        if (($data['status'] ?? null) === 'completed' && !$finishingTask->completed_at) {
            $data['completed_at'] = now();
        }

        $finishingTask->update($data);

        AuditLog::record('finishing_task_updated', "Finishing task #{$finishingTask->id} updated", $finishingTask);

        return response()->json($finishingTask->fresh()->load('jobCard.customer'));
    }

    private function authorizeBranch(FinishingTask $task): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && $task->jobCard?->branch_id !== $user->branch_id) {
            abort(403, 'Access denied');
        }
    }
}
