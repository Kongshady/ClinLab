<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment_usage', function (Blueprint $table) {
            $table->integer('usage_id', true, true)->length(10);
            $table->integer('equipment_id')->length(10);
            $table->date('date_used');
            $table->string('user_name', 200);
            $table->string('item_name', 200);
            $table->integer('quantity')->length(10)->default(1);
            $table->text('purpose');
            $table->string('or_number', 50)->nullable();
            $table->enum('status', ['functional', 'not_functional'])->default('functional');
            $table->text('remarks')->nullable();
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            
            $table->primary('usage_id');
            $table->index('equipment_id');
            $table->index('date_used', 'idx_date_used');
            $table->index('user_name', 'idx_user_name');
            $table->index('status', 'idx_status');
            
            $table->foreign('equipment_id', 'fk_equipment_usage')
                  ->references('equipment_id')->on('equipment')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_usage');
    }
};
