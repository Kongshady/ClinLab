<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\StockUsage;
use App\Models\Item;
use App\Models\Employee;
use App\Models\Section;
use App\Traits\LogsActivity;
?>

<div class="min-h-screen bg-gray-50 max-w-8xl mx-auto px-4 sm:px-4 lg:px-6 py-8">
    <div class="max-w-8xl  ">
        <!-- Success Message -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashMessage): ?>
            <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-900"><?php echo e($flashMessage); ?></span>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
            <p class="mt-1 text-sm text-gray-500">Track and manage your laboratory inventory</p>
        </div>

        <!-- Tab Navigation - Modern Minimal -->
        <div class="mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button wire:click="setTab('stock_in')" 
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all <?php echo e($activeTab === 'stock_in' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                        <svg class="mr-2 h-5 w-5 <?php echo e($activeTab === 'stock_in' ? 'text-emerald-500' : 'text-gray-400 group-hover:text-gray-500'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                        Stock In
                    </button>
                    <button wire:click="setTab('stock_out')" 
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all <?php echo e($activeTab === 'stock_out' ? 'border-rose-500 text-rose-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                        <svg class="mr-2 h-5 w-5 <?php echo e($activeTab === 'stock_out' ? 'text-rose-500' : 'text-gray-400 group-hover:text-gray-500'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                        Stock Out
                    </button>
                    <button wire:click="setTab('stock_usage')" 
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all <?php echo e($activeTab === 'stock_usage' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                        <svg class="mr-2 h-5 w-5 <?php echo e($activeTab === 'stock_usage' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Stock Usage
                    </button>
                </nav>
            </div>
        </div>

        <!-- Forms Card -->
        <!-- Forms Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-8">
                <!-- Stock In Form -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'stock_in'): ?>
                <form wire:submit.prevent="addStock" class="space-y-6">
                    <!-- Multiple Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Items to Add</label>
                            <button type="button" wire:click="addStockInRow" class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-sm font-medium rounded-lg transition-all flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Item
                            </button>
                        </div>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $stock_in_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Item <span class="text-rose-500">*</span></label>
                                    <select wire:model.live="stock_in_items.<?php echo e($index); ?>.item_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                                        <option value="">Select Item</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->getAvailableItemsForStockIn($index, $items); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                            <option value="<?php echo e($itemOption->item_id); ?>"><?php echo e($itemOption->label); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_in_items.'.$index.'.item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Quantity <span class="text-rose-500">*</span></label>
                                    <input type="number" wire:model="stock_in_items.<?php echo e($index); ?>.quantity" min="1" placeholder="0" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_in_items.'.$index.'.quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Expiry Date</label>
                                    <div class="flex gap-2">
                                        <input type="date" wire:model="stock_in_items.<?php echo e($index); ?>.expiry_date" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($stock_in_items) > 1): ?>
                                        <button type="button" wire:click="removeStockInRow(<?php echo e($index); ?>)" class="px-3 py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_in_items.'.$index.'.expiry_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-200">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Supplier</label>
                            <input type="text" wire:model="stock_in_supplier" placeholder="Supplier name" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_in_supplier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Reference No.</label>
                            <input type="text" wire:model="stock_in_reference" placeholder="Invoice/PO number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_in_reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Remarks</label>
                            <input type="text" wire:model="stock_in_remarks" placeholder="Optional notes" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_in_remarks'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Stock
                        </button>
                    </div>
                </form>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Stock Out Form -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'stock_out'): ?>
                <form wire:submit.prevent="removeStock" class="space-y-6">
                    <!-- Multiple Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Items to Remove</label>
                            <button type="button" wire:click="addStockOutRow" class="px-3 py-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 text-sm font-medium rounded-lg transition-all flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Item
                            </button>
                        </div>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $stock_out_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Item <span class="text-rose-500">*</span></label>
                                    <select wire:model.live="stock_out_items.<?php echo e($index); ?>.item_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                                        <option value="">Select Item</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->getAvailableItemsForStockOut($index, $itemsWithStock); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                            <option value="<?php echo e($itemOption->item_id); ?>"><?php echo e($itemOption->label); ?> (Available: <?php echo e(number_format($itemOption->current_stock)); ?>)</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_out_items.'.$index.'.item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Quantity <span class="text-rose-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="number" wire:model="stock_out_items.<?php echo e($index); ?>.quantity" min="1" placeholder="0" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($stock_out_items) > 1): ?>
                                        <button type="button" wire:click="removeStockOutRow(<?php echo e($index); ?>)" class="px-3 py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_out_items.'.$index.'.quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-200">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Reference No.</label>
                            <input type="text" wire:model="stock_out_reference" placeholder="Requisition number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_out_reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Remarks</label>
                            <input type="text" wire:model="stock_out_remarks" placeholder="Reason for removal" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_out_remarks'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            Remove Stock
                        </button>
                    </div>
                </form>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Stock Usage Form -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'stock_usage'): ?>
                <form wire:submit.prevent="recordUsage" class="space-y-6">
                    <!-- Multiple Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Items to Record</label>
                            <button type="button" wire:click="addStockUsageRow" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-all flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Item
                            </button>
                        </div>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $stock_usage_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Item <span class="text-rose-500">*</span></label>
                                    <select wire:model.live="stock_usage_items.<?php echo e($index); ?>.item_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        <option value="">Select Item</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->getAvailableItemsForStockUsage($index, $itemsWithStock); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                            <option value="<?php echo e($itemOption->item_id); ?>"><?php echo e($itemOption->label); ?> (Available: <?php echo e(number_format($itemOption->current_stock)); ?>)</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_usage_items.'.$index.'.item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Quantity <span class="text-rose-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="number" wire:model="stock_usage_items.<?php echo e($index); ?>.quantity" min="1" placeholder="1" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($stock_usage_items) > 1): ?>
                                        <button type="button" wire:click="removeStockUsageRow(<?php echo e($index); ?>)" class="px-3 py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_usage_items.'.$index.'.quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-200">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Employee <span class="text-rose-500">*</span></label>
                            <select wire:model="stock_usage_employee_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Select Employee</option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                    <option value="<?php echo e($employee->employee_id); ?>"><?php echo e($employee->firstname); ?> <?php echo e($employee->lastname); ?></option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </select>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_usage_employee_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Purpose <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="stock_usage_purpose" placeholder="Purpose of use" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_usage_purpose'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">OR Number</label>
                            <input type="text" wire:model="stock_usage_or_number" placeholder="Official receipt number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stock_usage_or_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-rose-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Record Usage
                        </button>
                    </div>
                </form>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        </div>

        <!-- Current Inventory Levels -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Current Inventory Levels</h2>
                <p class="mt-1 text-sm text-gray-500">Track your stock levels in real-time</p>
            </div>
            
            <!-- Filters -->
            <div class="px-8 py-6 bg-gray-50 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Search</label>
                        <input type="text" wire:model.live="search" placeholder="Item name..." class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Section</label>
                        <select wire:model.live="filterSection" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="">All Sections</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                <option value="<?php echo e($section->section_id); ?>"><?php echo e($section->label); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Status</label>
                        <select wire:model.live="filterStatus" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="">All Status</option>
                            <option value="low">Low Stock</option>
                            <option value="in_stock">In Stock</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Per Page</label>
                        <select wire:model.live="perPage" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-8 py-4 bg-white">
                <p class="text-sm text-gray-600">Showing <?php echo e($inventory->count()); ?> of <?php echo e($inventory->total()); ?> items</p>
            </div>

            <!-- Inventory Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-y border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Item Name</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Section</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Unit</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Total In</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Total Out</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Current Stock</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Reorder Level</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $inventory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <?php
                                $currentStock = $item->total_in - $item->total_out - $item->total_usage;
                                $reorderLevel = $item->reorder_level ?? 0;
                                $isLowStock = $currentStock <= $reorderLevel;
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors <?php echo e($isLowStock ? 'bg-orange-50' : ''); ?>">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm text-gray-900"><?php echo e($item->label); ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700"><?php echo e($item->section->label ?? 'N/A'); ?></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm text-gray-700"><?php echo e($item->unit ?? 'pcs'); ?></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium">
                                        <?php echo e(number_format($item->total_in)); ?>

                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">
                                        <?php echo e(number_format($item->total_out + $item->total_usage)); ?>

                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-bold <?php echo e($isLowStock ? 'bg-orange-100 text-orange-700' : 'bg-blue-50 text-blue-700'); ?>">
                                        <?php echo e(number_format($currentStock)); ?>

                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm text-gray-600"><?php echo e(number_format($reorderLevel)); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isLowStock): ?>
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-700">
                                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            LOW STOCK
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            IN STOCK
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No items found</p>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-8 py-4 border-t border-gray-200 bg-gray-50">
                <?php echo e($inventory->links()); ?>

            </div>
        </div>

        <!-- Stock Movement History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Stock Movement History</h2>
                <p class="mt-1 text-sm text-gray-500">Recent stock transactions and activities</p>
            </div>

            <!-- Movement Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-y border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Item</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Performed By</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $movements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo e($movement->datetime_added->format('M d, Y')); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($movement->datetime_added->format('h:i A')); ?></div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($movement->type === 'IN'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-700 text-xs font-semibold">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                            </svg>
                                            STOCK IN
                                        </span>
                                    <?php elseif($movement->type === 'OUT'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-rose-100 text-rose-700 text-xs font-semibold">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                            </svg>
                                            STOCK OUT
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-100 text-blue-700 text-xs font-semibold">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            USAGE
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm text-gray-900"><?php echo e($movement->item->label ?? 'N/A'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($movement->item->section->label ?? ''); ?></div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold
                                        <?php echo e($movement->type === 'IN' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700'); ?>">
                                        <?php echo e($movement->type === 'IN' ? '+' : '-'); ?><?php echo e(number_format($movement->quantity)); ?>

                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700"><?php echo e($movement->reference ?? '—'); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($movement->type === 'USAGE' && $movement->employee): ?>
                                            <?php echo e($movement->employee->firstname); ?> <?php echo e($movement->employee->lastname); ?>

                                        <?php elseif($movement->performedByEmployee): ?>
                                            <?php echo e($movement->performedByEmployee->firstname); ?> <?php echo e($movement->performedByEmployee->lastname); ?>

                                        <?php else: ?>
                                            System
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600"><?php echo e(Str::limit($movement->remarks ?? '—', 40)); ?></span>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No stock movements recorded yet</p>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-8 py-4 border-t border-gray-200 bg-gray-50">
                <?php echo e($movements->links()); ?>

            </div>
        </div>
    </div>
</div><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\storage\framework/views/livewire/views/e0210cab.blade.php ENDPATH**/ ?>