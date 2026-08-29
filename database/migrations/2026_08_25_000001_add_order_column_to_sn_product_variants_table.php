<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * sn_product_variants 表增加 order_column（组合顺序权重），
     * 用于规格顺序重排时在不删除记录的前提下更新变体排序。
     */
    public function up(): void
    {
        if (! Schema::hasTable('sn_product_variants')) {
            return;
        }

        if (Schema::hasColumn('sn_product_variants', 'order_column')) {
            return;
        }

        Schema::table('sn_product_variants', function (Blueprint $table) {
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('sn_product_variants')) {
            return;
        }

        if (! Schema::hasColumn('sn_product_variants', 'order_column')) {
            return;
        }

        Schema::table('sn_product_variants', function (Blueprint $table) {
            $table->dropColumn('order_column');
        });
    }
};
