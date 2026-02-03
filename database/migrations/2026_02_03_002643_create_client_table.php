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
        Schema::create('client', function (Blueprint $table) {
            $table->integer('client_id', true, true)->length(10);
            $table->integer('client_type_id')->length(10);
            $table->integer('reference_id')->length(10);
            
            $table->primary('client_id');
            $table->index('client_type_id', 'idx_client_type');
            
            $table->foreign('client_type_id', 'fk_client_clienttype')
                  ->references('client_type_id')->on('client_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client');
    }
};
