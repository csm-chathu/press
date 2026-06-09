<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Quotation;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalController extends Controller
{
    private function guardClient(): void
    {
        if (request()->user()?->role !== 'client') {
            abort(403, 'Client portal access only.');
        }
    }

    public function me(Request $request)
    {
        $this->guardClient();
        return response()->json($request->user()->load('customer'));
    }

    public function quotations(Request $request)
    {
        $this->guardClient();
        $customerId = $request->user()->customer_id;

        if (!$customerId) {
            return response()->json(['data' => []]);
        }

        $quotations = Quotation::where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->get(['id', 'quotation_number', 'title', 'status', 'total', 'valid_until', 'created_at']);

        return response()->json(['data' => $quotations]);
    }

    public function jobs(Request $request)
    {
        $this->guardClient();
        $customerId = $request->user()->customer_id;

        if (!$customerId) {
            return response()->json(['data' => []]);
        }

        $jobs = JobCard::where('customer_id', $customerId)
            ->with('machine:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'job_number', 'title', 'status', 'quantity_ordered', 'due_date', 'machine_id', 'created_at']);

        return response()->json(['data' => $jobs]);
    }

    public function jobDetail(Request $request, string $jobNumber)
    {
        $this->guardClient();
        $customerId = $request->user()->customer_id;

        $job = JobCard::where('job_number', $jobNumber)
            ->where('customer_id', $customerId)
            ->with(['machine:id,name', 'prepressTask', 'finishingTask'])
            ->firstOrFail();

        return response()->json($job);
    }

    public function proofDecision(Request $request, int $jobCardId)
    {
        $this->guardClient();
        $customerId = $request->user()->customer_id;

        $job = JobCard::where('id', $jobCardId)
            ->where('customer_id', $customerId)
            ->where('status', 'proof_approval')
            ->with('prepressTask')
            ->firstOrFail();

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'notes'    => 'nullable|string|max:1000',
        ]);

        $job->prepressTask?->update([
            'client_decision'    => $data['decision'],
            'client_decision_at' => now(),
            'client_notes'       => $data['notes'] ?? null,
        ]);

        return response()->json(['message' => 'Decision recorded', 'decision' => $data['decision']]);
    }

    public function orders(Request $request)
    {
        $this->guardClient();
        $customerId = $request->user()->customer_id;

        if (!$customerId) {
            return response()->json(['data' => []]);
        }

        $orders = Sale::where('customer_id', $customerId)
            ->whereIn('status', ['completed'])
            ->orderByDesc('created_at')
            ->get(['id', 'invoice_number', 'status', 'order_status', 'total', 'payment_status', 'sold_at']);

        return response()->json(['data' => $orders]);
    }
}
