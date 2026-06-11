<?php

namespace App\Support;

class PaperYieldService
{
    // Standard paper sizes in mm [width, height] (portrait orientation)
    public const SIZES = [
        'A0'   => [841,  1189],
        'A1'   => [594,  841],
        'A2'   => [420,  594],
        'A3'   => [420,  297],
        'A4'   => [297,  210],
        'A5'   => [210,  148],
        'A6'   => [148,  105],
        'SRA3' => [450,  320],
        'SRA2' => [640,  450],
        'SRA1' => [900,  640],
        'B0'   => [1000, 1414],
        'B1'   => [707,  1000],
        'B2'   => [500,  707],
        'B3'   => [353,  500],
        'B4'   => [250,  353],
        'B5'   => [176,  250],
    ];

    /**
     * Calculate how many print pieces fit on one sheet of paper.
     *
     * @param float $paperW    Paper width in mm
     * @param float $paperH    Paper height in mm
     * @param float $jobW      Finished job width in mm (after trim)
     * @param float $jobH      Finished job height in mm (after trim)
     * @param float $bleedMm   Bleed per side in mm (added to each job dimension)
     * @param float $gutterMm  Gap between pieces in mm
     * @return array{pieces_per_sheet, orientation, rows, cols, used_w, used_h, efficiency_pct}
     */
    public static function piecesPerSheet(
        float $paperW,
        float $paperH,
        float $jobW,
        float $jobH,
        float $bleedMm  = 3.0,
        float $gutterMm = 0.0
    ): array {
        // Effective job size including bleed on each side
        $effW = $jobW + ($bleedMm * 2);
        $effH = $jobH + ($bleedMm * 2);

        // Try both orientations and pick the one that yields more pieces
        $normal  = self::fitGrid($paperW, $paperH, $effW, $effH, $gutterMm);
        $rotated = self::fitGrid($paperW, $paperH, $effH, $effW, $gutterMm);

        if ($rotated['pieces'] > $normal['pieces']) {
            $best        = $rotated;
            $orientation = 'rotated';
        } else {
            $best        = $normal;
            $orientation = 'portrait';
        }

        $paperArea    = $paperW * $paperH;
        $pieceArea    = $effW * $effH;
        $efficiency   = $paperArea > 0 ? round(($best['pieces'] * $pieceArea / $paperArea) * 100, 1) : 0;

        return [
            'pieces_per_sheet' => $best['pieces'],
            'orientation'      => $orientation,
            'cols'             => $best['cols'],
            'rows'             => $best['rows'],
            'used_w_mm'        => round($best['cols'] * ($effW + $gutterMm) - $gutterMm, 1),
            'used_h_mm'        => round($best['rows'] * ($effH + $gutterMm) - $gutterMm, 1),
            'paper_w_mm'       => $paperW,
            'paper_h_mm'       => $paperH,
            'job_w_mm'         => $jobW,
            'job_h_mm'         => $jobH,
            'bleed_mm'         => $bleedMm,
            'efficiency_pct'   => $efficiency,
        ];
    }

    /**
     * Calculate total sheets needed for a job (including wastage).
     *
     * @param float $paperW
     * @param float $paperH
     * @param float $jobW
     * @param float $jobH
     * @param int   $quantity         Number of finished pieces needed
     * @param float $wastagePercent   Extra % on top (e.g. 5 = 5%)
     * @param float $bleedMm
     * @param float $gutterMm
     * @return array{pieces_per_sheet, sheets_net, sheets_with_wastage, wastage_sheets, ...}
     */
    public static function sheetsNeeded(
        float $paperW,
        float $paperH,
        float $jobW,
        float $jobH,
        int   $quantity,
        float $wastagePercent = 0.0,
        float $bleedMm  = 3.0,
        float $gutterMm = 0.0
    ): array {
        $yield      = self::piecesPerSheet($paperW, $paperH, $jobW, $jobH, $bleedMm, $gutterMm);
        $pps        = max(1, $yield['pieces_per_sheet']);

        $sheetsNet  = (int) ceil($quantity / $pps);
        $wastage    = (int) ceil($sheetsNet * $wastagePercent / 100);
        $sheetsTotal = $sheetsNet + $wastage;

        return array_merge($yield, [
            'quantity'            => $quantity,
            'wastage_percent'     => $wastagePercent,
            'sheets_net'          => $sheetsNet,
            'wastage_sheets'      => $wastage,
            'sheets_with_wastage' => $sheetsTotal,
        ]);
    }

    /** Return [width, height] for a named paper size, or null */
    public static function sizeToMm(string $size): ?array
    {
        $key = strtoupper(trim($size));
        return self::SIZES[$key] ?? null;
    }

    // ── private ──────────────────────────────────────────────────────────────

    private static function fitGrid(float $paperW, float $paperH, float $itemW, float $itemH, float $gutter): array
    {
        if ($itemW <= 0 || $itemH <= 0) {
            return ['pieces' => 0, 'cols' => 0, 'rows' => 0];
        }

        // cols = how many items fit across, accounting for gutters between pieces
        $cols = $gutter > 0
            ? (int) floor(($paperW + $gutter) / ($itemW + $gutter))
            : (int) floor($paperW / $itemW);

        $rows = $gutter > 0
            ? (int) floor(($paperH + $gutter) / ($itemH + $gutter))
            : (int) floor($paperH / $itemH);

        $cols = max(0, $cols);
        $rows = max(0, $rows);

        return ['pieces' => $cols * $rows, 'cols' => $cols, 'rows' => $rows];
    }
}
