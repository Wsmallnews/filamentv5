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
        // 附加项库（两级：组行 parent_id=0 / 选项行指向组）
        Schema::create('sn_product_extras', function (Blueprint $table) {
            $table->comment('产品附加项库（加料/增值服务）');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('scope_type', 20)->nullable()->comment('作用域类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('作用域 ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('所属组（0 = 组行）');
            $table->string('name', 60)->comment('名称');
            $table->string('description')->nullable()->comment('描述');
            $table->string('type', 20)->default('multiple')->comment('选择类型：single/multiple（组行）');
            $table->boolean('is_require')->default(false)->comment('是否必选（组行）');
            $table->boolean('is_num')->default(false)->comment('是否按数量计价（组行）');
            $table->unsignedBigInteger('price')->default(0)->comment('加价金额，整数分（选项行）');
            $table->datetime('sold_out_until')->nullable()->comment('售罄至时间点：之前置灰不可选，过后自动恢复（选项行）');
            $table->json('options')->nullable()->comment('扩展：数量型起购/限购 limit_min_num/limit_max_num');
            $table->string('status', 20)->default('up')->comment('状态：up/down（永久上下架，手动控制）');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['parent_id', 'status']);
        });

        // 商品挂载（选项粒度：挂哪个选项、是否预选、按商品覆盖价）
        Schema::create('sn_product_extra_items', function (Blueprint $table) {
            $table->comment('产品附加项挂载');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('product_id')->default(0)->comment('产品');
            $table->unsignedBigInteger('extra_id')->default(0)->comment('附加项（选项行）');
            $table->boolean('is_default')->default(false)->comment('是否默认选中');
            $table->unsignedBigInteger('price_override')->nullable()->comment('覆盖价，整数分（空 = 用库价，库改价即同步）');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序');
            $table->timestamps();
            $table->index(['product_id', 'extra_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_product_extra_items');
        Schema::dropIfExists('sn_product_extras');
    }
};
