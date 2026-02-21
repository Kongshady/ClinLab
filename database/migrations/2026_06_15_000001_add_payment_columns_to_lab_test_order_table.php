<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_test_order', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('PENDING_PAYMENT')->after('status');
            $table->decimal('total_amount', 10, 2)->nullable()->after('payment_status');
            $table->datetime('paid_at')->nullable()->after('total_amount');
            $table->integer('paid_by_transaction_id')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('lab_test_order', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'total_amount', 'paid_at', 'paid_by_transaction_id']);
        });
    }
};
