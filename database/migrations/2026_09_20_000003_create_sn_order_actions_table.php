<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sn_order_actions', function (Blueprint $table) {
            $table->comment('订单Item');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            $table->unsignedBigInteger('order_id')->default(0)->comment('订单');
            $table->string('order_type', 20)->comment('订单类型(同order表)');
            $table->unsignedBigInteger('order_item_id')->default(0)->comment('订单Item');
            $table->morphs('buyer');
            $table->morphs('operator');
            $table->string('order_status', 20)->comment('订单状态');
            $table->json('order_status_fields')->nullable()->comment('订单状态集合');
            $table->json('item_status_fields')->nullable()->comment('Item状态集合');
            $table->string('message')->nullable()->comment('操作信息');
            $table->json('options')->nullable()->comment('选项');
            $table->timestamps();
            $table->index('scope_type');
            $table->index('scope_id');
            $table->index('order_id');
            $table->index('order_item_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_order_actions');
    }
};
