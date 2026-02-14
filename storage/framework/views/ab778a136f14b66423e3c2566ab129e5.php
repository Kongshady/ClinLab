<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CertificateIssue;
use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\LogsActivity;
use Illuminate\Pagination\LengthAwarePaginator;
?>

<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-400 bg-clip-text text-transparent">
                Issued Certificates
            </h1>
            <p class="text-slate-600 mt-1">View, download, and manage all issued certificates</p>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashMessage): ?>
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl">
                <?php echo e($flashMessage); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Total</p>
                        <p class="text-2xl font-bold text-slate-900"><?php echo e($stats['total']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Draft</p>
                        <p class="text-2xl font-bold text-slate-500"><?php echo e($stats['draft']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Pending</p>
                        <p class="text-2xl font-bold text-orange-600"><?php echo e($stats['pending']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Issued</p>
                        <p class="text-2xl font-bold text-emerald-600"><?php echo e($stats['issued']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Revoked</p>
                        <p class="text-2xl font-bold text-rose-600"><?php echo e($stats['revoked']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Expired</p>
                        <p class="text-2xl font-bold text-amber-600"><?php echo e($stats['expired']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                <input 
                    type="text" 
                    wire:model.live="search" 
                    placeholder="Search certificate no. or code..."
                    class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <select wire:model.live="filterStatus" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="Draft">Draft</option>
                    <option value="Pending">Pending</option>
                    <option value="Issued">Issued</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Expired">Expired</option>
                </select>
                <select wire:model.live="filterType" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="calibration">Calibration</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="lab_result">Lab Result</option>
                    <option value="safety">Safety</option>
                    <option value="compliance">Compliance</option>
                    <option value="other">Other</option>
                </select>
                <input 
                    type="date" 
                    wire:model.live="dateFrom"
                    class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="From"
                >
                <input 
                    type="date" 
                    wire:model.live="dateTo"
                    class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="To"
                >
            </div>
        </div>

        <!-- Certificates Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Certificate No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Equipment</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Issued Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Issued By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $certificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-mono text-slate-900"><?php echo e($certificate->certificate_no); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        <?php echo e($certificate->type === 'calibration' ? 'bg-blue-100 text-blue-700' : ''); ?>

                                        <?php echo e($certificate->type === 'maintenance' ? 'bg-emerald-100 text-emerald-700' : ''); ?>

                                        <?php echo e($certificate->type === 'safety' ? 'bg-amber-100 text-amber-700' : ''); ?>

                                        <?php echo e($certificate->type === 'lab_result' ? 'bg-purple-100 text-purple-700' : ''); ?>

                                        <?php echo e($certificate->type === 'compliance' ? 'bg-cyan-100 text-cyan-700' : ''); ?>

                                        <?php echo e($certificate->type === 'other' ? 'bg-slate-100 text-slate-700' : ''); ?>

                                    ">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $certificate->type))); ?>

                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?php echo e($certificate->equipment_name); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?php echo e($certificate->issued_at ? $certificate->issued_at->format('M d, Y') : 'N/A'); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->status === 'Draft'): ?>
                                        <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-medium">Draft</span>
                                    <?php elseif($certificate->status === 'Pending'): ?>
                                        <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">Pending</span>
                                    <?php elseif($certificate->status === 'Issued'): ?>
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">Issued</span>
                                    <?php elseif($certificate->status === 'Revoked'): ?>
                                        <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-medium">Revoked</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Expired</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->source === 'generated'): ?>
                                        <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-medium">Auto-Generated</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-slate-50 text-slate-600 rounded-full text-xs font-medium">Manual</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?php echo e($certificate->generated_by); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->source === 'generated'): ?>
                                            
                                            <button
                                                wire:click="downloadPdf(<?php echo e($certificate->id); ?>)"
                                                class="px-3 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors"
                                            >
                                                Download
                                            </button>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->status === 'Pending'): ?>
                                                <button
                                                    wire:click="approve(<?php echo e($certificate->id); ?>)"
                                                    wire:confirm="Approve this certificate?"
                                                    class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded hover:bg-emerald-100 transition-colors"
                                                >
                                                    Approve
                                                </button>
                                                <button
                                                    wire:click="reject(<?php echo e($certificate->id); ?>)"
                                                    wire:confirm="Are you sure you want to reject this certificate?"
                                                    class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                                >
                                                    Reject
                                                </button>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->status === 'Issued'): ?>
                                                <button
                                                    wire:click="revoke(<?php echo e($certificate->id); ?>, 'generated')"
                                                    wire:confirm="Are you sure you want to revoke this certificate?"
                                                    class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                                >
                                                    Revoke
                                                </button>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php else: ?>
                                            
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->status === 'Draft'): ?>
                                                <button
                                                    wire:click="markIssued(<?php echo e($certificate->id); ?>)"
                                                    wire:confirm="Mark this certificate as issued?"
                                                    class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded hover:bg-emerald-100 transition-colors"
                                                >
                                                    Mark Issued
                                                </button>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->status === 'Issued'): ?>
                                                <button
                                                    wire:click="revoke(<?php echo e($certificate->id); ?>, 'legacy')"
                                                    wire:confirm="Are you sure you want to revoke this certificate?"
                                                    class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                                >
                                                    Revoke
                                                </button>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p>No certificates found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-200">
                <?php echo e($certificates->links()); ?>

            </div>
        </div>
    </div>
</div><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\storage\framework/views/livewire/views/735d135a.blade.php ENDPATH**/ ?>