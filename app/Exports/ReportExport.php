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
use App\Models\Transaction;
use App\Models\Certificate;
use App\Models\ActivityLog;

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
            'daily_collection' => 'Daily Collection',
            'revenue_by_test' => 'Revenue By Test',
            'test_volume' => 'Test Volume',
            'issued_certificates' => 'Issued Certificates',
            'activity_log' => 'Activity Log',
            'expiring_inventory' => 'Expiring Inventory',
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
            'daily_collection' => ['Date & Time', 'OR Number', 'Patient', 'Designation', 'Status'],
            'revenue_by_test' => ['Test Name', 'Section', 'Unit Price', 'Total Orders', 'Total Revenue'],
            'test_volume' => ['Test Name', 'Section', 'Total', 'Final', 'Draft'],
            'issued_certificates' => ['Certificate No.', 'Type', 'Patient', 'Issued By', 'Issue Date', 'Status'],
            'activity_log' => ['Date & Time', 'Employee', 'Description'],
            'expiring_inventory' => ['Item Name', 'Section', 'Quantity', 'Expiry Date', 'Days Left', 'Urgency'],
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
            'daily_collection' => $this->dailyCollectionData(),
            'revenue_by_test' => $this->revenueByTestData(),
            'test_volume' => $this->testVolumeData(),
            'issued_certificates' => $this->issuedCertificatesData(),
            'activity_log' => $this->activityLogData(),
            'expiring_inventory' => $this->expiringInventoryData(),
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

    protected function dailyCollectionData(): Collection
    {
        return Transaction::with(['patient'])
            ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
            ->orderBy('datetime_added', 'desc')
            ->get()
            ->map(fn($t) => [
                $t->datetime_added ? Carbon::parse($t->datetime_added)->format('M d, Y h:i A') : 'N/A',
                $t->or_number ?? 'N/A',
                $t->patient->full_name ?? 'N/A',
                $t->client_designation ?? 'N/A',
                ucfirst($t->status_code ?? 'completed'),
            ]);
    }

    protected function revenueByTestData(): Collection
    {
        return collect(DB::table('transaction_detail')
            ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
            ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
            ->leftJoin('section', 'test.section_id', '=', 'section.section_id')
            ->whereBetween('transaction.datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
            ->when($this->sectionId, fn($q) => $q->where('test.section_id', $this->sectionId))
            ->select(
                'test.label as test_name', 'section.label as section_name',
                'test.current_price',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(test.current_price) as total_revenue')
            )
            ->groupBy('test.test_id', 'test.label', 'section.label', 'test.current_price')
            ->orderByDesc('total_revenue')
            ->get())
            ->map(fn($r) => [
                $r->test_name,
                $r->section_name ?? 'N/A',
                number_format($r->current_price, 2),
                $r->total_orders,
                number_format($r->total_revenue, 2),
            ]);
    }

    protected function testVolumeData(): Collection
    {
        return LabResult::with(['test', 'test.section'])
            ->whereBetween('result_date', [$this->startDate, $this->endDate])
            ->when($this->sectionId, fn($q) => $q->whereHas('test', fn($sq) => $sq->where('section_id', $this->sectionId)))
            ->select(
                'test_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw("SUM(CASE WHEN status = 'final' THEN 1 ELSE 0 END) as final_count"),
                DB::raw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
            )
            ->groupBy('test_id')
            ->orderByDesc('total_count')
            ->get()
            ->map(fn($v) => [
                $v->test->label ?? 'N/A',
                $v->test->section->label ?? 'N/A',
                $v->total_count,
                $v->final_count,
                $v->draft_count,
            ]);
    }

    protected function issuedCertificatesData(): Collection
    {
        return Certificate::with(['patient', 'issuedBy'])
            ->whereBetween('issue_date', [$this->startDate, $this->endDate])
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(fn($c) => [
                $c->certificate_number ?? 'N/A',
                ucfirst($c->certificate_type ?? 'N/A'),
                $c->patient->full_name ?? 'N/A',
                $c->issuedBy->full_name ?? 'N/A',
                $c->issue_date ? Carbon::parse($c->issue_date)->format('M d, Y') : 'N/A',
                ucfirst($c->status ?? 'draft'),
            ]);
    }

    protected function activityLogData(): Collection
    {
        return ActivityLog::with(['employee'])
            ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
            ->orderBy('datetime_added', 'desc')
            ->get()
            ->map(fn($l) => [
                $l->datetime_added ? Carbon::parse($l->datetime_added)->format('M d, Y h:i A') : 'N/A',
                $l->employee->full_name ?? 'N/A',
                $l->description ?? 'N/A',
            ]);
    }

    protected function expiringInventoryData(): Collection
    {
        return StockIn::with(['item', 'item.section'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays(90)->toDateString())
            ->where('expiry_date', '>=', Carbon::now()->toDateString())
            ->when($this->sectionId, fn($q) => $q->whereHas('item', fn($sq) => $sq->where('section_id', $this->sectionId)))
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(fn($s) => [
                $s->item->label ?? 'N/A',
                $s->item->section->label ?? 'N/A',
                $s->quantity ?? 0,
                $s->expiry_date ? Carbon::parse($s->expiry_date)->format('M d, Y') : 'N/A',
                Carbon::now()->diffInDays(Carbon::parse($s->expiry_date), false) . ' days',
                Carbon::now()->diffInDays(Carbon::parse($s->expiry_date), false) <= 30 ? 'Critical' : (Carbon::now()->diffInDays(Carbon::parse($s->expiry_date), false) <= 60 ? 'Warning' : 'OK'),
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
