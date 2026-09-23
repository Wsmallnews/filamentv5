<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_wallet_market_rates', function (Blueprint $table) {
            $table->id();
            $table->string('source_currency', 3)->comment('源币种(ISO 4217)');
            $table->string('target_currency', 3)->comment('目标币种(通常为记账本位币)');
            $table->decimal('rate', 24, 10)->comment('1 源币种主单位 = rate 目标币种主单位');
            $table->timestamps();

            $table->unique(['source_currency', 'target_currency'], 'sn_wallet_market_rates_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_wallet_market_rates');
    }
};
