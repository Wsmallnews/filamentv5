<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID(NULL=全局钱包,如跨租户共享的用户钱包)');
            $table->string('owner_type', 100)->comment('所有者类型');
            $table->unsignedBigInteger('owner_id')->comment('所有者ID');
            $table->unsignedBigInteger('wallet_type_id')->comment('钱包类型ID');
            $table->unsignedBigInteger('balance')->default(0)->comment('可用余额(钱包最小单位)');
            $table->unsignedBigInteger('frozen')->default(0)->comment('冻结金额(钱包最小单位)');
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'wallet_type_id'], 'sn_wallets_owner_type_unique');
            $table->index('wallet_type_id');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_wallets');
    }
};
