<?php
/*
 * ============================================================
 * ReportPDF.php — Shared PDF report layout for CertsVerify
 * ============================================================
 * Extends FPDF to add:
 *  - System title/logo header on every page
 *  - Report title + generated date/time
 *  - Page numbers in the footer
 *  - A reusable table renderer for any dataset
 * ============================================================
 */

require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

class ReportPDF extends FPDF
{
    public string $reportTitle = 'Report';

    function __construct($orientation='P', $unit='mm', $size='A4') {
        parent::__construct($orientation, $unit, $size);
        // Disable FPDF's automatic page break — RenderTable handles it manually
        // so it can re-draw the table header on each new page
        $this->SetAutoPageBreak(false);
        $this->SetMargins(10, 10, 10);
    }

    // ── Page header — runs automatically on every page ──
    function Header()
    {
        $pageW = $this->GetPageWidth();

        // Blue brand bar spanning full page width
        $this->SetFillColor(30, 58, 95);   // navy
        $this->Rect(0, 0, $pageW, 22, 'F');

        // Logo mark — simple blue square with "CV" lettering
        $this->SetFillColor(37, 99, 235);  // blue
        $this->Rect(10, 5, 12, 12, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetXY(10, 8.5);
        $this->Cell(12, 6, 'CV', 0, 0, 'C');

        // System name
        $this->SetFont('Helvetica', 'B', 16);
        $this->SetXY(26, 6);
        $this->Cell(80, 10, 'CertsVerify', 0, 0, 'L');

        // Report title, right aligned
        $this->SetFont('Helvetica', '', 10);
        $this->SetXY($pageW - 110, 6);
        $this->Cell(100, 5, $this->reportTitle, 0, 0, 'R');

        // Generated date/time, right aligned below title
        $this->SetFont('Helvetica', '', 8);
        $this->SetXY($pageW - 110, 13);
        $this->Cell(100, 5, 'Generated: ' . date('d M Y, H:i:s'), 0, 0, 'R');

        $this->SetTextColor(0, 0, 0);
        $this->Ln(20);
    }

    // ── Page footer — runs automatically on every page ──
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, 'CertsVerify - Educational Document Verification System', 0, 0, 'L');
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'R');
    }

    // ── Reusable table renderer ──
    // $headers = ['Col 1', 'Col 2', ...]
    // $widths  = [40, 60, ...]  (in mm, must sum to <= usable page width)
    // $rows    = [[val1, val2, ...], ...]
    function RenderTable(array $headers, array $widths, array $rows, int $rowHeight = 6)
    {
        $totalWidth = array_sum($widths);

        // ── Header row ──
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetFillColor(30, 58, 95);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(200, 210, 220);
        $this->SetLineWidth(0.3);
        foreach ($headers as $i => $h) {
            $this->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
        }
        $this->Ln();

        // ── Data rows ──
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(20, 20, 20);
        $fill = false;

        if (empty($rows)) {
            $this->SetFillColor(255, 255, 255);
            $this->Cell($totalWidth, $rowHeight, 'No records found.', 1, 0, 'C');
            $this->Ln();
            return;
        }

        foreach ($rows as $row) {
            // ── Calculate the height this row needs ──
            // Pre-render each cell into a temp buffer to get max line count
            $this->SetFont('Helvetica', '', 8);
            $maxH = $rowHeight;
            foreach ($row as $i => $val) {
                // Use Cell width minus small padding; 4mm per line at size 8
                $lines = $this->CountLines($widths[$i] - 2, (string)$val);
                $cellH = $lines * 4.5;
                if ($cellH > $maxH) $maxH = $cellH;
            }
            $maxH = max($maxH, $rowHeight);

            // ── Page break check — add new page + re-draw header if needed ──
            $bottomMargin = 20; // mm from bottom for footer
            if ($this->GetY() + $maxH > ($this->h - $bottomMargin)) {
                $this->AddPage($this->CurOrientation);
                // Re-draw column headers on new page
                $this->SetFont('Helvetica', 'B', 9);
                $this->SetFillColor(30, 58, 95);
                $this->SetTextColor(255, 255, 255);
                foreach ($headers as $i => $h) {
                    $this->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetFont('Helvetica', '', 8);
                $this->SetTextColor(20, 20, 20);
            }

            // ── Draw the row using a Y-anchor approach ──
            // Record starting position, draw each cell as a MultiCell,
            // then restore X and advance Y to the bottom of the row.
            $yStart = $this->GetY();
            $xStart = $this->lMargin;

            // Draw row background fill
            if ($fill) {
                $this->SetFillColor(245, 248, 255);
                $this->Rect($xStart, $yStart, $totalWidth, $maxH, 'F');
            } else {
                $this->SetFillColor(255, 255, 255);
            }

            // Draw borders for the whole row first
            $xCur = $xStart;
            $this->SetDrawColor(200, 210, 220);
            $this->SetLineWidth(0.2);
            foreach ($row as $i => $val) {
                $this->Rect($xCur, $yStart, $widths[$i], $maxH);
                $xCur += $widths[$i];
            }

            // Draw text in each cell
            $xCur = $xStart;
            foreach ($row as $i => $val) {
                $this->SetXY($xCur + 1, $yStart + 1);
                $this->MultiCell($widths[$i] - 2, 4.5, (string)$val, 0, 'L');
                $xCur += $widths[$i];
            }

            // Move to next row
            $this->SetXY($xStart, $yStart + $maxH);
            $fill = !$fill;
        }
    }

    // ── Simple line counter for a given cell width and text ──
    function CountLines(float $w, string $txt): int
    {
        if (trim($txt) === '') return 1;
        // At font size 8, Helvetica averages ~1.9mm per character
        $charsPerLine = max(1, (int)floor($w / 1.9));
        $lines = 0;
        foreach (explode("\n", wordwrap($txt, $charsPerLine, "\n", true)) as $line) {
            $lines++;
        }
        return max(1, $lines);
    }

    // ── Section title used above each table ──
    function SectionTitle(string $title, string $subtitle = '')
    {
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(30, 58, 95);
        $this->Cell(0, 8, $title, 0, 1, 'L');
        if ($subtitle) {
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 6, $subtitle, 0, 1, 'L');
        }
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }
}
?>
