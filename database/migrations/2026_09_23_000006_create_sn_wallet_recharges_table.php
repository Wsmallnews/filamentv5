<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_wallet_recharges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('owner_type', 100)->comment('受益人类型');
            $table->unsignedBigInteger('owner_id')->comment('受益人ID');
            $table->string('wallet_type', 20)->comment('钱包类型code(快照)');
            $table->unsignedBigInteger('wallet_amount')->default(0)->comment('到账金额(钱包最小单位)');
            $table->string('pay_currency', 3)->default('CNY')->comment('支付币种(ISO 4217,创建时快照)');
            $table->unsignedBigInteger('pay_fee')->default(0)->comment('应付金额(整数分)');
            $table->json('rate_options')->nullable()->comment('换算快照(锚定率等)');
            $table->string('status', 20)->default('unpaid')->comment('状态(unpaid/paid/closed)');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
            $table->index('team_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_wallet_recharges');
    }
};
