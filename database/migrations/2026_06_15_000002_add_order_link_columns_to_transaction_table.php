<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->integer('lab_test_order_id')->nullable()->after('status_code');
            $table->decimal('amount', 10, 2)->nullable()->after('lab_test_order_id');
            $table->string('payment_method', 50)->nullable()->after('amount');
            $table->integer('processed_by')->nullable()->after('payment_method');
            $table->datetime('paid_at')->nullable()->after('processed_by');
        });
    }

    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn(['lab_test_order_id', 'amount', 'payment_method', 'processed_by', 'paid_at']);
        });
    }
};
