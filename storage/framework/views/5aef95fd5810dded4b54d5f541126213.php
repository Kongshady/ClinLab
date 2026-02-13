
<div class="border-b border-gray-200 bg-white px-6 pt-4">
    <nav class="flex space-x-8" aria-label="Certificate Tabs">
        <a href="<?php echo e(route('certificates.index')); ?>"
           class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors <?php echo e(request()->routeIs('certificates.index') ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Certificates
        </a>
        <a href="<?php echo e(route('certificates.issued')); ?>"
           class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors <?php echo e(request()->routeIs('certificates.issued') ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Issued
        </a>
        <a href="<?php echo e(route('certificates.templates')); ?>"
           class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors <?php echo e(request()->routeIs('certificates.templates') ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            Templates
        </a>
        <a href="<?php echo e(route('certificates.verify')); ?>"
           class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors <?php echo e(request()->routeIs('certificates.verify') ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Verify
        </a>
    </nav>
</div>
<?php /**PATH C:\xampp\htdocs\dashboard\clinlab_app\resources\views/certificates/_tabs.blade.php ENDPATH**/ ?>