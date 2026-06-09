<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinishingTask;
use App\Models\JobCard;
use App\Models\PrepressTask;
use App\Support\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobCardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $jobs = JobCard::with([
            'customer:id,name,phone',
            'machine:id,name',
            'operator:id,name',
            'createdBy:id,name',
        ])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('search'), fn($q) => $q->where(function ($inner) use ($request) {
                $s = $request->input('search');
                $inner->where('job_number', 'like', "%$s%")
                    ->orWhere('title', 'like', "%$s%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%"));
            }))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->input('customer_id')))
            ->when($request->filled('machine_id'), fn($q) => $q->where('machine_id', $request->input('machine_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'            => 'nullable|exists:customers,id',
            'order_id'               => 'nullable|exists:sales,id',
            'quotation_id'           => 'nullable|exists:quotations,id',
            'title'                  => 'required|string|max:255',
            'product_description'    => 'nullable|string',
            'paper_type'             => 'nullable|string|max:100',
            'gsm'                    => 'nullable|integer|min:1',
            'size'                   => 'nullable|string|max:50',
            'width_mm'               => 'nullable|numeric|min:0',
            'height_mm'              => 'nullable|numeric|min:0',
            'quantity_ordered'       => 'nullable|integer|min:1',
            'color_count'            => 'nullable|string|max:20',
            'printing_method'        => 'nullable|string|max:50',
            'printing_instructions'  => 'nullable|string',
            'finishing_instructions' => 'nullable|string',
            'delivery_instructions'  => 'nullable|string',
            'machine_id'             => 'nullable|exists:press_machines,id',
            'assigned_operator_id'   => 'nullable|exists:users,id',
            'artwork_status'         => 'nullable|in:pending,received,reviewing,approved',
            'scheduled_date'         => 'nullable|date',
            'due_date'               => 'nullable|date',
            'notes'                  => 'nullable|string',
            // Finishing options
            'finishing'              => 'nullable|array',
            'finishing.cutting'      => 'nullable|boolean',
            'finishing.folding'      => 'nullable|boolean',
            'finishing.binding'      => 'nullable|boolean',
            'finishing.lamination'   => 'nullable|boolean',
            'finishing.uv_coating'   => 'nullable|boolean',
            'finishing.foiling'      => 'nullable|boolean',
            'finishing.die_cutting'  => 'nullable|boolean',
            'finishing.packaging'    => 'nullable|boolean',
            'finishing.lamination_type' => 'nullable|string',
            'finishing.binding_type'    => 'nullable|string',
            'finishing.other_instructions' => 'nullable|string',
        ]);

        $jobNumber = JobCard::generateNumber();
        $jobCard = JobCard::create([
            ...$data,
            'branch_id'   => $request->user()->branch_id,
            'job_number'  => $jobNumber,
            'order_date'  => now()->toDateString(),
            'status'      => 'waiting',
            'qr_code'     => url("/track/{$jobNumber}"),
            'created_by'  => $request->user()->id,
        ]);

        // Create pre-press task record
        PrepressTask::create([
            'branch_id'   => $request->user()->branch_id,
            'job_card_id' => $jobCard->id,
            'status'      => 'pending',
        ]);

        // Create finishing task if finishing options given
        $finishing = $data['finishing'] ?? [];
        FinishingTask::create([
            'branch_id'    => $request->user()->branch_id,
            'job_card_id'  => $jobCard->id,
            'cutting'      => (bool) ($finishing['cutting'] ?? false),
            'folding'      => (bool) ($finishing['folding'] ?? false),
            'binding'      => (bool) ($finishing['binding'] ?? false),
            'lamination'   => (bool) ($finishing['lamination'] ?? false),
            'uv_coating'   => (bool) ($finishing['uv_coating'] ?? false),
            'foiling'      => (bool) ($finishing['foiling'] ?? false),
            'die_cutting'  => (bool) ($finishing['die_cutting'] ?? false),
            'packaging'    => (bool) ($finishing['packaging'] ?? false),
            'lamination_type'      => $finishing['lamination_type'] ?? null,
            'binding_type'         => $finishing['binding_type'] ?? null,
            'other_instructions'   => $finishing['other_instructions'] ?? null,
            'status'       => 'pending',
        ]);

        AuditLog::record('job_card_created', "Job Card {$jobCard->job_number} created", $jobCard);

        return response()->json(
            $jobCard->load(['customer', 'machine', 'operator', 'prepressTask', 'finishingTask']),
            201
        );
    }

    public function show(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);
        // Ensure qr_code always reflects the correct tracking URL (handles old records)
        $jobCard->qr_code = url("/track/{$jobCard->job_number}");
        return response()->json($jobCard->load([
            'customer', 'order', 'quotation', 'machine', 'operator',
            'createdBy:id,name', 'productionJobs.machine', 'productionJobs.operator',
            'prepressTask', 'finishingTask', 'deliveryItems',
        ]));
    }

    public function update(Request $request, JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $data = $request->validate([
            'customer_id'            => 'nullable|exists:customers,id',
            'title'                  => 'required|string|max:255',
            'product_description'    => 'nullable|string',
            'paper_type'             => 'nullable|string|max:100',
            'gsm'                    => 'nullable|integer|min:1',
            'size'                   => 'nullable|string|max:50',
            'width_mm'               => 'nullable|numeric|min:0',
            'height_mm'              => 'nullable|numeric|min:0',
            'quantity_ordered'       => 'nullable|integer|min:1',
            'color_count'            => 'nullable|string|max:20',
            'printing_method'        => 'nullable|string|max:50',
            'printing_instructions'  => 'nullable|string',
            'finishing_instructions' => 'nullable|string',
            'delivery_instructions'  => 'nullable|string',
            'machine_id'             => 'nullable|exists:press_machines,id',
            'assigned_operator_id'   => 'nullable|exists:users,id',
            'artwork_status'         => 'nullable|in:pending,received,reviewing,approved',
            'status'                 => 'nullable|in:' . implode(',', array_keys(JobCard::$statuses)),
            'scheduled_date'         => 'nullable|date',
            'due_date'               => 'nullable|date',
            'notes'                  => 'nullable|string',
        ]);

        if (isset($data['status']) && $data['status'] === 'delivered' && !$jobCard->completed_at) {
            $data['completed_at'] = now();
        }

        $jobCard->update($data);

        AuditLog::record('job_card_updated', "Job Card {$jobCard->job_number} status → {$jobCard->status}", $jobCard);

        return response()->json($jobCard->load(['customer', 'machine', 'operator', 'prepressTask', 'finishingTask']));
    }

    public function clone(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $jobNumber = JobCard::generateNumber();
        $clone = JobCard::create([
            'branch_id'              => $jobCard->branch_id,
            'job_number'             => $jobNumber,
            'customer_id'            => $jobCard->customer_id,
            'quotation_id'           => $jobCard->quotation_id,
            'title'                  => $jobCard->title . ' (Copy)',
            'product_description'    => $jobCard->product_description,
            'paper_type'             => $jobCard->paper_type,
            'gsm'                    => $jobCard->gsm,
            'size'                   => $jobCard->size,
            'width_mm'               => $jobCard->width_mm,
            'height_mm'              => $jobCard->height_mm,
            'quantity_ordered'       => $jobCard->quantity_ordered,
            'color_count'            => $jobCard->color_count,
            'printing_method'        => $jobCard->printing_method,
            'printing_instructions'  => $jobCard->printing_instructions,
            'finishing_instructions' => $jobCard->finishing_instructions,
            'delivery_instructions'  => $jobCard->delivery_instructions,
            'machine_id'             => $jobCard->machine_id,
            'assigned_operator_id'   => $jobCard->assigned_operator_id,
            'notes'                  => $jobCard->notes,
            'status'                 => 'waiting',
            'is_priority'            => false,
            'order_date'             => now()->toDateString(),
            'qr_code'                => url("/track/{$jobNumber}"),
            'created_by'             => request()->user()->id,
        ]);

        PrepressTask::create(['branch_id' => $clone->branch_id, 'job_card_id' => $clone->id, 'status' => 'pending']);

        $ft = $jobCard->finishingTask;
        FinishingTask::create([
            'branch_id'   => $clone->branch_id,
            'job_card_id' => $clone->id,
            'cutting'     => $ft?->cutting ?? false,
            'folding'     => $ft?->folding ?? false,
            'binding'     => $ft?->binding ?? false,
            'lamination'  => $ft?->lamination ?? false,
            'uv_coating'  => $ft?->uv_coating ?? false,
            'foiling'     => $ft?->foiling ?? false,
            'die_cutting' => $ft?->die_cutting ?? false,
            'packaging'   => $ft?->packaging ?? false,
            'lamination_type'    => $ft?->lamination_type,
            'binding_type'       => $ft?->binding_type,
            'other_instructions' => $ft?->other_instructions,
            'status'      => 'pending',
        ]);

        AuditLog::record('job_card_cloned', "Job Card {$clone->job_number} cloned from {$jobCard->job_number}", $clone);

        return response()->json($clone->load(['customer', 'machine', 'prepressTask', 'finishingTask']), 201);
    }

    public function togglePriority(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);
        $jobCard->update(['is_priority' => !$jobCard->is_priority]);
        return response()->json(['is_priority' => $jobCard->is_priority]);
    }

    public function destroy(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        if (in_array($jobCard->status, ['printing', 'finishing', 'quality_check'])) {
            return response()->json(['message' => 'Cannot delete a job card that is in production'], 422);
        }

        AuditLog::record('job_card_deleted', "Job Card {$jobCard->job_number} deleted", $jobCard);
        $jobCard->delete();

        return response()->json(['message' => 'Job card deleted']);
    }

    public function updateStatus(Request $request, JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(JobCard::$statuses)),
            'notes'  => 'nullable|string',
        ]);

        $jobCard->update([
            'status'       => $data['status'],
            'notes'        => $data['notes'] ?? $jobCard->notes,
            'completed_at' => $data['status'] === 'delivered' ? now() : $jobCard->completed_at,
        ]);

        AuditLog::record('job_status_changed', "Job {$jobCard->job_number}: → {$data['status']}", $jobCard);

        // Notify customer via SMS when status changes to a customer-visible milestone
        $notifyStatuses = ['proof_approval', 'ready', 'delivered'];
        if (in_array($data['status'], $notifyStatuses, true)) {
            $jobCard->load('customer:id,name,phone');
            $phone = $jobCard->customer?->phone;
            if ($phone) {
                $label   = JobCard::$statuses[$data['status']] ?? $data['status'];
                $trackUrl = url("/track/{$jobCard->job_number}");
                $msg = "LMUC Press: Your job '{$jobCard->title}' ({$jobCard->job_number}) is now: {$label}. Track: {$trackUrl}";
                SmsService::send($phone, $msg);
            }
        }

        return response()->json($jobCard->fresh(['customer', 'machine']));
    }

    public function queue(Request $request)
    {
        $user = $request->user();
        $jobs = JobCard::with(['customer:id,name', 'machine:id,name', 'operator:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotIn('status', ['delivered'])
            ->orderByRaw("CASE status
                WHEN 'printing'      THEN 1
                WHEN 'plate_making'  THEN 2
                WHEN 'proof_approval'THEN 3
                WHEN 'designing'     THEN 4
                WHEN 'waiting'       THEN 5
                WHEN 'finishing'     THEN 6
                WHEN 'quality_check' THEN 7
                WHEN 'ready'         THEN 8
                ELSE 9 END")
            ->orderBy('due_date')
            ->get();

        return response()->json($jobs);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
