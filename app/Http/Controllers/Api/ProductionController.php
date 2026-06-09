<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JobCard;
use App\Models\ProductionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $jobs = ProductionJob::with([
            'jobCard:id,job_number,title,customer_id,status',
            'jobCard.customer:id,name',
            'machine:id,name',
            'operator:id,name',
        ])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('machine_id'), fn($q) => $q->where('machine_id', $request->input('machine_id')))
            ->when($request->filled('operator_id'), fn($q) => $q->where('operator_id', $request->input('operator_id')))
            ->when($request->filled('job_card_id'), fn($q) => $q->where('job_card_id', $request->input('job_card_id')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'job_card_id'     => 'required|exists:job_cards,id',
            'machine_id'      => 'nullable|exists:press_machines,id',
            'operator_id'     => 'nullable|exists:users,id',
            'start_time'      => 'nullable|date',
            'end_time'        => 'nullable|date',
            'output_quantity' => 'nullable|integer|min:0',
            'waste_quantity'  => 'nullable|integer|min:0',
            'notes'           => 'nullable|string',
        ]);

        $jobCard = JobCard::findOrFail($data['job_card_id']);
        $this->authorizeBranch($jobCard->branch_id);

        $productionJob = ProductionJob::create([
            ...$data,
            'branch_id'  => $request->user()->branch_id,
            'start_time' => $data['start_time'] ?? now(),
            'status'     => isset($data['end_time']) ? 'completed' : 'in_progress',
        ]);

        // Update job card status to printing
        if ($jobCard->status === 'plate_making' || $jobCard->status === 'waiting') {
            $jobCard->update(['status' => 'printing']);
        }

        AuditLog::record('production_started', "Production started for {$jobCard->job_number}", $productionJob);

        return response()->json($productionJob->load(['jobCard', 'machine', 'operator']), 201);
    }

    public function update(Request $request, ProductionJob $productionJob)
    {
        $this->authorizeBranch($productionJob->branch_id);

        $data = $request->validate([
            'machine_id'      => 'nullable|exists:press_machines,id',
            'operator_id'     => 'nullable|exists:users,id',
            'start_time'      => 'nullable|date',
            'end_time'        => 'nullable|date|after_or_equal:start_time',
            'output_quantity' => 'nullable|integer|min:0',
            'waste_quantity'  => 'nullable|integer|min:0',
            'status'          => 'nullable|in:pending,in_progress,paused,completed',
            'notes'           => 'nullable|string',
        ]);

        $productionJob->update($data);

        if (($data['status'] ?? null) === 'completed') {
            $jobCard = $productionJob->jobCard;
            if ($jobCard && $jobCard->status === 'printing') {
                $jobCard->update(['status' => 'finishing']);
            }
            AuditLog::record('production_completed', "Production completed for {$jobCard?->job_number}", $productionJob);
        }

        return response()->json($productionJob->load(['jobCard', 'machine', 'operator']));
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $branchFilter = fn($q) => $q->when(!$user->isAdmin(), fn($qq) => $qq->where('branch_id', $user->branch_id));

        $activeJobs = JobCard::with(['customer:id,name', 'machine:id,name', 'operator:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereIn('status', ['designing', 'proof_approval', 'plate_making', 'printing', 'finishing', 'quality_check'])
            ->orderBy('due_date')
            ->get();

        $machineSummary = ProductionJob::with('machine:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'in_progress')
            ->get()
            ->groupBy('machine_id')
            ->map(fn($jobs) => [
                'machine' => $jobs->first()?->machine,
                'active_jobs' => $jobs->count(),
            ])
            ->values();

        return response()->json([
            'active_jobs'     => $activeJobs,
            'machine_summary' => $machineSummary,
        ]);
    }

    public function analytics(Request $request)
    {
        $user     = $request->user();
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        $baseQuery = ProductionJob::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(start_time)'), [$dateFrom, $dateTo])
            ->where('status', 'completed');

        // Output vs waste by machine
        $byMachine = (clone $baseQuery)
            ->with('machine:id,name')
            ->select('machine_id',
                DB::raw('SUM(output_quantity) as total_output'),
                DB::raw('SUM(waste_quantity) as total_waste'),
                DB::raw('COUNT(*) as run_count')
            )
            ->groupBy('machine_id')
            ->get()
            ->map(fn($row) => [
                'machine'      => $row->machine?->name ?? 'Unassigned',
                'total_output' => (int) $row->total_output,
                'total_waste'  => (int) $row->total_waste,
                'run_count'    => (int) $row->run_count,
                'efficiency'   => $row->total_output > 0
                    ? round(($row->total_output / ($row->total_output + $row->total_waste)) * 100, 1)
                    : 0,
            ]);

        // Daily output trend (last 30 days)
        $dailyTrend = (clone $baseQuery)
            ->select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('SUM(output_quantity) as output'),
                DB::raw('SUM(waste_quantity) as waste')
            )
            ->groupBy(DB::raw('DATE(start_time)'))
            ->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'date'   => $r->date,
                'output' => (int) $r->output,
                'waste'  => (int) $r->waste,
            ]);

        // Jobs by status
        $jobsByStatus = JobCard::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Summary totals
        $summary = (clone $baseQuery)->selectRaw(
            'SUM(output_quantity) as total_output, SUM(waste_quantity) as total_waste, COUNT(*) as total_runs'
        )->first();

        return response()->json([
            'by_machine'    => $byMachine,
            'daily_trend'   => $dailyTrend,
            'jobs_by_status'=> $jobsByStatus,
            'summary'       => [
                'total_output' => (int) ($summary->total_output ?? 0),
                'total_waste'  => (int) ($summary->total_waste  ?? 0),
                'total_runs'   => (int) ($summary->total_runs   ?? 0),
                'efficiency'   => ($summary->total_output ?? 0) > 0
                    ? round(($summary->total_output / ($summary->total_output + $summary->total_waste)) * 100, 1)
                    : 0,
            ],
        ]);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
