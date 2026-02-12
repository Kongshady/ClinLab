<?php
use Livewire\Component;
use App\Models\Patient;
use App\Models\LabResult;
use App\Models\LabTestOrder;
use App\Models\Certificate;
use App\Models\CertificateIssue;
?>

<div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$patient): ?>
        
        <div class="max-w-lg mx-auto mt-16">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Profile Not Yet Linked</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Your account has not been linked to a patient record yet.<br>Please contact the laboratory staff to link your profile.</p>
            </div>
        </div>
    <?php else: ?>

    <div class="flex flex-col lg:flex-row gap-6">

        
        <div class="lg:w-80 flex-shrink-0 space-y-4">

            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-400 px-6 py-8 text-center relative">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvc3ZnPg==')] opacity-50"></div>
                    <div class="relative">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->avatar): ?>
                            <img src="<?php echo e(auth()->user()->avatar); ?>" alt="Avatar" class="w-20 h-20 rounded-full mx-auto ring-4 ring-white/30 shadow-lg mb-3">
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-2xl font-bold mx-auto ring-4 ring-white/30 mb-3">
                                <?php echo e(strtoupper(substr($patient->firstname, 0, 1) . substr($patient->lastname, 0, 1))); ?>

                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <h2 class="text-lg font-bold text-white"><?php echo e($patient->full_name); ?></h2>
                        <span class="inline-block mt-1.5 px-3 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-white/90 text-xs font-medium">
                            <?php echo e($patient->patient_type); ?> Patient
                        </span>
                    </div>
                </div>

                
                <div class="px-5 py-4 space-y-2.5 text-sm">
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="truncate"><?php echo e($patient->email ?? auth()->user()->email); ?></span>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($patient->birthdate): ?>
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span><?php echo e($patient->birthdate->format('M d, Y')); ?> &middot; <?php echo e($patient->birthdate->age); ?>y</span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span><?php echo e($patient->gender ?: 'Not set'); ?></span>
                    </div>
                </div>
            </div>

            
            <nav class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-2 space-y-1">
                    <button wire:click="switchTab('results')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                <?php echo e($activeTab === 'results' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'); ?>">
                        <svg class="w-5 h-5 <?php echo e($activeTab === 'results' ? 'text-blue-500' : 'text-gray-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Lab Results
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($labOrders) > 0): ?>
                            <span class="ml-auto text-xs px-2 py-0.5 rounded-full <?php echo e($activeTab === 'results' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'); ?>">
                                <?php echo e(count($labOrders)); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>

                    <button wire:click="switchTab('certificates')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                <?php echo e($activeTab === 'certificates' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'); ?>">
                        <svg class="w-5 h-5 <?php echo e($activeTab === 'certificates' ? 'text-blue-500' : 'text-gray-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        Certificates
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($certificates) > 0): ?>
                            <span class="ml-auto text-xs px-2 py-0.5 rounded-full <?php echo e($activeTab === 'certificates' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'); ?>">
                                <?php echo e(count($certificates)); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>

                    <button wire:click="switchTab('verify')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                <?php echo e($activeTab === 'verify' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'); ?>">
                        <svg class="w-5 h-5 <?php echo e($activeTab === 'verify' ? 'text-blue-500' : 'text-gray-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Verify Certificate
                    </button>

                    <button wire:click="switchTab('profile')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                <?php echo e($activeTab === 'profile' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'); ?>">
                        <svg class="w-5 h-5 <?php echo e($activeTab === 'profile' ? 'text-blue-500' : 'text-gray-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        My Profile
                    </button>
                </div>
            </nav>

            
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600"><?php echo e(count($labOrders)); ?></p>
                    <p class="text-xs text-gray-500 mt-0.5">Orders</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600"><?php echo e(collect($labOrders)->where('status', 'completed')->count()); ?></p>
                    <p class="text-xs text-gray-500 mt-0.5">Completed</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-violet-600"><?php echo e(count($certificates)); ?></p>
                    <p class="text-xs text-gray-500 mt-0.5">Certificates</p>
                </div>
            </div>
        </div>

        
        <div class="flex-1 min-w-0">

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'results'): ?>
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Lab Results</h2>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($labOrders) > 0): ?>
                    <div class="space-y-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $labOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <?php
                            $totalTests = $order->orderTests->count();
                            $completedTests = $order->orderTests->where('status', 'completed')->count();
                            $progressPct = $totalTests > 0 ? round($completedTests / $totalTests * 100) : 0;
                        ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            
                            <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-full 
                                            <?php if($order->status === 'completed'): ?> bg-emerald-500
                                            <?php elseif($order->status === 'cancelled'): ?> bg-red-500
                                            <?php else: ?> bg-amber-500
                                            <?php endif; ?>"></div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-800">Order #<?php echo e($order->lab_test_order_id); ?></h3>
                                            <p class="text-xs text-gray-500">
                                                <?php echo e($order->order_date ? $order->order_date->format('F d, Y h:i A') : 'N/A'); ?>

                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->physician): ?>
                                                    &middot; Dr. <?php echo e($order->physician->physician_name); ?>

                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-medium text-gray-600"><?php echo e($completedTests); ?>/<?php echo e($totalTests); ?></span>
                                            <div class="w-14 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full <?php echo e($progressPct >= 100 ? 'bg-emerald-500' : 'bg-blue-500'); ?>" 
                                                     style="width: <?php echo e($progressPct); ?>%"></div>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full 
                                            <?php if($order->status === 'completed'): ?> bg-emerald-100 text-emerald-700
                                            <?php elseif($order->status === 'cancelled'): ?> bg-red-100 text-red-700
                                            <?php else: ?> bg-amber-100 text-amber-700
                                            <?php endif; ?>">
                                            <?php echo e(ucfirst($order->status)); ?>

                                        </span>
                                        <button wire:click="viewOrder(<?php echo e($order->lab_test_order_id); ?>)"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Details
                                        </button>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead>
                                        <tr class="text-xs text-gray-500 uppercase">
                                            <th class="px-5 py-2.5 text-left font-medium">Test</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Result</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Normal Range</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $order->orderTests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $orderTest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <tr class="hover:bg-blue-50/40 transition-colors">
                                            <td class="px-5 py-3">
                                                <div class="text-sm font-medium text-gray-900"><?php echo e($orderTest->test->label ?? 'N/A'); ?></div>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->test && $orderTest->test->section): ?>
                                                    <div class="text-xs text-gray-400"><?php echo e($orderTest->test->section->label); ?></div>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                            <td class="px-5 py-3 text-sm text-gray-700">
                                                <?php echo e($orderTest->labResult->result_value ?? '—'); ?>

                                            </td>
                                            <td class="px-5 py-3 text-sm text-gray-500">
                                                <?php echo e($orderTest->labResult->normal_range ?? '—'); ?>

                                            </td>
                                            <td class="px-5 py-3">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orderTest->labResult): ?>
                                                    <?php
                                                        $sc = match($orderTest->labResult->status ?? 'draft') {
                                                            'final' => 'bg-emerald-100 text-emerald-700',
                                                            'revised' => 'bg-blue-100 text-blue-700',
                                                            default => 'bg-amber-100 text-amber-700',
                                                        };
                                                    ?>
                                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo e($sc); ?>"><?php echo e(ucfirst($orderTest->labResult->status)); ?></span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-500">Pending</span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <h3 class="text-gray-600 font-semibold mb-1">No Lab Results Yet</h3>
                        <p class="text-gray-400 text-sm">Your lab results will appear here once they are processed.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'certificates'): ?>
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">My Certificates</h2>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-5">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" wire:model.live.debounce.300ms="certSearch" placeholder="Search by certificate number..."
                                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            </div>
                        </div>
                        <select wire:model.live="certFilterType"
                                class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            <option value="">All Types</option>
                            <option value="calibration">Calibration</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="lab_result">Lab Result</option>
                            <option value="safety">Safety Compliance</option>
                        </select>
                        <select wire:model.live="certFilterStatus"
                                class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            <option value="">All Statuses</option>
                            <option value="issued">Issued</option>
                            <option value="revoked">Revoked</option>
                            <option value="expired">Expired</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($certificates) > 0): ?>
                    <div class="space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $certificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div wire:click="viewCertificate('<?php echo e($cert['source']); ?>', <?php echo e($cert['id']); ?>)"
                             class="bg-white rounded-2xl shadow-sm border border-gray-200 hover:border-blue-300 hover:shadow-md cursor-pointer transition-all overflow-hidden group">
                            <div class="flex items-center gap-4 p-5">
                                
                                <div class="flex-shrink-0">
                                    <?php
                                        $iconBg = match(strtolower($cert['type'])) {
                                            'calibration' => 'bg-violet-100',
                                            'maintenance' => 'bg-amber-100',
                                            'lab_result', 'lab result' => 'bg-emerald-100',
                                            'safety', 'safety compliance' => 'bg-red-100',
                                            default => 'bg-blue-100',
                                        };
                                        $iconColor = match(strtolower($cert['type'])) {
                                            'calibration' => 'text-violet-600',
                                            'maintenance' => 'text-amber-600',
                                            'lab_result', 'lab result' => 'text-emerald-600',
                                            'safety', 'safety compliance' => 'text-red-600',
                                            default => 'text-blue-600',
                                        };
                                    ?>
                                    <div class="w-12 h-12 rounded-xl <?php echo e($iconBg); ?> flex items-center justify-center">
                                        <svg class="w-6 h-6 <?php echo e($iconColor); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                        </svg>
                                    </div>
                                </div>

                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-gray-900 truncate"><?php echo e($cert['number']); ?></h3>
                                        <?php
                                            $sBg = match(strtolower($cert['status'])) {
                                                'issued' => 'bg-emerald-100 text-emerald-700',
                                                'revoked' => 'bg-red-100 text-red-700',
                                                'expired' => 'bg-gray-100 text-gray-600',
                                                'draft' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-blue-100 text-blue-700',
                                            };
                                        ?>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo e($sBg); ?>"><?php echo e(ucfirst($cert['status'])); ?></span>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            <?php echo e($cert['type']); ?>

                                        </span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cert['issue_date']): ?>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <?php echo e($cert['issue_date']); ?>

                                        </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cert['equipment']): ?>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                            <?php echo e($cert['equipment']); ?>

                                        </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>

                                
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cert['valid_until']): ?>
                            <div class="px-5 py-2 bg-gray-50 border-t border-gray-100 text-xs text-gray-500">
                                <span class="font-medium">Valid Until:</span> <?php echo e($cert['valid_until']); ?>

                            </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-gray-600 font-semibold mb-1">No Certificates Found</h3>
                        <p class="text-gray-400 text-sm">Your certificates will appear here once they are issued by the laboratory.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'verify'): ?>
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Verify Certificate</h2>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <div class="max-w-lg mx-auto text-center">
                            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Certificate Verification</h3>
                            <p class="text-sm text-gray-500 mb-6">Enter a certificate number or verification code to check its authenticity and validity.</p>

                            <div class="flex gap-3">
                                <div class="flex-1 relative">
                                    <input type="text" wire:model="verifyCode" wire:keydown.enter="verifyCertificate"
                                           placeholder="e.g. CERT-2026-00001 or verification code"
                                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                </div>
                                <button wire:click="verifyCertificate"
                                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-medium text-sm rounded-xl shadow-sm shadow-blue-500/25 transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    Verify
                                </button>
                            </div>
                        </div>
                    </div>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($verifyResult): ?>
                    <div class="border-t border-gray-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($verifyResult['status'] === 'found'): ?>
                            <div class="p-6">
                                <div class="max-w-lg mx-auto">
                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($verifyResult['valid']): ?>
                                    <div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-emerald-50 border border-emerald-200">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-emerald-800">Certificate is Valid</p>
                                            <p class="text-xs text-emerald-600">This certificate has been verified and is currently active.</p>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-red-50 border border-red-200">
                                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-red-800">Certificate is Invalid</p>
                                            <p class="text-xs text-red-600">This certificate is <?php echo e(strtolower($verifyResult['cert_status'])); ?> or has expired.</p>
                                        </div>
                                    </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    
                                    <div class="bg-gray-50 rounded-xl overflow-hidden">
                                        <div class="divide-y divide-gray-200/60">
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Certificate No.</span>
                                                <span class="text-sm font-semibold text-gray-900 font-mono"><?php echo e($verifyResult['number']); ?></span>
                                            </div>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Type</span>
                                                <span class="text-sm font-medium text-gray-900"><?php echo e($verifyResult['type']); ?></span>
                                            </div>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Status</span>
                                                <?php
                                                    $vsBg = match(strtolower($verifyResult['cert_status'])) {
                                                        'issued' => 'bg-emerald-100 text-emerald-700',
                                                        'revoked' => 'bg-red-100 text-red-700',
                                                        'expired' => 'bg-gray-100 text-gray-600',
                                                        default => 'bg-amber-100 text-amber-700',
                                                    };
                                                ?>
                                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full <?php echo e($vsBg); ?>"><?php echo e(ucfirst($verifyResult['cert_status'])); ?></span>
                                            </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($verifyResult['issue_date']): ?>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Issue Date</span>
                                                <span class="text-sm font-medium text-gray-900"><?php echo e($verifyResult['issue_date']); ?></span>
                                            </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($verifyResult['valid_until']): ?>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Valid Until</span>
                                                <span class="text-sm font-medium text-gray-900"><?php echo e($verifyResult['valid_until']); ?></span>
                                            </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($verifyResult['patient']): ?>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Patient</span>
                                                <span class="text-sm font-medium text-gray-900"><?php echo e($verifyResult['patient']); ?></span>
                                            </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($verifyResult['issued_by']): ?>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Issued By</span>
                                                <span class="text-sm font-medium text-gray-900"><?php echo e($verifyResult['issued_by']); ?></span>
                                            </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <button wire:click="clearVerification" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Verify Another Certificate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php elseif($verifyResult['status'] === 'not_found'): ?>
                            <div class="p-6 text-center">
                                <div class="max-w-lg mx-auto">
                                    <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="font-semibold text-amber-800">Certificate Not Found</p>
                                            <p class="text-xs text-amber-600"><?php echo e($verifyResult['message']); ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button wire:click="clearVerification" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Try Again
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php elseif($verifyResult['status'] === 'error'): ?>
                            <div class="p-6 text-center">
                                <div class="max-w-lg mx-auto">
                                    <div class="flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200">
                                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <p class="text-sm text-red-700 text-left"><?php echo e($verifyResult['message']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'profile'): ?>
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">My Profile</h2>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$editMode): ?>
                        <button wire:click="startEdit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Profile
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('profile_saved')): ?>
                    <div class="mb-5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <?php echo e(session('profile_saved')); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$editMode): ?>
                        
                        <div class="divide-y divide-gray-100">
                            <?php
                                $fields = [
                                    ['label' => 'First Name', 'value' => $patient->firstname, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Middle Name', 'value' => $patient->middlename ?: '—', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Last Name', 'value' => $patient->lastname, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Gender', 'value' => $patient->gender ?: '—', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                                    ['label' => 'Date of Birth', 'value' => $patient->birthdate ? $patient->birthdate->format('F d, Y') . ' (' . $patient->birthdate->age . ' years old)' : '—', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                    ['label' => 'Email', 'value' => $patient->email ?? auth()->user()->email, 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                    ['label' => 'Contact Number', 'value' => $patient->contact_number ?: '—', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                    ['label' => 'Address', 'value' => $patient->address ?: '—', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                                    ['label' => 'Patient Type', 'value' => $patient->patient_type, 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                                ];
                            ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <div class="flex items-center px-6 py-4">
                                <div class="flex items-center gap-3 w-44 flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($field['icon']); ?>"/></svg>
                                    <span class="text-sm text-gray-500"><?php echo e($field['label']); ?></span>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo e($field['value']); ?></span>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    <?php else: ?>
                        
                        <form wire:submit.prevent="saveProfile" class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name <span class="text-red-400">*</span></label>
                                    <input type="text" wire:model="editFirstname"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editFirstname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Middle Name</label>
                                    <input type="text" wire:model="editMiddlename"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editMiddlename'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name <span class="text-red-400">*</span></label>
                                    <input type="text" wire:model="editLastname"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editLastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender <span class="text-red-400">*</span></label>
                                    <select wire:model="editGender"
                                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editGender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Birth <span class="text-red-400">*</span></label>
                                    <input type="date" wire:model="editBirthdate"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editBirthdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Number</label>
                                    <input type="text" wire:model="editContact" placeholder="09xx-xxx-xxxx"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editContact'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                                <textarea wire:model="editAddress" rows="2" placeholder="Enter your complete address"
                                          class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all resize-none"></textarea>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editAddress'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                                <button type="button" wire:click="cancelEdit"
                                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedResult): ?>
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeResult">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Result Details</h3>
                <button wire:click="closeResult" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Test</p>
                        <p class="text-sm font-semibold text-gray-900"><?php echo e($selectedResult->test->label ?? 'N/A'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Status</p>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700"><?php echo e(ucfirst($selectedResult->status)); ?></span>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Result Value</p>
                        <p class="text-sm font-semibold text-gray-900"><?php echo e($selectedResult->result_value ?? 'N/A'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Normal Range</p>
                        <p class="text-sm font-semibold text-gray-900"><?php echo e($selectedResult->normal_range ?? 'N/A'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Findings</p>
                        <p class="text-sm text-gray-900"><?php echo e($selectedResult->findings ?: 'No findings recorded.'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Remarks</p>
                        <p class="text-sm text-gray-900"><?php echo e($selectedResult->remarks ?: 'No remarks.'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Result Date</p>
                        <p class="text-sm text-gray-900"><?php echo e($selectedResult->result_date ? $selectedResult->result_date->format('M d, Y') : 'N/A'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Performed By</p>
                        <p class="text-sm text-gray-900"><?php echo e($selectedResult->performedBy ? $selectedResult->performedBy->firstname . ' ' . $selectedResult->performedBy->lastname : 'N/A'); ?></p>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button wire:click="closeResult" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedOrder): ?>
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeOrder">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">Order #<?php echo e($selectedOrder->lab_test_order_id); ?></h3>
                        <p class="text-blue-100 text-sm mt-0.5"><?php echo e($selectedOrder->order_date->format('F d, Y - h:i A')); ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1.5 text-xs font-bold rounded-full
                            <?php echo e($selectedOrder->status === 'completed' ? 'bg-green-400/20 text-green-100' : ($selectedOrder->status === 'cancelled' ? 'bg-red-400/20 text-red-100' : 'bg-yellow-400/20 text-yellow-100')); ?>">
                            <?php echo e(ucfirst($selectedOrder->status)); ?>

                        </span>
                        <button wire:click="closeOrder" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            
            <div class="p-6 overflow-y-auto space-y-5">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Physician</p>
                        <p class="text-sm font-semibold text-gray-900"><?php echo e($selectedOrder->physician->physician_name ?? 'Not specified'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Progress</p>
                        <div class="flex items-center gap-2 mt-1">
                            <?php
                                $completed = $selectedOrder->orderTests->where('status', 'completed')->count();
                                $total = $selectedOrder->orderTests->count();
                                $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
                            ?>
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full <?php echo e($pct === 100 ? 'bg-green-500' : 'bg-blue-500'); ?>" style="width: <?php echo e($pct); ?>%"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-600"><?php echo e($completed); ?>/<?php echo e($total); ?></span>
                        </div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedOrder->remarks): ?>
                    <div class="bg-gray-50 p-3.5 rounded-xl col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Remarks</p>
                        <p class="text-sm text-gray-900"><?php echo e($selectedOrder->remarks); ?></p>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div>
                    <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Ordered Tests
                    </h4>
                    <div class="space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $selectedOrder->orderTests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0
                                        <?php echo e($ot->status === 'completed' ? 'bg-green-500' : ($ot->status === 'in_progress' ? 'bg-blue-500' : ($ot->status === 'cancelled' ? 'bg-red-400' : 'bg-amber-400'))); ?>"></div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900"><?php echo e($ot->test->label ?? 'Unknown Test'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo e($ot->test->section->label ?? ''); ?></p>
                                    </div>
                                </div>
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full
                                    <?php echo e($ot->status === 'completed' ? 'bg-green-100 text-green-700' : ($ot->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : ($ot->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'))); ?>">
                                    <?php echo e(ucfirst(str_replace('_', ' ', $ot->status))); ?>

                                </span>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ot->labResult): ?>
                            <div class="px-4 py-3 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Result</span>
                                    <p class="font-semibold text-gray-900 mt-0.5"><?php echo e($ot->labResult->result_value ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Normal Range</span>
                                    <p class="font-medium text-gray-700 mt-0.5"><?php echo e($ot->labResult->normal_range ?? 'N/A'); ?></p>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ot->labResult->findings): ?>
                                <div class="col-span-2">
                                    <span class="text-xs text-gray-500 uppercase">Findings</span>
                                    <p class="text-gray-700 mt-0.5"><?php echo e($ot->labResult->findings); ?></p>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ot->labResult->remarks): ?>
                                <div class="col-span-2">
                                    <span class="text-xs text-gray-500 uppercase">Remarks</span>
                                    <p class="text-gray-700 mt-0.5"><?php echo e($ot->labResult->remarks); ?></p>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Result Date</span>
                                    <p class="text-gray-700 mt-0.5"><?php echo e($ot->labResult->result_date ? \Carbon\Carbon::parse($ot->labResult->result_date)->format('M d, Y') : 'N/A'); ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Status</span>
                                    <p class="mt-0.5">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                            <?php echo e($ot->labResult->status === 'final' ? 'bg-green-100 text-green-700' : ($ot->labResult->status === 'revised' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700')); ?>">
                                            <?php echo e(ucfirst($ot->labResult->status)); ?>

                                        </span>
                                    </p>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ot->labResult->performedBy): ?>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Performed By</span>
                                    <p class="text-gray-700 mt-0.5"><?php echo e($ot->labResult->performedBy->firstname . ' ' . $ot->labResult->performedBy->lastname); ?></p>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ot->labResult->verifiedBy): ?>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Verified By</span>
                                    <p class="text-gray-700 mt-0.5"><?php echo e($ot->labResult->verifiedBy->firstname . ' ' . $ot->labResult->verifiedBy->lastname); ?></p>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="px-4 py-3 text-center">
                                <p class="text-sm text-gray-400 italic">Results pending</p>
                            </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                <button wire:click="closeOrder" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate): ?>
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeCertificate">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
            
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-500 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Certificate Details</h3>
                        <p class="text-sm text-white/80 font-mono"><?php echo e($selectedCertificate['number']); ?></p>
                    </div>
                </div>
                <button wire:click="closeCertificate" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            
            <div class="p-6 space-y-5 max-h-[60vh] overflow-y-auto">
                
                <div class="flex items-center justify-center">
                    <?php
                        $msBg = match(strtolower($selectedCertificate['status'])) {
                            'issued' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
                            'revoked' => 'bg-red-50 border-red-200 text-red-700',
                            'expired' => 'bg-gray-50 border-gray-200 text-gray-600',
                            default => 'bg-amber-50 border-amber-200 text-amber-700',
                        };
                        $msIcon = match(strtolower($selectedCertificate['status'])) {
                            'issued' => 'M5 13l4 4L19 7',
                            'revoked' => 'M6 18L18 6M6 6l12 12',
                            default => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        };
                    ?>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border <?php echo e($msBg); ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($msIcon); ?>"/></svg>
                        <?php echo e(ucfirst($selectedCertificate['status'])); ?>

                    </span>
                </div>

                
                <div class="bg-gray-50 rounded-xl overflow-hidden divide-y divide-gray-200/60">
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Certificate Number</span>
                        <span class="text-sm font-bold text-gray-900 font-mono"><?php echo e($selectedCertificate['number']); ?></span>
                    </div>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Type</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($selectedCertificate['type']); ?></span>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['patient_name']): ?>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Patient</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($selectedCertificate['patient_name']); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['equipment']): ?>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Equipment</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($selectedCertificate['equipment']); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['issue_date']): ?>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Issue Date</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($selectedCertificate['issue_date']); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['valid_until']): ?>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Valid Until</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($selectedCertificate['valid_until']); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['issued_by']): ?>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Issued By</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($selectedCertificate['issued_by']); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['verified_by']): ?>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Verified By</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($selectedCertificate['verified_by']); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['verification_code']): ?>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Verification Code</span>
                        <span class="text-sm font-mono font-medium text-blue-700 bg-blue-50 px-2 py-0.5 rounded"><?php echo e($selectedCertificate['verification_code']); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['verification_code'] || $selectedCertificate['number']): ?>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-3">Scan to verify this certificate</p>
                    <div class="inline-block bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <div id="cert-qr-<?php echo e($selectedCertificate['id']); ?>" class="w-32 h-32 flex items-center justify-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=128x128&data=<?php echo e(urlencode(url('/certificates/verify?code=' . ($selectedCertificate['verification_code'] ?: $selectedCertificate['number'])))); ?>"
                                 alt="QR Code" class="w-32 h-32" loading="lazy">
                        </div>
                    </div>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($selectedCertificate['certificate_data'])): ?>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Additional Information</h4>
                    <div class="bg-gray-50 rounded-xl overflow-hidden divide-y divide-gray-200/60">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $selectedCertificate['certificate_data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500"><?php echo e(ucwords(str_replace('_', ' ', $key))); ?></span>
                            <span class="text-sm font-medium text-gray-900"><?php echo e(is_array($value) ? json_encode($value) : $value); ?></span>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <button wire:click="closeCertificate" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCertificate['pdf_path'] || $selectedCertificate['source'] === 'certificate'): ?>
                    <a href="<?php echo e(route('patient.certificate.download', ['source' => $selectedCertificate['source'], 'id' => $selectedCertificate['id']])); ?>"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\storage\framework/views/livewire/views/f38fb5ab.blade.php ENDPATH**/ ?>