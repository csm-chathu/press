<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportExportController extends Controller
{
    public function pdf(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'file_name' => 'nullable|string|max:150',
            'date_range' => 'nullable|string|max:80',
            'restaurant.name' => 'nullable|string|max:150',
            'restaurant.address' => 'nullable|string|max:255',
            'restaurant.city' => 'nullable|string|max:100',
            'restaurant.country' => 'nullable|string|max:100',
            'restaurant.logo_url' => 'nullable|string|max:1000',
            'headers' => 'required|array|min:1',
            'headers.*' => 'required|string|max:80',
            'rows' => 'required|array',
            'rows.*' => 'required|array',
        ]);

        $pdf = Pdf::loadView('exports.report-table', [
            'title' => $data['title'],
            'dateRange' => $data['date_range'] ?? null,
            'restaurant' => $data['restaurant'] ?? [],
            'headers' => $data['headers'],
            'rows' => $data['rows'],
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->setPaper('a4', 'portrait');

        $fileName = ($data['file_name'] ?? 'report') . '.pdf';

        return $pdf->download($fileName);
    }
}
