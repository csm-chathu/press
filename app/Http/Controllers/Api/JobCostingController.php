<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\JobCosting;
use Illuminate\Http\Request;

class JobCostingController extends Controller
{
    public function show(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $jobCard->load(['quotation', 'order:id,total,quotation_id']);

        $estimated = null;
        if ($jobCard->quotation) {
            $q = $jobCard->quotation;
            $estimated = [
                'paper_cost'           => $q->paper_cost,
                'ink_cost'             => $q->ink_cost,
                'plate_cost'           => $q->plate_cost,
                'finishing_cost'       => $q->finishing_cost,
                'labour_cost'          => $q->labour_cost,
                'wastage_percent'      => $q->wastage_percent,
                'profit_margin_percent'=> $q->profit_margin_percent,
                'total'                => $q->total,
            ];
        }

        $revenueSource = null;
        $revenue = 0;
        if ($jobCard->order) {
            $revenueSource = 'sale';
            $revenue = (float) $jobCard->order->total;
        } elseif ($jobCard->quotation) {
            $revenueSource = 'quotation';
            $revenue = (float) $jobCard->quotation->total;
        }

        return response()->json([
            'costing'        => $jobCard->costing,
            'estimated'      => $estimated,
            'revenue_source' => $revenueSource,
            'revenue'        => $revenue,
        ]);
    }

    public function upsert(Request $request, JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $data = $request->validate([
            'paper_sheets'          => 'nullable|integer|min:0',
            'paper_rate_per_sheet'  => 'nullable|numeric|min:0',
            'ink_colours'           => 'nullable|integer|min:0',
            'ink_cost_per_colour'   => 'nullable|numeric|min:0',
            'plate_count'           => 'nullable|integer|min:0',
            'plate_cost_each'       => 'nullable|numeric|min:0',
            'machine_hours'         => 'nullable|numeric|min:0',
            'machine_rate_per_hour' => 'nullable|numeric|min:0',
            'labour_hours'          => 'nullable|numeric|min:0',
            'labour_rate_per_hour'  => 'nullable|numeric|min:0',
            'electricity_cost'      => 'nullable|numeric|min:0',
            'outsource_cost'        => 'nullable|numeric|min:0',
            'outsource_description' => 'nullable|string',
            'waste_percentage'      => 'nullable|numeric|min:0|max:100',
            'notes'                 => 'nullable|string',
        ]);

        // Fill defaults
        $d = array_merge([
            'paper_sheets' => 0, 'paper_rate_per_sheet' => 0,
            'ink_colours' => 0, 'ink_cost_per_colour' => 0,
            'plate_count' => 0, 'plate_cost_each' => 0,
            'machine_hours' => 0, 'machine_rate_per_hour' => 0,
            'labour_hours' => 0, 'labour_rate_per_hour' => 0,
            'electricity_cost' => 0, 'outsource_cost' => 0,
            'waste_percentage' => 0,
        ], $data);

        // Auto-compute derived fields
        $d['paper_cost']   = round($d['paper_sheets']   * $d['paper_rate_per_sheet'], 2);
        $d['ink_cost']     = round($d['ink_colours']    * $d['ink_cost_per_colour'], 2);
        $d['plate_cost']   = round($d['plate_count']    * $d['plate_cost_each'], 2);
        $d['machine_cost'] = round($d['machine_hours']  * $d['machine_rate_per_hour'], 2);
        $d['labour_cost']  = round($d['labour_hours']   * $d['labour_rate_per_hour'], 2);

        $materialsSub = $d['paper_cost'] + $d['ink_cost'] + $d['plate_cost']
            + $d['machine_cost'] + $d['labour_cost'];

        $d['waste_cost']        = round($materialsSub * $d['waste_percentage'] / 100, 2);
        $d['total_actual_cost'] = round($materialsSub + $d['waste_cost'] + $d['electricity_cost'] + $d['outsource_cost'], 2);

        // Revenue from linked sale or quotation
        $jobCard->load(['order:id,total', 'quotation:id,total']);
        $revenue = 0;
        if ($jobCard->order) {
            $revenue = (float) $jobCard->order->total;
        } elseif ($jobCard->quotation) {
            $revenue = (float) $jobCard->quotation->total;
        }

        $d['revenue']       = $revenue;
        $d['profit']        = round($revenue - $d['total_actual_cost'], 2);
        $d['profit_margin'] = $revenue > 0 ? round(($d['profit'] / $revenue) * 100, 2) : 0;

        $costing = JobCosting::updateOrCreate(
            ['job_card_id' => $jobCard->id],
            array_merge($d, ['branch_id' => $jobCard->branch_id])
        );

        return response()->json($costing);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
