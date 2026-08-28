<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sn_product_variants', function (Blueprint $table) {
            $table->comment('产品变体');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('product_id')->default(0)->comment('产品');
            $table->string('product_spec_text')->nullable()->comment('规格中文');
            $table->string('product_sn')->nullable()->comment('货号');
            $table->string('image')->nullable()->comment('规格封面');
            $table->string('spec_type', 20)->comment('spec_type类型');
            $table->unsignedInteger('price')->default(0)->comment('现价');
            $table->integer('stock')->default(0)->comment('库存');
            $table->integer('sales')->default(0)->comment('销量');
            $table->string('stock_unit', 20)->nullable()->comment('库存单位');
            $table->unsignedInteger('stock_convert_num')->default(0)->comment('换算比例');
            $table->decimal('weight', 10, 2)->default(0)->comment('重量KG');
            $table->string('status', 20)->comment('规格状态');
            $table->timestamps();
            $table->index('product_id');
        });

        Schema::create('sn_product_spec_variants', function (Blueprint $table) {
            $table->comment('规格变体关联表');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('variant_id')->default(0)->comment('变体');
            $table->unsignedBigInteger('spec_id')->default(0)->comment('产品规格项');
            $table->timestamps();
            $table->unique(['variant_id', 'spec_id'], 'psv_spec_variant_unique');
            $table->index('spec_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_product_variants');

        Schema::dropIfExists('sn_product_spec_variants');
    }
};
