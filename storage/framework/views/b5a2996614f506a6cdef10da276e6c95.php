<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lab Result - Order #<?php echo e($order->lab_test_order_id); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .page { padding: 30px 40px; }

        /* Header */
        .header { text-align: center; border-bottom: 3px solid #d1324a; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #d1324a; letter-spacing: 1px; margin-bottom: 2px; }
        .header h2 { font-size: 13px; color: #444; font-weight: normal; }
        .header .subtitle { font-size: 10px; color: #777; margin-top: 4px; }

        /* Info Grid */
        .info-grid { display: table; width: 100%; margin-bottom: 18px; }
        .info-row { display: table-row; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; padding: 0 5px; }
        .info-box { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 12px; margin-bottom: 8px; }
        .info-label { font-size: 9px; text-transform: uppercase; color: #888; font-weight: bold; letter-spacing: 0.5px; }
        .info-value { font-size: 11px; color: #222; font-weight: bold; margin-top: 2px; }

        /* Status Badge */
        .status { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Table */
        .results-title { font-size: 13px; font-weight: bold; color: #d1324a; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        thead th { background: #d1324a; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 10px; text-align: left; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #eee; font-size: 10px; }
        tbody tr:nth-child(even) { background: #fafafa; }

        /* Flag */
        .flag { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; }
        .flag-high { background: #f8d7da; color: #721c24; }
        .flag-low { background: #cce5ff; color: #004085; }
        .flag-normal { background: #d4edda; color: #155724; }

        /* Remarks */
        .remarks-box { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 4px; padding: 10px 12px; margin-bottom: 18px; }
        .remarks-box .label { font-size: 9px; text-transform: uppercase; color: #92400e; font-weight: bold; }
        .remarks-box .text { font-size: 10px; color: #78350f; margin-top: 3px; }

        /* Signatures */
        .signatures { display: table; width: 100%; margin-top: 40px; }
        .sig-col { display: table-cell; width: 50%; text-align: center; padding: 0 20px; }
        .sig-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        .sig-name { font-size: 11px; font-weight: bold; color: #222; }
        .sig-title { font-size: 9px; color: #888; }

        /* Footer */
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #999; }
        .footer .generated { font-style: italic; }
    </style>
</head>
<body>
    <div class="page">
        
        <div class="header">
            <h1>UNIVERSITY IMMACULATE CONCEPTION</h1>
            <h2>Clinical Laboratory</h2>
            <div class="subtitle">Laboratory Test Result Report</div>
        </div>

        
        <table style="margin-bottom: 15px;">
            <tr>
                <td style="border: none; padding: 3px 5px; width: 50%;">
                    <div class="info-box">
                        <div class="info-label">Patient Name</div>
                        <div class="info-value"><?php echo e($order->patient->full_name ?? 'N/A'); ?></div>
                    </div>
                </td>
                <td style="border: none; padding: 3px 5px; width: 50%;">
                    <div class="info-box">
                        <div class="info-label">Order Number</div>
                        <div class="info-value">#<?php echo e($order->lab_test_order_id); ?></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 5px;">
                    <div class="info-box">
                        <div class="info-label">Physician</div>
                        <div class="info-value"><?php echo e($order->physician->physician_name ?? 'Not specified'); ?></div>
                    </div>
                </td>
                <td style="border: none; padding: 3px 5px;">
                    <div class="info-box">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?php echo e($order->order_date ? $order->order_date->format('F d, Y - h:i A') : 'N/A'); ?></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 5px;">
                    <div class="info-box">
                        <div class="info-label">Gender / Birthdate</div>
                        <div class="info-value">
                            <?php echo e($order->patient->gender ?? '—'); ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->patient->birthdate): ?>
                                / <?php echo e($order->patient->birthdate->format('M d, Y')); ?>

                                (<?php echo e($order->patient->birthdate->age); ?> yrs)
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="border: none; padding: 3px 5px;">
                    <div class="info-box">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status status-<?php echo e($order->status); ?>"><?php echo e(ucfirst($order->status)); ?></span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        
        <div class="results-title">Test Results</div>
        <table>
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>Section</th>
                    <th>Result</th>
                    <th>Reference Range</th>
                    <th>Flag</th>
                    <th>Remarks</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $order->orderTests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                <?php
                    $result = $ot->labResult;
                    $flag = null;
                    $flagClass = '';
                    if ($result && $result->result_value && $result->normal_range) {
                        $val = floatval($result->result_value);
                        $range = $result->normal_range;
                        if (preg_match('/^(\d+\.?\d*)\s*[-–]\s*(\d+\.?\d*)/', $range, $m)) {
                            $low = floatval($m[1]);
                            $high = floatval($m[2]);
                            if (is_numeric($result->result_value)) {
                                if ($val < $low) { $flag = 'Low'; $flagClass = 'flag-low'; }
                                elseif ($val > $high) { $flag = 'High'; $flagClass = 'flag-high'; }
                                else { $flag = 'Normal'; $flagClass = 'flag-normal'; }
                            }
                        }
                    }
                    if (!$flag && $result) {
                        $text = strtolower(($result->findings ?? '') . ' ' . ($result->remarks ?? ''));
                        if (str_contains($text, 'high') || str_contains($text, 'elevated')) { $flag = 'High'; $flagClass = 'flag-high'; }
                        elseif (str_contains($text, 'low') || str_contains($text, 'decreased')) { $flag = 'Low'; $flagClass = 'flag-low'; }
                        elseif (str_contains($text, 'normal') || str_contains($text, 'within')) { $flag = 'Normal'; $flagClass = 'flag-normal'; }
                    }
                ?>
                <tr>
                    <td style="font-weight: bold;"><?php echo e($ot->test->label ?? 'Unknown'); ?></td>
                    <td><?php echo e($ot->test->section->label ?? '—'); ?></td>
                    <td style="font-weight: bold;"><?php echo e($result->result_value ?? '—'); ?></td>
                    <td><?php echo e($result->normal_range ?? '—'); ?></td>
                    <td>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flag): ?>
                            <span class="flag <?php echo e($flagClass); ?>"><?php echo e($flag); ?></span>
                        <?php else: ?>
                            —
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td><?php echo e($result->remarks ?? ($result->findings ?? '—')); ?></td>
                    <td>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($result): ?>
                            <?php echo e(ucfirst($result->status)); ?>

                        <?php else: ?>
                            Pending
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </tbody>
        </table>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->remarks): ?>
        <div class="remarks-box">
            <div class="label">Order Remarks</div>
            <div class="text"><?php echo e($order->remarks); ?></div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <div class="signatures">
            <table style="margin: 0;">
                <tr>
                    <td style="border: none; width: 50%; text-align: center; padding: 0 20px;">
                        <div class="sig-line">
                            <div class="sig-name">
                                <?php
                                    $performers = $order->orderTests
                                        ->filter(fn($ot) => $ot->labResult && $ot->labResult->performedBy)
                                        ->map(fn($ot) => $ot->labResult->performedBy->firstname . ' ' . $ot->labResult->performedBy->lastname)
                                        ->unique()->implode(', ');
                                ?>
                                <?php echo e($performers ?: '________________________'); ?>

                            </div>
                            <div class="sig-title">Medical Technologist</div>
                        </div>
                    </td>
                    <td style="border: none; width: 50%; text-align: center; padding: 0 20px;">
                        <div class="sig-line">
                            <div class="sig-name">
                                <?php
                                    $verifiers = $order->orderTests
                                        ->filter(fn($ot) => $ot->labResult && $ot->labResult->verifiedBy)
                                        ->map(fn($ot) => $ot->labResult->verifiedBy->firstname . ' ' . $ot->labResult->verifiedBy->lastname)
                                        ->unique()->implode(', ');
                                ?>
                                <?php echo e($verifiers ?: '________________________'); ?>

                            </div>
                            <div class="sig-title">Pathologist</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        
        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p class="generated">Generated on <?php echo e(now()->format('F d, Y h:i A')); ?></p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\resources\views/pdf/lab-result.blade.php ENDPATH**/ ?>