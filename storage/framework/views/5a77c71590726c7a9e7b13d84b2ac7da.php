<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Physician;
use App\Models\Section;
use App\Traits\LogsActivity;
?>

<div class="p-6 space-y-6" x-data="{ 
    selectedIds: [],
    selectAll: false,
    toggleAll(ids) {
        if (this.selectAll) {
            this.selectedIds = ids;
        } else {
            this.selectedIds = [];
        }
    },
    toggleOne(id) {
        const idx = this.selectedIds.indexOf(id);
        if (idx > -1) {
            this.selectedIds.splice(idx, 1);
        } else {
            this.selectedIds.push(id);
        }
    }
}" @selection-cleared.window="selectedIds = []; selectAll = false">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashMessage): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <span class="block sm:inline"><?php echo e($flashMessage); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6">
        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Physician Management</h1>
    </div>

    <!-- Add New Physician Form -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Add New Physician</h2>
            <button wire:click="toggleForm" type="button" class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md transition-colors <?php echo e($showForm ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-pink-600 text-white hover:bg-pink-700'); ?>">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showForm): ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>Close Form</span>
                <?php else: ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add New Physician</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </button>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showForm): ?>
        <form wire:submit.prevent="save" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Physician Name *</label>
                        <input type="text" wire:model="physician_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['physician_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-600 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                        <input type="text" wire:model="specialization" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['specialization'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-600 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                               @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="val && val.length > 0 && val.length < 11">
                            <span class="text-red-500 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                        </template>
                        <p class="text-gray-400 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['contact_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-600 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" wire:model="email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-600 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Section</label>
                        <select wire:model="section_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Section (Optional)</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                <option value="<?php echo e($section->section_id); ?>"><?php echo e($section->label); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['section_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-600 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                        Add Physician
                    </button>
                </div>
            </form>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" wire:model.live="search" placeholder="Search by name, specialization, or email..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Specialization</label>
                <select wire:model.live="filterSpecialization" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">All Specializations</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $specializations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $spec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <option value="<?php echo e($spec); ?>"><?php echo e($spec); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Rows per page -->
    <div class="flex items-center space-x-3">
        <label class="text-sm font-medium text-gray-700">Rows per page:</label>
        <select wire:model.live="perPage" 
                class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-sm">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">All</option>
        </select>
    </div>

    <!-- Physicians List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Physicians List</h2>
                <!-- Delete Selected Button -->
                <div x-show="selectedIds.length > 0" x-cloak x-transition>
                    <button type="button" 
                            @click="if(confirm('Are you sure you want to delete ' + selectedIds.length + ' selected physician(s)?')) { $wire.deleteSelected(selectedIds) }"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected (<span x-text="selectedIds.length"></span>)
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left w-10">
                                <input type="checkbox" x-model="selectAll" 
                                       @change="toggleAll([<?php echo e($physicians instanceof \Illuminate\Pagination\LengthAwarePaginator ? $physicians->pluck('physician_id')->implode(',') : $physicians->pluck('physician_id')->implode(',')); ?>])"
                                       class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Specialization</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $physicians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $physician): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <tr <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processElementKey('physician-{{ $physician->physician_id }}', get_defined_vars()); ?>wire:key="physician-<?php echo e($physician->physician_id); ?>" 
                                class="hover:bg-gray-50 cursor-pointer transition-colors"
                                wire:click="showDetails(<?php echo e($physician->physician_id); ?>)">
                                <td class="px-4 py-3" wire:click.stop>
                                    <input type="checkbox" value="<?php echo e($physician->physician_id); ?>" 
                                           @change="toggleOne(<?php echo e($physician->physician_id); ?>)"
                                           :checked="selectedIds.includes(<?php echo e($physician->physician_id); ?>)"
                                           class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium"><?php echo e($physician->physician_name); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($physician->section): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?php echo e($physician->section->label); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Unassigned</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo e($physician->specialization ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo e($physician->contact_number ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo e($physician->email ?? 'N/A'); ?></td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No physicians found.</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($perPage !== 'all'): ?>
            <div class="mt-6">
                <?php echo e($physicians->links()); ?>

            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <!-- View / Edit Physician Modal -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showViewModal && $viewingPhysician): ?>
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">
                            <?php echo e($editMode ? 'Edit Physician' : 'Physician Details'); ?>

                        </h3>
                        <button type="button" wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editMode): ?>
                <!-- Edit Mode -->
                <form wire:submit.prevent="save">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Physician Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="physician_name" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['physician_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                                <input type="text" wire:model="specialization" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['specialization'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                                <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                                       @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                                       :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                                       class="w-full px-3 py-2.5 border rounded-md focus:outline-none focus:ring-1">
                                <template x-if="val && val.length > 0 && val.length < 11">
                                    <span class="text-red-500 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                                </template>
                                <p class="text-gray-400 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['contact_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" wire:model="email" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Section</label>
                                <select wire:model="section_id"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                                    <option value="">Select Section (Optional)</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <option value="<?php echo e($section->section_id); ?>"><?php echo e($section->label); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['section_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                        <button type="button" wire:click="cancelEdit" 
                                class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 bg-pink-600 text-white text-sm rounded-md font-medium hover:bg-pink-700">
                            Update Physician
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <!-- View Mode (Read-only) -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Physician Name</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo e($viewingPhysician->physician_name); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Assigned Section</p>
                            <p class="text-sm font-medium text-gray-900">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($viewingPhysician->section): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?php echo e($viewingPhysician->section->label); ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">Unassigned</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Specialization</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo e($viewingPhysician->specialization ?? 'N/A'); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Contact Number</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo e($viewingPhysician->contact_number ?? 'N/A'); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Email</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo e($viewingPhysician->email ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button type="button" wire:click="closeViewModal" 
                            class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Close
                    </button>
                    <button type="button" wire:click="enableEdit" 
                            class="px-5 py-2.5 bg-orange-500 text-white text-sm rounded-md font-medium hover:bg-orange-600">
                        Edit
                    </button>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\storage\framework/views/livewire/views/ed700a73.blade.php ENDPATH**/ ?>