<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * sn_products 表的 sku_type 列更名为 spec_type
     * （product_skus → product_specs 命名迁移的一部分，兼容未执行过旧迁移的全新安装）。
     */
    public function up(): void
    {
        if (! Schema::hasTable('sn_products')) {
            return;
        }

        if (Schema::hasColumn('sn_products', 'spec_type')) {
            return;
        }

        if (Schema::hasColumn('sn_products', 'sku_type')) {
            Schema::table('sn_products', function (Blueprint $table) {
                $table->renameColumn('sku_type', 'spec_type');
            });

            return;
        }

        Schema::table('sn_products', function (Blueprint $table) {
            $table->string('spec_type', 20)->default('single')->comment('spec_type类型');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('sn_products')) {
            return;
        }

        if (Schema::hasColumn('sn_products', 'sku_type')) {
            return;
        }

        if (Schema::hasColumn('sn_products', 'spec_type')) {
            Schema::table('sn_products', function (Blueprint $table) {
                $table->renameColumn('spec_type', 'sku_type');
            });
        }
    }
};
