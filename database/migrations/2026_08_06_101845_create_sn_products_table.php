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
        Schema::create('sn_products', function (Blueprint $table) {
            $table->comment('产品');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');

            $table->morphs('publisher');
            $table->string('type', 30)->nullable()->comment('类型');
            $table->string('title')->nullable()->comment('标题');
            $table->string('subtitle')->nullable()->comment('副标题');
            $table->string('sku_type', 20)->comment('sku类型');
            $table->unsignedInteger('price')->default(0)->comment('现价');

            $table->string('stock_type', 20)->comment('库存类型');
            $table->string('stock_unit', 20)->nullable()->comment('库存单位');
            $table->json('params')->nullable()->comment('参数');

            $table->json('counter')->nullable()->comment('计数器:view_num,like_num,collect_num等');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->timestamp('scheduled_at')->nullable()->comment('定时发布时间');
            $table->json('options')->nullable()->comment('选项');
            $table->string('status', 20)->comment('商品状态');
            $table->unsignedInteger('order_column')->nullable()->comment('排序');
            $table->timestamps();
            $table->softDeletes();
            $table->index('team_id');
            $table->index(['scope_type', 'scope_id']);
            $table->index('order_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_product');
    }
};
