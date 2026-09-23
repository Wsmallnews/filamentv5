<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_wallet_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_type_id')->comment('钱包类型ID');
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID(NULL=全局默认锚定率)');
            $table->decimal('anchor_rate', 24, 8)->comment('锚定率:1 锚定币种主单位 = anchor_rate 钱包主单位');
            $table->string('anchor_currency', 3)->comment('锚定币种(ISO 4217)');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->unique(['wallet_type_id', 'team_id'], 'sn_wallet_rates_type_team_unique');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_wallet_rates');
    }
};
