<?php

namespace App\Http\Livewire;

use App\Models\Equipment;
use App\Models\Section;
use App\Models\CalibrationRecord;
use App\Models\MaintenanceRecord;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;

class EquipmentIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $sectionFilter = '';
    public $statusFilter = '';
    public $perPage = 15;

    protected $queryString = [
        'search' => ['except' => ''],
        'sectionFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSectionFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $sections = Cache::remember('sections_list', 300, function() {
            return Section::where('is_deleted', 0)
                ->select('section_id', 'label')
                ->orderBy('label')
                ->get();
        });

        $equipments = Equipment::query()
            ->select([
                'equipment_id', 'name', 'model', 'serial_no', 
                'section_id', 'status', 'description'
            ])
            ->with(['section:section_id,label'])
            ->where('is_deleted', 0)
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'LIKE', '%' . $this->search . '%')
                      ->orWhere('model', 'LIKE', '%' . $this->search . '%')
                      ->orWhere('serial_no', 'LIKE', '%' . $this->search . '%');
                });
            })
            ->when($this->sectionFilter, function($query) {
                $query->where('section_id', $this->sectionFilter);
            })
            ->when($this->statusFilter, function($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        // Get maintenance status for each equipment efficiently
        $equipmentIds = $equipments->pluck('equipment_id');
        
        $maintenanceStatus = [];
        if ($equipmentIds->isNotEmpty()) {
            $maintenanceRecords = MaintenanceRecord::whereIn('equipment_id', $equipmentIds)
                ->select('equipment_id', 'next_due_date')
                ->whereNotNull('next_due_date')
                ->orderBy('next_due_date', 'desc')
                ->get()
                ->groupBy('equipment_id')
                ->map->first();

            foreach ($maintenanceRecords as $equipmentId => $record) {
                $maintenanceStatus[$equipmentId] = [
                    'next_due' => $record->next_due_date,
                    'status' => $this->getMaintenanceStatus($record->next_due_date)
                ];
            }
        }

        return view('components.equipment.index', compact('equipments', 'sections', 'maintenanceStatus'));
    }

    private function getMaintenanceStatus($nextDueDate)
    {
        if (!$nextDueDate) return 'unknown';
        
        $daysUntilDue = now()->diffInDays($nextDueDate, false);
        
        if ($daysUntilDue < 0) return 'overdue';
        if ($daysUntilDue <= 7) return 'due-soon';
        
        return 'ok';
    }
}