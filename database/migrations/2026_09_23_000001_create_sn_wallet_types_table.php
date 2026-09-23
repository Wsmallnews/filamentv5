<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_wallet_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('类型标识(balance/point/energy)');
            $table->string('name', 50)->comment('类型名称');
            $table->string('currency_code', 10)->default('CNY')->comment('钱包单位币种代码(ISO 4217 或自定义码如 POINT)');
            $table->tinyInteger('decimals')->default(2)->comment('小数位数(0=整数积分)');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_wallet_types');
    }
};
