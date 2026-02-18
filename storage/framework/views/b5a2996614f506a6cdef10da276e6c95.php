<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lab Result - Order #<?php echo e($order->lab_test_order_id); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; line-height: 1.6; }
        .page { padding: 36px 48px; }

        /* ── Letterhead ── */
        .letterhead { display: table; width: 100%; margin-bottom: 0; }
        .letterhead-logo { display: table-cell; width: 72px; vertical-align: middle; }
        .letterhead-logo img { width: 68px; height: 68px; }
        .letterhead-text { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .letterhead-text .institution { font-size: 15px; font-weight: bold; color: #d1324a; letter-spacing: 0.5px; }
        .letterhead-text .department { font-size: 11px; color: #444; margin-top: 2px; }
        .letterhead-text .address { font-size: 9px; color: #888; margin-top: 2px; }
        .letterhead-right { display: table-cell; vertical-align: middle; text-align: right; width: 160px; }
        .doc-type { font-size: 10px; font-weight: bold; color: #d1324a; text-transform: uppercase; letter-spacing: 1px; }
        .doc-no { font-size: 12px; font-weight: bold; color: #222; margin-top: 3px; }
        .doc-date { font-size: 9px; color: #888; margin-top: 2px; }

        /* Top accent line */
        .rule-top { border: none; border-top: 3px solid #d1324a; margin: 10px 0 4px 0; }
        .rule-thin { border: none; border-top: 1px solid #e0e0e0; margin: 4px 0 16px 0; }

        /* ── Patient Info strip ── */
        .patient-strip { background: #fdf2f4; border-left: 4px solid #d1324a; padding: 10px 14px; margin-bottom: 18px; }
        .patient-name { font-size: 14px; font-weight: bold; color: #d1324a; }
        .patient-meta { font-size: 9px; color: #555; margin-top: 3px; }
        .patient-meta span { margin-right: 18px; }
        .patient-meta .sep { color: #ccc; margin-right: 18px; }

        /* ── Info row (physician / date / status) ── */
        .info-row { display: table; width: 100%; margin-bottom: 18px; }
        .info-cell { display: table-cell; vertical-align: top; padding-right: 24px; }
        .info-cell:last-child { padding-right: 0; text-align: right; }
        .info-cell .lbl { font-size: 8px; text-transform: uppercase; color: #aaa; font-weight: bold; letter-spacing: 0.5px; }
        .info-cell .val { font-size: 11px; color: #222; margin-top: 2px; }

        /* Status pill */
        .pill { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .pill-completed { background: #d4f6e3; color: #0d6e3f; }
        .pill-pending   { background: #fff3cd; color: #856404; }
        .pill-cancelled { background: #f8d7da; color: #721c24; }

        /* ── Section heading ── */
        .section-heading { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #d1324a; margin-bottom: 6px; }

        /* ── Results table ── */
        table.results { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; }
        table.results thead tr { background: #d1324a; }
        table.results thead th { color: #fff; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; padding: 7px 10px; text-align: left; font-weight: bold; }
        table.results tbody td { padding: 7px 10px; color: #333; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        table.results tbody tr:nth-child(even) td { background: #fdf6f7; }

        /* Flag chips */
        .chip { display: inline-block; padding: 1px 8px; border-radius: 20px; font-size: 8.5px; font-weight: bold; }
        .chip-high   { background: #fde8e8; color: #b91c1c; }
        .chip-low    { background: #dbeafe; color: #1e40af; }
        .chip-normal { background: #dcfce7; color: #166534; }

        /* ── Remarks ── */
        .remarks { border-left: 3px solid #f59e0b; padding: 8px 12px; background: #fffbeb; margin-bottom: 20px; font-size: 10px; color: #78350f; }
        .remarks .lbl { font-size: 8px; text-transform: uppercase; font-weight: bold; color: #b45309; margin-bottom: 3px; }

        /* ── Signatures ── */
        .sig-table { width: 100%; margin-top: 50px; }
        .sig-table td { border: none; width: 50%; text-align: center; padding: 0 30px; vertical-align: bottom; }
        .sig-blank { height: 38px; border-bottom: 1px solid #555; margin-bottom: 5px; }
        .sig-name { font-size: 10px; font-weight: bold; color: #222; }
        .sig-title { font-size: 8.5px; color: #888; margin-top: 2px; }
        .sig-lic { font-size: 8px; color: #aaa; margin-top: 1px; }

        /* ── Footer ── */
        .footer { margin-top: 32px; padding-top: 8px; border-top: 1px solid #e8e8e8; display: table; width: 100%; }
        .footer-left { display: table-cell; font-size: 8px; color: #bbb; vertical-align: bottom; }
        .footer-right { display: table-cell; text-align: right; font-size: 8px; color: #bbb; vertical-align: bottom; }
    </style>
</head>
<body>
<div class="page">

    
    <div class="letterhead">
        <div class="letterhead-logo">
            <img src="<?php echo e(public_path('images/UIC_logo.png')); ?>" alt="UIC Logo">
        </div>
        <div class="letterhead-text">
            <div class="institution">University of the Immaculate Conception</div>
            <div class="department">Clinical Laboratory Services</div>
            <div class="address">Fr. Selga St., Davao City 8000 &bull; clinlab@uic.edu.ph</div>
        </div>
        <div class="letterhead-right">
            <div class="doc-type">Laboratory Result</div>
            <div class="doc-no">#<?php echo e(str_pad($order->lab_test_order_id, 6, '0', STR_PAD_LEFT)); ?></div>
            <div class="doc-date"><?php echo e($order->order_date ? $order->order_date->format('d M Y') : ''); ?></div>
        </div>
    </div>

    <hr class="rule-top">
    <hr class="rule-thin">

    
    <?php
        $patient = $order->patient;
        $age = $patient && $patient->birthdate ? $patient->birthdate->age : null;
    ?>
    <div class="patient-strip">
        <div class="patient-name"><?php echo e($patient->full_name ?? 'N/A'); ?></div>
        <div class="patient-meta">
            <span><?php echo e($patient->gender ?? '—'); ?></span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($age !== null): ?><span><?php echo e($age); ?> yrs old</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($patient && $patient->birthdate): ?><span>DOB: <?php echo e($patient->birthdate->format('d M Y')); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($patient && $patient->contact_number): ?><span><?php echo e($patient->contact_number); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    <div class="info-row">
        <div class="info-cell">
            <div class="lbl">Requesting Physician</div>
            <div class="val"><?php echo e($order->physician->physician_name ?? 'Not specified'); ?></div>
        </div>
        <div class="info-cell">
            <div class="lbl">Order Date</div>
            <div class="val"><?php echo e($order->order_date ? $order->order_date->format('F d, Y') : 'N/A'); ?></div>
        </div>
        <div class="info-cell">
            <div class="lbl">Time</div>
            <div class="val"><?php echo e($order->order_date ? $order->order_date->format('h:i A') : '—'); ?></div>
        </div>
        <div class="info-cell">
            <div class="lbl">Order Status</div>
            <div class="val">
                <span class="pill pill-<?php echo e($order->status); ?>"><?php echo e(ucfirst($order->status)); ?></span>
            </div>
        </div>
    </div>

    
    <div class="section-heading">Test Results</div>
    <table class="results">
        <thead>
            <tr>
                <th style="width:22%">Test</th>
                <th style="width:14%">Section</th>
                <th style="width:11%">Result</th>
                <th style="width:14%">Reference Range</th>
                <th style="width:8%">Flag</th>
                <th style="width:20%">Remarks / Findings</th>
                <th style="width:11%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $order->orderTests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
            <?php
                $result = $ot->labResult;
                $flag = null; $flagClass = '';
                if ($result && $result->result_value && $result->normal_range) {
                    $val = floatval($result->result_value);
                    if (preg_match('/^(\d+\.?\d*)\s*[-–]\s*(\d+\.?\d*)/', $result->normal_range, $m)) {
                        $low = floatval($m[1]); $high = floatval($m[2]);
                        if (is_numeric($result->result_value)) {
                            if ($val < $low)      { $flag = 'Low';    $flagClass = 'chip-low'; }
                            elseif ($val > $high) { $flag = 'High';   $flagClass = 'chip-high'; }
                            else                  { $flag = 'Normal'; $flagClass = 'chip-normal'; }
                        }
                    }
                }
                if (!$flag && $result) {
                    $text = strtolower(($result->findings ?? '') . ' ' . ($result->remarks ?? ''));
                    if (str_contains($text, 'high') || str_contains($text, 'elevated'))     { $flag = 'High';   $flagClass = 'chip-high'; }
                    elseif (str_contains($text, 'low') || str_contains($text, 'decreased')) { $flag = 'Low';    $flagClass = 'chip-low'; }
                    elseif (str_contains($text, 'normal') || str_contains($text, 'within')) { $flag = 'Normal'; $flagClass = 'chip-normal'; }
                }
            ?>
            <tr>
                <td style="font-weight:bold;"><?php echo e($ot->test->label ?? 'Unknown'); ?></td>
                <td><?php echo e($ot->test->section->label ?? '—'); ?></td>
                <td style="font-weight:bold; font-size:11px;"><?php echo e($result->result_value ?? '—'); ?></td>
                <td><?php echo e($result->normal_range ?? '—'); ?></td>
                <td>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flag): ?>
                        <span class="chip <?php echo e($flagClass); ?>"><?php echo e($flag); ?></span>
                    <?php else: ?>
                        <span style="color:#ccc;">—</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
                <td style="color:#555;"><?php echo e($result ? ($result->remarks ?? ($result->findings ?? '—')) : '—'); ?></td>
                <td>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($result): ?>
                        <span class="chip <?php echo e(match($result->status) { 'final' => 'chip-normal', 'draft' => 'chip-low', default => 'chip-high' }); ?>">
                            <?php echo e(ucfirst($result->status)); ?>

                        </span>
                    <?php else: ?>
                        <span style="color:#aaa; font-size:9px;">Pending</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
            </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </tbody>
    </table>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->remarks): ?>
    <div class="remarks">
        <div class="lbl">Order Remarks</div>
        <?php echo e($order->remarks); ?>

    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php
        $performers = $order->orderTests
            ->filter(fn($ot) => $ot->labResult && $ot->labResult->performedBy)
            ->map(fn($ot) => $ot->labResult->performedBy->firstname . ' ' . $ot->labResult->performedBy->lastname)
            ->unique()->implode(', ');
        $verifiers = $order->orderTests
            ->filter(fn($ot) => $ot->labResult && $ot->labResult->verifiedBy)
            ->map(fn($ot) => $ot->labResult->verifiedBy->firstname . ' ' . $ot->labResult->verifiedBy->lastname)
            ->unique()->implode(', ');
    ?>
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-blank"></div>
                <div class="sig-name"><?php echo e($performers ?: 'Medical Technologist'); ?></div>
                <div class="sig-title">Medical Technologist</div>
                <div class="sig-lic">License No. _________________</div>
            </td>
            <td>
                <div class="sig-blank"></div>
                <div class="sig-name"><?php echo e($verifiers ?: 'Pathologist'); ?></div>
                <div class="sig-title">Pathologist / Laboratory Director</div>
                <div class="sig-lic">License No. _________________</div>
            </td>
        </tr>
    </table>

    
    <div class="footer">
        <div class="footer-left">
            University of the Immaculate Conception &bull; Clinical Laboratory &bull; Davao City
        </div>
        <div class="footer-right">
            Generated: <?php echo e(now()->format('d M Y, h:i A')); ?> &bull; Order #<?php echo e(str_pad($order->lab_test_order_id, 6, '0', STR_PAD_LEFT)); ?>

        </div>
    </div>

</div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\resources\views/pdf/lab-result.blade.php ENDPATH**/ ?>