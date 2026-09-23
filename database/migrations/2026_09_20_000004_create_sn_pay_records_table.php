<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sn_pay_records', function (Blueprint $table) {
            $table->comment('支付记录');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            $table->string('pay_sn', 60)->unique()->comment('订单号');
            $table->morphs('payer');
            $table->morphs('payable');
            $table->json('payable_options')->nullable()->comment('payable选项');
            $table->string('pay_method', 20)->comment('支付方式');
            $table->string('currency', 3)->default('CNY')->comment('支付币种(ISO 4217)，创建时快照');
            $table->unsignedBigInteger('pay_fee')->default(0)->comment('支付金额');
            $table->unsignedBigInteger('real_fee')->default(0)->comment('实际金额');
            $table->string('transaction_id', 60)->nullable()->comment('交易单号');
            $table->json('buyer_info')->nullable()->comment('交易用户');
            $table->json('payment_json')->nullable()->comment('交易原始数据');
            $table->timestamp('paid_at', precision: 0)->nullable()->comment('支付时间');
            $table->string('status', 20)->comment('支付状态');
            $table->unsignedBigInteger('refunded_fee')->default(0)->comment('已退款金额');
            $table->timestamps();
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_pay_records');
    }
};
