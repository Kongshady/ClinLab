<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Equipment;
use App\Models\CalibrationRecord;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Item;
use App\Models\LabResult;

class ReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected string $reportType;
    protected string $startDate;
    protected string $endDate;
    protected ?string $sectionId;

    public function __construct(string $reportType, string $startDate, string $endDate, ?string $sectionId = null)
    {
        $this->reportType = $reportType;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->sectionId = $sectionId;
    }

    public function title(): string
    {
        return match ($this->reportType) {
            'equipment_maintenance' => 'Equipment Maintenance',
            'calibration_records' => 'Calibration Records',
            'inventory_movement' => 'Inventory Movement',
            'low_stock_alert' => 'Low Stock Alert',
            'laboratory_results' => 'Laboratory Results',
            default => 'Report',
        };
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'equipment_maintenance' => ['Equipment Name', 'Model', 'Serial Number', 'Section', 'Status', 'Purchase Date'],
            'calibration_records' => ['Equipment', 'Calibration Date', 'Next Due Date', 'Status', 'Performed By', 'Certificate No.'],
            'inventory_movement' => ['Date', 'Item', 'Type', 'Quantity', 'Supplier / Reference', 'Remarks'],
            'low_stock_alert' => ['Item Name', 'Current Stock', 'Reorder Level', 'Unit', 'Section', 'Status'],
            'laboratory_results' => ['Date', 'Patient', 'Test', 'Result Value', 'Normal Range', 'Status'],
            default => [],
        };
    }

    public function collection(): Collection
    {
        return match ($this->reportType) {
            'equipment_maintenance' => $this->equipmentData(),
            'calibration_records' => $this->calibrationData(),
            'inventory_movement' => $this->inventoryData(),
            'low_stock_alert' => $this->lowStockData(),
            'laboratory_results' => $this->labResultsData(),
            default => collect(),
        };
    }

    protected function equipmentData(): Collection
    {
        return Equipment::with(['section'])
            ->when($this->sectionId, fn($q) => $q->where('section_id', $this->sectionId))
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($e) => [
                $e->equipment_name,
                $e->model ?? 'N/A',
                $e->serial_number ?? 'N/A',
                $e->section->label ?? 'N/A',
                ucfirst($e->status ?? 'active'),
                $e->purchase_date ? Carbon::parse($e->purchase_date)->format('M d, Y') : 'N/A',
            ]);
    }

    protected function calibrationData(): Collection
    {
        return CalibrationRecord::with(['equipment', 'equipment.section'])
            ->when($this->sectionId, fn($q) => $q->whereHas('equipment', fn($sq) => $sq->where('section_id', $this->sectionId)))
            ->whereBetween('calibration_date', [$this->startDate, $this->endDate])
            ->orderBy('calibration_date', 'desc')
            ->get()
            ->map(fn($c) => [
                $c->equipment->equipment_name ?? 'N/A',
                $c->calibration_date ? Carbon::parse($c->calibration_date)->format('M d, Y') : 'N/A',
                $c->next_due_date ? Carbon::parse($c->next_due_date)->format('M d, Y') : 'N/A',
                ucfirst($c->status ?? 'pending'),
                $c->performed_by ?? 'N/A',
                $c->certificate_number ?? 'N/A',
            ]);
    }

    protected function inventoryData(): Collection
    {
        $stockIns = StockIn::with(['item', 'item.section'])
            ->when($this->sectionId, fn($q) => $q->whereHas('item', fn($sq) => $sq->where('section_id', $this->sectionId)))
            ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
            ->get()
            ->map(fn($s) => [
                $s->datetime_added ? Carbon::parse($s->datetime_added)->format('M d, Y') : 'N/A',
                $s->item->label ?? 'N/A',
                'Stock In',
                $s->quantity ?? 0,
                $s->supplier ?? 'N/A',
                $s->remarks ?? '',
            ]);

        $stockOuts = StockOut::with(['item', 'item.section'])
            ->when($this->sectionId, fn($q) => $q->whereHas('item', fn($sq) => $sq->where('section_id', $this->sectionId)))
            ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
            ->get()
            ->map(fn($s) => [
                $s->datetime_added ? Carbon::parse($s->datetime_added)->format('M d, Y') : 'N/A',
                $s->item->label ?? 'N/A',
                'Stock Out',
                $s->quantity ?? 0,
                $s->reference_number ?? 'N/A',
                $s->remarks ?? '',
            ]);

        return $stockIns->merge($stockOuts)->sortByDesc(fn($r) => $r[0]);
    }

    protected function lowStockData(): Collection
    {
        return Item::with(['section'])
            ->leftJoin('stock_in', 'item.item_id', '=', 'stock_in.item_id')
            ->leftJoin('stock_out', 'item.item_id', '=', 'stock_out.item_id')
            ->select(
                'item.*',
                \DB::raw('COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0) as current_stock')
            )
            ->groupBy('item.item_id', 'item.section_id', 'item.item_type_id', 'item.label', 'item.status_code', 'item.unit', 'item.reorder_level', 'item.is_deleted', 'item.deleted_at', 'item.deleted_by')
            ->when($this->sectionId, fn($q) => $q->where('item.section_id', $this->sectionId))
            ->where('item.is_deleted', 0)
            ->havingRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) <= item.reorder_level')
            ->orderByRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) ASC')
            ->get()
            ->map(fn($i) => [
                $i->label,
                $i->current_stock,
                $i->reorder_level ?? 0,
                $i->unit ?? 'N/A',
                $i->section->label ?? 'N/A',
                'Low Stock',
            ]);
    }

    protected function labResultsData(): Collection
    {
        return LabResult::with(['patient', 'test', 'performedBy'])
            ->whereBetween('result_date', [$this->startDate, $this->endDate])
            ->orderBy('result_date', 'desc')
            ->get()
            ->map(fn($r) => [
                $r->result_date ? Carbon::parse($r->result_date)->format('M d, Y') : 'N/A',
                $r->patient->full_name ?? 'N/A',
                $r->test->label ?? 'N/A',
                $r->result_value ?? 'N/A',
                $r->normal_range ?? 'N/A',
                ucfirst($r->status ?? 'draft'),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        return [
            // Header row style
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1324A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Data rows
            "A2:{$lastCol}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Header borders
            "A1:{$lastCol}1" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'B91C3A'],
                    ],
                ],
            ],
        ];
    }
}
