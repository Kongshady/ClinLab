<?php
use App\Models\Transaction;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Item;
use Illuminate\Support\Facades\Cache;

// Cache dashboard statistics for 5 minutes
$stats = Cache::remember('secretary_dashboard_stats', 300, function() {
    $transactionsToday = Transaction::whereDate('datetime_added', today())
        ->count();
    
    $stockInToday = StockIn::whereDate('datetime_added', today())
        ->count();
    
    $stockOutToday = StockOut::whereDate('datetime_added', today())
        ->count();
    
    $lowStockCount = Item::where('is_deleted', 0)
        ->count();
    
    return [
        'transactions_today' => $transactionsToday,
        'stock_in_today' => $stockInToday,
        'stock_out_today' => $stockOutToday,
        'low_stock_count' => $lowStockCount,
    ];
});

$lowStockItems = Item::where('is_deleted', 0)
    ->orderBy('label', 'asc')
    ->limit(10)
    ->get();
?>



<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Secretary Dashboard</h1>
            <p class="text-lg text-gray-600">
                <span class="inline-flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Welcome, <?php echo e(auth()->user()->name); ?>

                </span>
            </p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Transactions Today -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Transactions Today</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo e($stats['transactions_today']); ?></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Stock In Today -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Stock In Today</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo e($stats['stock_in_today']); ?></p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Stock Out Today -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Stock Out Today</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo e($stats['stock_out_today']); ?></p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Items</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo e($stats['low_stock_count']); ?></p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.732-1.333-2.5 0L4.268 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Inventory Management</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('transactions.access')): ?>
                <a href="<?php echo e(route('transactions.index')); ?>" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Transactions</span>
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('stockin.access')): ?>
                <a href="<?php echo e(route('stockin.index')); ?>" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Stock In</span>
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('stockout.access')): ?>
                <a href="<?php echo e(route('stockout.index')); ?>" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <svg class="w-8 h-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Stock Out</span>
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('items.access')): ?>
                <a href="<?php echo e(route('items.index')); ?>" class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                    <svg class="w-8 h-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">All Items</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Recent Items</h2>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('items.access')): ?>
                <a href="<?php echo e(route('items.index')); ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">View All →</a>
                <?php endif; ?>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Item Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Unit</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Reorder Level</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $lowStockItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo e($item->label); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo e($item->unit); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo e($item->reorder_level); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                        </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">All items are well stocked</td>
                        </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\resources\views/dashboards/secretary.blade.php ENDPATH**/ ?>