<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sn_pay_refunds', function (Blueprint $table) {
            $table->comment('退款');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            $table->unsignedBigInteger('pay_record_id')->default(0)->comment('支付记录');
            $table->string('refund_sn', 60)->unique()->comment('退款单号');
            $table->morphs('payer');
            $table->morphs('refundable');
            $table->json('refundable_options')->nullable()->comment('refundable选项');
            $table->string('pay_method', 20)->comment('支付方式');
            $table->string('currency', 3)->default('CNY')->comment('退款币种(ISO 4217)，与支付单一致');
            $table->unsignedBigInteger('refund_fee')->default(0)->comment('退款金额');
            $table->unsignedBigInteger('real_refund_fee')->default(0)->comment('实际退款金额');
            $table->string('refund_type', 20)->comment('退款类型');
            $table->string('refund_method', 20)->comment('退款方式');
            $table->string('status', 20)->comment('退款状态');
            $table->string('remark')->comment('备注');
            $table->string('transaction_id', 60)->nullable()->comment('交易单号');
            $table->json('payment_json')->nullable()->comment('交易原始数据');
            $table->json('buyer_info')->nullable()->comment('交易用户');
            $table->timestamps();
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_pay_refunds');
    }
};
