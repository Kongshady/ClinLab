<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\LabTestOrder;
use App\Models\OrderTest;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Test;
use App\Models\Section;
use App\Models\Physician;
use App\Models\Employee;
use Carbon\Carbon;
use App\Traits\LogsActivity;
?>

<div class="p-6" x-data="{ testSearch: '' }">
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Lab Test Orders & Results
        </h1>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashMessage): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            <div class="flex items-center justify-between">
                <p class="text-green-800 font-medium"><?php echo e($flashMessage); ?></p>
                <button @click="show = false" class="text-green-600 hover:text-green-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo e($pendingCount); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Today's Orders</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo e($todayCount); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Completed Today</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo e($completedTodayCount); ?></p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">Test Orders</h2>
            <button wire:click="openCreateModal" 
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Test Order
            </button>
        </div>

        
        <div class="p-6 border-b border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Patient</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by patient name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model.live="filterStatus" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Date</label>
                    <input type="date" wire:model.live="filterDate" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>

        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Physician</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tests</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <?php
                            $totalTests = $order->orderTests->count();
                            $completedTests = $order->orderTests->where('status', 'completed')->count();
                        ?>
                        <tr <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processElementKey('order-{{ $order->lab_test_order_id }}', get_defined_vars()); ?>wire:key="order-<?php echo e($order->lab_test_order_id); ?>" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-blue-600">#<?php echo e($order->lab_test_order_id); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo e($order->patient->full_name ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600"><?php echo e($order->physician->physician_name ?? '—'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600">
                                    <?php echo e($order->order_date ? $order->order_date->format('M d, Y h:i A') : 'N/A'); ?>

                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm font-medium text-gray-900"><?php echo e($completedTests); ?>/<?php echo e($totalTests); ?></span>
                                    <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full <?php echo e($completedTests >= $totalTests && $totalTests > 0 ? 'bg-green-500' : 'bg-blue-500'); ?>" 
                                             style="width: <?php echo e($totalTests > 0 ? ($completedTests/$totalTests*100) : 0); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php if($order->status === 'completed'): ?> bg-green-100 text-green-800
                                    <?php elseif($order->status === 'cancelled'): ?> bg-red-100 text-red-800
                                    <?php else: ?> bg-yellow-100 text-yellow-800
                                    <?php endif; ?>">
                                    <?php echo e(ucfirst($order->status)); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-1.5">
                                <button wire:click="viewOrder(<?php echo e($order->lab_test_order_id); ?>)" 
                                        class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                    View
                                </button>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->status === 'pending'): ?>
                                    <button wire:click="cancelOrder(<?php echo e($order->lab_test_order_id); ?>)" 
                                            wire:confirm="Are you sure you want to cancel this order?"
                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg transition-colors">
                                        Cancel
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No test orders found</p>
                                <p class="text-gray-400 text-sm mt-1">Create a new test order to get started.</p>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($perPage !== 'all' && method_exists($orders, 'hasPages') && $orders->hasPages()): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <?php echo e($orders->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showCreateModal): ?>
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col">
                
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Create Test Order</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Select a patient and the tests to order</p>
                    </div>
                    <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                
                <form wire:submit.prevent="createOrder" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-6 overflow-y-auto flex-1 space-y-5">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Patient <span class="text-red-500">*</span></label>
                                <select wire:model="orderPatientId" 
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Patient</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <option value="<?php echo e($patient->patient_id); ?>"><?php echo e($patient->full_name); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['orderPatientId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Physician</label>
                                <select wire:model="orderPhysicianId" 
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Physician (Optional)</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $physicians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $physician): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <option value="<?php echo e($physician->physician_id); ?>"><?php echo e($physician->physician_name); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                        </div>

                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Remarks</label>
                            <textarea wire:model="orderRemarks" rows="2" maxlength="200" placeholder="Optional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>

                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Select Tests <span class="text-red-500">*</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($selectedTests) > 0): ?>
                                    <span class="ml-2 text-blue-600 font-normal">(<?php echo e(count($selectedTests)); ?> selected)</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </label>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['selectedTests'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mb-2 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <div class="mb-3">
                                <input type="text" x-model="testSearch" placeholder="Search tests..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>

                            <div class="border border-gray-200 rounded-lg max-h-64 overflow-y-auto">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($section->tests->count() > 0): ?>
                                        <div x-data="{ sectionLabel: '<?php echo e(strtolower(addslashes($section->label))); ?>', testLabels: <?php echo e(json_encode($section->tests->pluck('label')->map(fn($l) => strtolower($l))->toArray())); ?> }"
                                             x-show="!testSearch || sectionLabel.includes(testSearch.toLowerCase()) || testLabels.some(t => t.includes(testSearch.toLowerCase()))">
                                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 sticky top-0">
                                                <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider"><?php echo e($section->label); ?></h4>
                                            </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $section->tests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $test): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                                <label x-show="!testSearch || '<?php echo e(strtolower(addslashes($test->label))); ?>'.includes(testSearch.toLowerCase()) || sectionLabel.includes(testSearch.toLowerCase())"
                                                       class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                                    <input type="checkbox" wire:model="selectedTests" value="<?php echo e($test->test_id); ?>" 
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                                                    <div class="flex-1">
                                                        <span class="text-sm text-gray-800"><?php echo e($test->label); ?></span>
                                                    </div>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($test->current_price): ?>
                                                        <span class="text-xs text-gray-400 ml-2">&#8369;<?php echo e(number_format($test->current_price, 2)); ?></span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </label>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 flex-shrink-0 rounded-b-xl">
                        <button type="button" wire:click="closeCreateModal" 
                                class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showOrderDetail && $viewingOrder): ?>
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Order #<?php echo e($viewingOrder->lab_test_order_id); ?></h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            <?php echo e($viewingOrder->order_date ? $viewingOrder->order_date->format('F d, Y h:i A') : ''); ?>

                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full 
                            <?php if($viewingOrder->status === 'completed'): ?> bg-green-100 text-green-800
                            <?php elseif($viewingOrder->status === 'cancelled'): ?> bg-red-100 text-red-800
                            <?php else: ?> bg-yellow-100 text-yellow-800
                            <?php endif; ?>">
                            <?php echo e(ucfirst($viewingOrder->status)); ?>

                        </span>
                        <button wire:click="closeOrderDetail" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                
                <div class="overflow-y-auto flex-1 p-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Patient</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo e($viewingOrder->patient->full_name ?? 'N/A'); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Physician</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo e($viewingOrder->physician->physician_name ?? '—'); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Remarks</p>
                            <p class="text-sm text-gray-700"><?php echo e($viewingOrder->remarks ?: '—'); ?></p>
                        </div>
                    </div>

                    
                    <?php
                        $totalTests = $viewingOrder->orderTests->count();
                        $completedTests = $viewingOrder->orderTests->where('status', 'completed')->count();
                        $progressPct = $totalTests > 0 ? round($completedTests / $totalTests * 100) : 0;
                    ?>
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Test Progress</span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo e($completedTests); ?>/<?php echo e($totalTests); ?> completed</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 <?php echo e($progressPct >= 100 ? 'bg-green-500' : 'bg-blue-500'); ?>" 
                                 style="width: <?php echo e($progressPct); ?>%"></div>
                        </div>
                    </div>

                    
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Ordered Tests</h4>
                    <div class="space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $viewingOrder->orderTests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $orderTest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                
                                <div class="px-4 py-3 flex items-center justify-between bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full 
                                            <?php if($orderTest->status === 'completed'): ?> bg-green-500
                                            <?php elseif($orderTest->status === 'in_progress'): ?> bg-blue-500
                                            <?php elseif($orderTest->status === 'cancelled'): ?> bg-red-500
                                            <?php else: ?> bg-yellow-500
                                            <?php endif; ?>"></div>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-900"><?php echo e($orderTest->test->label ?? 'Unknown Test'); ?></span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->test && $orderTest->test->section): ?>
                                                <span class="text-xs text-gray-400 ml-2"><?php echo e($orderTest->test->section->label); ?></span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full 
                                            <?php if($orderTest->status === 'completed'): ?> bg-green-100 text-green-700
                                            <?php elseif($orderTest->status === 'in_progress'): ?> bg-blue-100 text-blue-700
                                            <?php elseif($orderTest->status === 'cancelled'): ?> bg-red-100 text-red-700
                                            <?php else: ?> bg-yellow-100 text-yellow-700
                                            <?php endif; ?>">
                                            <?php echo e(str_replace('_', ' ', ucfirst($orderTest->status))); ?>

                                        </span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->status !== 'completed' && $orderTest->status !== 'cancelled' && !$orderTest->labResult): ?>
                                            <button wire:click="openResultModal(<?php echo e($orderTest->order_test_id); ?>)" 
                                                    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                Add Result
                                            </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>

                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->labResult): ?>
                                    <div class="px-4 py-3 border-t border-gray-100 bg-white">
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Result Value</p>
                                                <p class="text-gray-900 font-semibold"><?php echo e($orderTest->labResult->result_value ?: '—'); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Normal Range</p>
                                                <p class="text-gray-700"><?php echo e($orderTest->labResult->normal_range ?: '—'); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Result Date</p>
                                                <p class="text-gray-700"><?php echo e($orderTest->labResult->result_date ? Carbon::parse($orderTest->labResult->result_date)->format('M d, Y') : '—'); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Status</p>
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo e($orderTest->labResult->status_badge_class); ?>">
                                                    <?php echo e(ucfirst($orderTest->labResult->status)); ?>

                                                </span>
                                            </div>
                                        </div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->labResult->findings): ?>
                                            <div class="mt-2">
                                                <p class="text-xs text-gray-500 font-medium">Findings</p>
                                                <p class="text-sm text-gray-700"><?php echo e($orderTest->labResult->findings); ?></p>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->labResult->remarks): ?>
                                            <div class="mt-2">
                                                <p class="text-xs text-gray-500 font-medium">Remarks</p>
                                                <p class="text-sm text-gray-700"><?php echo e($orderTest->labResult->remarks); ?></p>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <div class="mt-2 flex items-center justify-between">
                                            <div class="flex gap-4 text-xs text-gray-500">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->labResult->performedBy): ?>
                                                    <span>Performed by: <span class="font-medium text-gray-700"><?php echo e($orderTest->labResult->performedBy->full_name); ?></span></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->labResult->verifiedBy): ?>
                                                    <span>Verified by: <span class="font-medium text-gray-700"><?php echo e($orderTest->labResult->verifiedBy->full_name); ?></span></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                            <button wire:click="openEditResultModal(<?php echo e($orderTest->labResult->lab_result_id); ?>)" 
                                                    class="px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                Edit Result
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end rounded-b-xl flex-shrink-0">
                    <button wire:click="closeOrderDetail" 
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showResultModal): ?>
    <div class="fixed inset-0 z-[60] overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Add Test Result</h3>
                    <button wire:click="closeResultModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveResult">
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Value</label>
                                <input type="text" wire:model="resultValue" placeholder="e.g., 120 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Normal Range</label>
                                <input type="text" wire:model="resultNormalRange" placeholder="e.g., 70-110 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="resultDate"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['resultDate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select wire:model="resultStatus"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Performed By</label>
                                <input type="text" value="<?php echo e(auth()->user()?->employee ? auth()->user()->employee->full_name : 'N/A'); ?>" readonly
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm text-gray-700 cursor-not-allowed">
                                <input type="hidden" wire:model="resultPerformedBy">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Verified By</label>
                                <select wire:model="resultVerifiedBy"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Employee</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <option value="<?php echo e($employee->employee_id); ?>"><?php echo e($employee->full_name); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Findings</label>
                            <textarea wire:model="resultFindings" rows="2" placeholder="Enter clinical findings..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <textarea wire:model="resultRemarks" rows="2" placeholder="Additional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                        <button type="button" wire:click="closeResultModal"
                                class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            Save Result
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showEditResultModal): ?>
    <div class="fixed inset-0 z-[60] overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Edit Test Result</h3>
                    <button wire:click="closeEditResultModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="updateResult">
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Value</label>
                                <input type="text" wire:model="editResultValue" placeholder="e.g., 120 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Normal Range</label>
                                <input type="text" wire:model="editResultNormalRange" placeholder="e.g., 70-110 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="editResultDate"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editResultDate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select wire:model="editResultStatus"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Performed By</label>
                                <select wire:model="editResultPerformedBy"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Employee</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <option value="<?php echo e($employee->employee_id); ?>"><?php echo e($employee->full_name); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Verified By</label>
                                <select wire:model="editResultVerifiedBy"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Employee</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <option value="<?php echo e($employee->employee_id); ?>"><?php echo e($employee->full_name); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Findings</label>
                            <textarea wire:model="editResultFindings" rows="2" placeholder="Enter clinical findings..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <textarea wire:model="editResultRemarks" rows="2" placeholder="Additional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                        <button type="button" wire:click="closeEditResultModal"
                                class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors">
                            Update Result
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\storage\framework/views/livewire/views/3496cec5.blade.php ENDPATH**/ ?>