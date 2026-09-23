<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->unique()->comment('幂等键');
            $table->unsignedBigInteger('wallet_id')->comment('钱包ID');
            $table->unsignedBigInteger('team_id')->nullable()->comment('归属团队ID(全局钱包的租户归因)');
            $table->string('type', 20)->comment('变动类型(recharge/consume/refund/adjust/freeze/unfreeze/freeze_consume/frozen_credit/transfer_in/transfer_out)');
            $table->bigInteger('amount')->default(0)->comment('业务变动量(钱包最小单位,带符号)');
            $table->bigInteger('balance_change')->default(0)->comment('可用余额变动(带符号)');
            $table->bigInteger('frozen_change')->default(0)->comment('冻结变动(带符号)');
            $table->unsignedBigInteger('balance_after')->default(0)->comment('变动后可用余额快照');
            $table->unsignedBigInteger('frozen_after')->default(0)->comment('变动后冻结金额快照');
            $table->string('subject_type', 100)->nullable()->comment('业务主体类型');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('业务主体ID');
            $table->string('causer_type', 100)->nullable()->comment('触发人类型');
            $table->unsignedBigInteger('causer_id')->nullable()->comment('触发人ID');
            $table->string('transfer_sn', 40)->nullable()->comment('转账对冲号(双流水关联)');
            $table->string('description', 200)->default('')->comment('描述');
            $table->json('options')->nullable()->comment('附加信息(换算快照等)');
            $table->timestamps();

            $table->index('wallet_id');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index('transfer_sn');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_wallet_transactions');
    }
};
