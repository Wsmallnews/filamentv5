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
        Schema::create('sn_product_specs', function (Blueprint $table) {
            $table->comment('产品规格项');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('product_id')->default(0)->comment('产品');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('上级');
            $table->string('name')->nullable()->comment('名称');
            $table->string('image')->nullable()->comment('规格图');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序');
            $table->index(['product_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_product_specs');
    }
};
