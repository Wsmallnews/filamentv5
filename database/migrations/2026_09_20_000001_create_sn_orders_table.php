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
        Schema::create(config('sn-order.order_table_name', 'sn_orders'), function (Blueprint $table) {
            $table->comment('订单');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            $table->string('type', 20)->comment('订单类型');
            $table->string('order_sn', 60)->unique()->comment('订单号');
            $table->morphs('buyer');
            $table->json('original_amount_fields')->nullable()->comment('原费用集合');
            $table->json('amount_fields')->nullable()->comment('现费用集合');
            $table->unsignedInteger('relate_original_amount')->default(0)->comment('原项目总金额');
            $table->unsignedInteger('relate_amount')->default(0)->comment('项目总金额');
            $table->unsignedInteger('order_amount')->default(0)->comment('订单总金额');
            $table->unsignedInteger('score_amount')->default(0)->comment('积分总数');
            $table->string('remark')->nullable()->comment('用户备注');
            $table->string('memo')->nullable()->comment('商家备注');
            $table->string('status', 20)->comment('订单状态');
            $table->json('discount_fields')->nullable()->comment('优惠费用集合');
            $table->unsignedInteger('discount_amount')->default(0)->comment('优惠总金额');
            $table->unsignedInteger('pay_fee')->default(0)->comment('支付总金额');
            $table->unsignedInteger('original_pay_fee')->default(0)->comment('原始支付总金额');
            $table->unsignedInteger('remain_pay_fee')->default(0)->comment('剩余支付金额');
            $table->string('pay_status', 20)->comment('支付状态');
            $table->timestamp('paid_at', precision: 0)->nullable()->comment('支付时间');
            $table->string('delivery_types', 60)->nullable()->comment('配送方式');
            $table->string('delivery_status', 20)->comment('配送状态');
            $table->string('refund_status', 20)->comment('退款状态');
            $table->json('fields_infos')->nullable()->comment('fields详细');
            $table->json('options')->nullable()->comment('选项');
            $table->string('platform', 60)->nullable()->comment('平台');
            $table->timestamps();
            $table->softDeletes();
            $table->index('scope_type');
            $table->index('scope_id');
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('sn-order.order_table_name', 'sn_orders'));
    }
};
