<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Patient;
use App\Models\LabResult;
use App\Traits\LogsActivity;
?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Patient Management
        </h1>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashMessage): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800"><?php echo e($flashMessage); ?></p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['duplicate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
            <p class="text-red-800"><?php echo e($message); ?></p>
        </div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Add New Patient Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Add New Patient</h2>
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
                    <span>Add New Patient</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </button>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showForm): ?>
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" wire:model="firstname" placeholder="Juan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['firstname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" wire:model="middlename" placeholder="Santos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['middlename'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" wire:model="lastname" placeholder="Dela Cruz" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                    <input type="date" wire:model="birthdate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['birthdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                    <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                           @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                           :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2">
                    <template x-if="val && val.length > 0 && val.length < 11">
                        <span class="text-red-500 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                    </template>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['contact_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <span class="text-xs text-gray-400 mt-1 block">11 digits only</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" wire:model="address" placeholder="123 Street, City" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700 transition-colors">
                    Add Patient
                </button>
            </div>
        </form>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Search and Filters Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-8">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Patients</label>
                    <input type="text" wire:model.live="search" placeholder="Search by name or patient ID..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select wire:model.live="filterGender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Patients List Card -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Patients Directory</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Birthdate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <tr <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processElementKey('patient-{{ $patient->patient_id }}', get_defined_vars()); ?>wire:key="patient-<?php echo e($patient->patient_id); ?>" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                                        <?php echo e(strtoupper(substr($patient->firstname, 0, 1) . substr($patient->lastname, 0, 1))); ?>

                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo e($patient->full_name); ?></div>
                                        <div class="text-xs text-gray-500">ID: <?php echo e($patient->patient_id); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo e(\Carbon\Carbon::parse($patient->birthdate)->format('M d, Y')); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo e($patient->gender); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo e($patient->contact_number ?: 'N/A'); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button type="button" wire:click="viewPatient(<?php echo e($patient->patient_id); ?>)" 
                                            class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                        View
                                    </button>
                                    <button type="button" wire:click="edit(<?php echo e($patient->patient_id); ?>)" 
                                            class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="delete(<?php echo e($patient->patient_id); ?>)" 
                                            wire:confirm="Are you sure you want to delete this patient?"
                                            class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No patients found
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($perPage !== 'all' && $patients->hasPages()): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <?php echo e($patients->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Edit Patient Modal -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editMode): ?>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Edit Patient</h3>
                <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form wire:submit.prevent="update">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" wire:model="firstname" placeholder="Juan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['firstname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" wire:model="middlename" placeholder="Santos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['middlename'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" wire:model="lastname" placeholder="Dela Cruz" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                        <input type="date" wire:model="birthdate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['birthdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                        <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                               @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2">
                        <template x-if="val && val.length > 0 && val.length < 11">
                            <span class="text-red-500 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                        </template>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['contact_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <span class="text-xs text-gray-400 mt-1 block">11 digits only</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" wire:model="address" placeholder="123 Street, City" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" 
                            wire:click="cancelEdit"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
                        Update Patient
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- View Patient Modal -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($viewMode && $viewingPatient): ?>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-10 mx-auto p-6 border w-full max-w-5xl shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Patient Details</h3>
                <button wire:click="closeView" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-purple-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                        <?php echo e(strtoupper(substr($viewingPatient->firstname, 0, 1) . substr($viewingPatient->lastname, 0, 1))); ?>

                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-gray-900"><?php echo e($viewingPatient->full_name); ?></h4>
                        <p class="text-sm text-gray-500">Patient ID: <?php echo e($viewingPatient->patient_id); ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Gender</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo e($viewingPatient->gender); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Birthdate</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo e(\Carbon\Carbon::parse($viewingPatient->birthdate)->format('M d, Y')); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Age</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo e(\Carbon\Carbon::parse($viewingPatient->birthdate)->age); ?> years old</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Contact Number</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo e($viewingPatient->contact_number ?: 'N/A'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Address</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo e($viewingPatient->address ?: 'N/A'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Date Added</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo e(\Carbon\Carbon::parse($viewingPatient->datetime_added)->format('M d, Y h:i A')); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Status</p>
                        <p class="text-sm font-medium text-green-600">Active</p>
                    </div>
                </div>

                <!-- Lab Results Section -->
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Lab Results
                    </h4>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($patientLabResults) > 0): ?>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Normal Range</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Findings</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performed By</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $patientLabResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo e($result->test->label ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?php echo e($result->result_value ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo e($result->normal_range ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo e($result->findings ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap"><?php echo e($result->result_date ? $result->result_date->format('M d, Y') : 'N/A'); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo e($result->status_badge_class); ?>">
                                            <?php echo e(ucfirst($result->status)); ?>

                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo e($result->performedBy->firstname ?? ''); ?> <?php echo e($result->performedBy->lastname ?? 'N/A'); ?>

                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($result->status === 'final'): ?>
                                        <a href="<?php echo e(route('lab-results.show', $result->lab_result_id)); ?>" target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                            Print
                                        </a>
                                        <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Pending</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm text-gray-500">No lab results found for this patient.</p>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-end pt-4 border-t mt-6">
                    <button wire:click="closeView" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\storage\framework/views/livewire/views/4847fbb4.blade.php ENDPATH**/ ?>