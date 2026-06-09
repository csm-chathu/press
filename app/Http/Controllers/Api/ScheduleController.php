<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\PressMachine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user      = $request->user();
        $week      = $request->input('week', now()->toDateString());
        $weekStart = Carbon::parse($week)->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $machines = PressMachine::when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'machine_type', 'capacity_per_hour']);

        // Jobs scheduled this week (per machine/date) + unscheduled active jobs
        $jobs = JobCard::with(['customer:id,name', 'machine:id,name', 'operator:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotIn('status', ['delivered'])
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                  ->orWhereNull('scheduled_date');
            })
            ->orderByRaw("CASE status
                WHEN 'printing'       THEN 1
                WHEN 'plate_making'   THEN 2
                WHEN 'proof_approval' THEN 3
                WHEN 'designing'      THEN 4
                WHEN 'waiting'        THEN 5
                WHEN 'finishing'      THEN 6
                WHEN 'quality_check'  THEN 7
                WHEN 'ready'          THEN 8
                ELSE 9 END")
            ->get();

        return response()->json([
            'jobs'       => $jobs,
            'machines'   => $machines,
            'week_start' => $weekStart->toDateString(),
            'week_end'   => $weekEnd->toDateString(),
        ]);
    }

    public function reschedule(Request $request, JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $data = $request->validate([
            'scheduled_date'      => 'nullable|date',
            'machine_id'          => 'nullable|exists:press_machines,id',
            'assigned_operator_id'=> 'nullable|exists:users,id',
        ]);

        $jobCard->update($data);

        return response()->json($jobCard->fresh(['machine:id,name', 'customer:id,name', 'operator:id,name']));
    }

    public function workload(Request $request)
    {
        $user = $request->user();

        $operators = User::whereIn('role', ['machine_operator', 'designer', 'production_manager'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->get(['id', 'name', 'role'])
            ->map(function ($op) {
                $op->job_count = JobCard::where('assigned_operator_id', $op->id)
                    ->whereNotIn('status', ['delivered', 'ready'])
                    ->count();
                return $op;
            })
            ->sortByDesc('job_count')
            ->values();

        return response()->json($operators);
    }

    public function alerts(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();
        $soon  = now()->addDays(2)->toDateString();

        $jobs = JobCard::with(['customer:id,name', 'machine:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotIn('status', ['delivered', 'ready'])
            ->whereNotNull('due_date')
            ->where(function ($q) use ($today, $soon) {
                $q->where('due_date', '<', $today)
                  ->orWhereBetween('due_date', [$today, $soon]);
            })
            ->orderBy('due_date')
            ->get(['id', 'job_number', 'title', 'status', 'due_date', 'machine_id', 'customer_id', 'is_priority']);

        return response()->json($jobs->map(fn($j) => [
            'id'         => $j->id,
            'job_number' => $j->job_number,
            'title'      => $j->title,
            'status'     => $j->status,
            'due_date'   => $j->due_date,
            'is_priority'=> $j->is_priority,
            'customer'   => $j->customer?->name,
            'machine'    => $j->machine?->name,
            'overdue'    => $j->due_date < $today,
        ]));
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
