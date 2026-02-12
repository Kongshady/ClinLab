

<?php $__env->startSection('content'); ?>
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
        <p class="text-gray-600 mt-1">View and generate laboratory reports</p>
    </div>

    <!-- Report Generation Form -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Generate Report
            </h2>
        </div>
        <div class="p-6">
            <form method="GET" action="<?php echo e(route('reports.index')); ?>">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Report Type <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="report_type" 
                            id="report_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Select Report Type</option>
                            <option value="equipment_maintenance" <?php echo e(request('report_type') == 'equipment_maintenance' ? 'selected' : ''); ?>>Equipment Maintenance</option>
                            <option value="calibration_records" <?php echo e(request('report_type') == 'calibration_records' ? 'selected' : ''); ?>>Calibration Records</option>
                            <option value="inventory_movement" <?php echo e(request('report_type') == 'inventory_movement' ? 'selected' : ''); ?>>Inventory Movement</option>
                            <option value="low_stock_alert" <?php echo e(request('report_type') == 'low_stock_alert' ? 'selected' : ''); ?>>Low Stock Alert</option>
                            <option value="laboratory_results" <?php echo e(request('report_type') == 'laboratory_results' ? 'selected' : ''); ?>>Laboratory Results</option>
                        </select>
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="start_date" 
                            id="start_date"
                            value="<?php echo e(request('start_date', now()->startOfMonth()->format('Y-m-d'))); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="end_date" 
                            id="end_date"
                            value="<?php echo e(request('end_date', now()->format('Y-m-d'))); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label for="section_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Section (Optional)
                        </label>
                        <select 
                            name="section_id" 
                            id="section_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Sections</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sections ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                <option value="<?php echo e($section->section_id); ?>" <?php echo e(request('section_id') == $section->section_id ? 'selected' : ''); ?>>
                                    <?php echo e($section->label); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Generate Report
                    </button>
                    <button 
                        type="button"
                        onclick="exportReport()"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Display Section -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request('report_type') && isset($reportData) && $reportData->count() > 0): ?>
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch(request('report_type')):
                        case ('equipment_maintenance'): ?>
                            Equipment Maintenance Report
                            <?php break; ?>
                        <?php case ('calibration_records'): ?>
                            Calibration Records Report
                            <?php break; ?>
                        <?php case ('inventory_movement'): ?>
                            Inventory Movement Report
                            <?php break; ?>
                        <?php case ('low_stock_alert'): ?>
                            Low Stock Alert Report
                            <?php break; ?>
                        <?php case ('laboratory_results'): ?>
                            Laboratory Results Report
                            <?php break; ?>
                    <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </h2>
                <span class="text-sm text-gray-500"><?php echo e($reportData->total()); ?> records found</span>
            </div>
            
            <div class="overflow-x-auto">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch(request('report_type')):
                    case ('equipment_maintenance'): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Serial Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $equipment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($equipment->name); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($equipment->model ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($equipment->serial_no ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($equipment->section->label ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                <?php if($equipment->status === 'active'): ?> bg-green-100 text-green-800
                                                <?php elseif($equipment->status === 'maintenance'): ?> bg-yellow-100 text-yellow-800
                                                <?php else: ?> bg-red-100 text-red-800
                                                <?php endif; ?>">
                                                <?php echo e(ucfirst($equipment->status ?? 'active')); ?>

                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo e($equipment->purchase_date ? \Carbon\Carbon::parse($equipment->purchase_date)->format('M d, Y') : 'N/A'); ?>

                                        </td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No equipment found</td></tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                        <?php break; ?>
                        
                    <?php case ('inventory_movement'): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo e($movement->datetime_added ? \Carbon\Carbon::parse($movement->datetime_added)->format('M d, Y') : 'N/A'); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($movement->item->label ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                Stock In
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($movement->quantity ?? 0); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($movement->supplier ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($movement->reference_number ?? 'N/A'); ?></td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No inventory movements found</td></tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                        <?php break; ?>
                        
                    <?php case ('low_stock_alert'): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($item->label); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold"><?php echo e($item->current_stock ?? 0); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($item->reorder_level ?? 0); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($item->unit ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($item->section->label ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                Low Stock
                                            </span>
                                        </td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">All items are sufficiently stocked</td></tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                        <?php break; ?>
                        
                    <?php case ('calibration_records'): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Calibration Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Next Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Performed By</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Certificate No.</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $calibration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($calibration->equipment->name ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo e($calibration->calibration_date ? \Carbon\Carbon::parse($calibration->calibration_date)->format('M d, Y') : 'N/A'); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo e($calibration->next_due_date ? \Carbon\Carbon::parse($calibration->next_due_date)->format('M d, Y') : 'N/A'); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                <?php if($calibration->status === 'completed'): ?> bg-green-100 text-green-800
                                                <?php elseif($calibration->status === 'due'): ?> bg-yellow-100 text-yellow-800
                                                <?php else: ?> bg-red-100 text-red-800
                                                <?php endif; ?>">
                                                <?php echo e(ucfirst($calibration->status ?? 'pending')); ?>

                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($calibration->performed_by ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($calibration->certificate_number ?? 'N/A'); ?></td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No calibration records found</td></tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                        <?php break; ?>
                        
                    <?php case ('laboratory_results'): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Result Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Normal Range</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo e($result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('M d, Y') : 'N/A'); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($result->patient->full_name ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($result->test->label ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($result->result_value ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($result->normal_range ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                <?php if($result->status === 'final'): ?> bg-green-100 text-green-800
                                                <?php elseif($result->status === 'revised'): ?> bg-blue-100 text-blue-800
                                                <?php else: ?> bg-yellow-100 text-yellow-800
                                                <?php endif; ?>">
                                                <?php echo e(ucfirst($result->status ?? 'draft')); ?>

                                            </span>
                                        </td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No lab results found</td></tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                        <?php break; ?>
                        
                    <?php default: ?>
                        <div class="p-12 text-center">
                            <p class="text-gray-500">Report type not implemented yet.</p>
                        </div>
                <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($reportData) && $reportData->hasPages()): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <?php echo e($reportData->appends(request()->input())->links()); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php elseif(request('report_type') && isset($reportData) && $reportData->count() === 0): ?>
        <div class="bg-white rounded-lg shadow border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 font-medium mb-2">No data found for the selected criteria</p>
            <p class="text-sm text-gray-400">Try adjusting the date range or removing section filter</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-600 font-medium mb-2">Ready to Generate Reports</p>
            <p class="text-sm text-gray-500">Select a report type, date range, and click "Generate Report" to begin</p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<script>
function exportReport() {
    const reportType = document.getElementById('report_type').value;
    if (!reportType) {
        alert('Please select a report type first.');
        return;
    }
    
    // Create a form for export
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '<?php echo e(route("reports.index")); ?>';
    
    // Add all current parameters
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'true');
    
    params.forEach((value, key) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\resources\views/reports/index.blade.php ENDPATH**/ ?>