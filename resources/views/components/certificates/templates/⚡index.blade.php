<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\CertificateTemplate;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    // Search and filters
    public $search = '';
    public $filterType = '';
    public $perPage = 10;

    // Modal state
    public $showModal = false;
    public $editMode = false;
    
    // Form fields
    public $templateId;
    #[Validate('required|string|max:255')]
    public $name = '';
    
    #[Validate('required|in:calibration,maintenance,safety,test')]
    public $type = 'calibration';
    
    #[Validate('required|string')]
    public $body_html = '';
    
    #[Validate('required|string|max:50')]
    public $version = '1.0';
    
    public $is_active = true;

    public $flashMessage = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $template = CertificateTemplate::findOrFail($id);
        
        $this->templateId = $template->id;
        $this->name = $template->name;
        $this->type = $template->type;
        $this->body_html = $template->body_html;
        $this->version = $template->version;
        $this->is_active = $template->is_active;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            $template = CertificateTemplate::findOrFail($this->templateId);
            $template->update([
                'name' => $this->name,
                'type' => $this->type,
                'body_html' => $this->body_html,
                'version' => $this->version,
                'is_active' => $this->is_active,
            ]);
            $this->logActivity("Updated certificate template ID {$this->templateId}: {$this->name}");
            $this->flashMessage = 'Template updated successfully!';
        } else {
            CertificateTemplate::create([
                'name' => $this->name,
                'type' => $this->type,
                'body_html' => $this->body_html,
                'version' => $this->version,
                'is_active' => $this->is_active,
                'created_by' => auth()->id(),
            ]);
            $this->logActivity("Created certificate template: {$this->name}");
            $this->flashMessage = 'Template created successfully!';
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive($id)
    {
        $template = CertificateTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);
        $this->logActivity("Toggled status for certificate template ID {$id}: {$template->name}");
        $this->flashMessage = 'Template status updated!';
    }

    public function delete($id)
    {
        CertificateTemplate::findOrFail($id)->delete();
        $this->logActivity("Deleted certificate template ID {$id}");
        $this->flashMessage = 'Template deleted successfully!';
    }

    public function resetForm()
    {
        $this->reset(['templateId', 'name', 'type', 'body_html', 'version', 'is_active']);
        $this->version = '1.0';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function with(): array
    {
        $query = CertificateTemplate::with('creator:id,name')
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterType, function($q) {
                $q->where('type', $this->filterType);
            })
            ->orderBy('created_at', 'desc');

        return [
            'templates' => $query->paginate($this->perPage),
        ];
    }
}; ?>

<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Certificate Templates
            </h1>
            <p class="text-slate-600 mt-1">Manage certificate templates for calibration, maintenance, and safety compliance</p>
        </div>

        @if($flashMessage)
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl">
                {{ $flashMessage }}
            </div>
        @endif

        <!-- Controls -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div class="flex flex-col sm:flex-row gap-3 flex-1">
                    <input 
                        type="text" 
                        wire:model.live="search" 
                        placeholder="Search templates..."
                        class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <select wire:model.live="filterType" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="calibration">Calibration</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="safety">Safety</option>
                        <option value="test">Test</option>
                    </select>
                </div>
                <button 
                    wire:click="openModal"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg whitespace-nowrap"
                >
                    + New Template
                </button>
            </div>
        </div>

        <!-- Templates Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Version</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Created By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($templates as $template)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $template->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $template->type === 'calibration' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $template->type === 'maintenance' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                        {{ $template->type === 'safety' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $template->type === 'test' ? 'bg-purple-100 text-purple-700' : '' }}
                                    ">
                                        {{ ucfirst($template->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $template->version }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($template->is_active)
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">Active</span>
                                    @else
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-medium">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $template->creator->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <button 
                                            wire:click="edit({{ $template->id }})"
                                            class="px-3 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            wire:click="toggleActive({{ $template->id }})"
                                            class="px-3 py-1 bg-amber-50 text-amber-600 rounded hover:bg-amber-100 transition-colors"
                                        >
                                            {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button 
                                            wire:click="delete({{ $template->id }})" 
                                            wire:confirm="Are you sure you want to delete this template?"
                                            class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p>No templates found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-200">
                {{ $templates->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-slate-900">
                            {{ $editMode ? 'Edit Template' : 'New Template' }}
                        </h2>
                        <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Template Name</label>
                            <input 
                                type="text" 
                                wire:model="name"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="e.g., Standard Calibration Certificate"
                            >
                            @error('name') <span class="text-rose-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                                <select wire:model="type" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="calibration">Calibration</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="safety">Safety</option>
                                    <option value="test">Test</option>
                                </select>
                                @error('type') <span class="text-rose-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Version</label>
                                <input 
                                    type="text" 
                                    wire:model="version"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="1.0"
                                >
                                @error('version') <span class="text-rose-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm font-medium text-slate-700">Active Template</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Template HTML</label>
                            <p class="text-xs text-slate-500 mb-2">Use placeholders like @{{equipment_name}}, @{{calibration_date}}, @{{certificate_no}}, etc.</p>
                            <textarea 
                                wire:model="body_html"
                                rows="12"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                                placeholder="Enter HTML template..."
                            ></textarea>
                            @error('body_html') <span class="text-rose-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button 
                                type="button"
                                wire:click="$set('showModal', false)"
                                class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-md"
                            >
                                {{ $editMode ? 'Update' : 'Create' }} Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
