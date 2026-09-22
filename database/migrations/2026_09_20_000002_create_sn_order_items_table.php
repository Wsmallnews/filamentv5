<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(config('sn-order.order_item_table_name', 'sn_order_items'), function (Blueprint $table) {
            $table->comment('订单Item');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            $table->unsignedBigInteger('order_id')->default(0)->comment('订单');
            $table->morphs('buyer');
            $table->string('relate_type', 20)->comment('关联类型');
            $table->unsignedBigInteger('relate_id')->default(0)->comment('关联');
            $table->string('relate_title')->nullable()->comment('关联标题');
            $table->string('relate_subtitle')->nullable()->comment('关联副标题');
            $table->json('relate_attributes')->nullable()->comment('属性集合');
            $table->string('relate_image')->nullable()->comment('关联图片');
            $table->unsignedInteger('relate_original_price')->default(0)->comment('关联原始价格');
            $table->unsignedInteger('relate_price')->default(0)->comment('关联价格');
            $table->unsignedInteger('relate_stock_num')->default(0)->comment('关联单位数量');
            $table->unsignedInteger('relate_num')->default(0)->comment('关联数量');
            $table->decimal('relate_weight', 10, 2)->default(0)->comment('关联重量KG');
            $table->string('relate_sn', 60)->nullable()->comment('关联货号');
            $table->json('relate_options')->nullable()->comment('关联选项');

            // product_id
            // product_title
            // product_image
            // product_stock_num
            // product_num
            // product_weight
            // product_sn
            // product_type(未记录)
            // product_variant_id(未记录)
            // item_product_variant_id(未记录)
            // product_sku_text(未记录)
            // original_product_price(未记录)
            // product_price(未记录)
            // product_attributes(未记录)
            // original_product_attribute_unit_amount(未记录)
            // product_attribute_unit_amount(未记录)
            // product_attribute_text(未记录)
            // original_product_unit_price(未记录)
            // product_unit_price(未记录)
            // product_sku_type(未记录)             // 下单扣库存需要知道是不是多规格或者多单位
            // product_relate_id(好像暂时不需要)

            $table->string('stock_unit', 30)->nullable()->comment('库存单位');
            $table->string('stock_type', 30)->comment('库存类型');

            $table->json('original_amount_fields')->nullable()->comment('原费用集合');
            $table->json('amount_fields')->nullable()->comment('现费用集合');

            $table->unsignedInteger('original_amount')->default(0)->comment('原始总金额(含运费)');
            $table->unsignedInteger('amount')->default(0)->comment('总金额(含运费)');
            $table->unsignedInteger('score_amount')->default(0)->comment('积分总数');

            $table->json('discount_fields')->nullable()->comment('优惠费用集合');
            $table->unsignedInteger('discount_amount')->default(0)->comment('优惠总金额');

            // 下面的字段根据情况再做调整
            $table->unsignedInteger('total_fee')->default(0)->comment('真实金额(含运费)');
            $table->unsignedInteger('reonly_fee')->default(0)->comment('真实金额(不含运费)');
            $table->string('pay_status', 20)->comment('支付状态');

            $table->string('delivery_type', 20)->comment('配送方式');
            $table->string('delivery_status', 20)->comment('配送状态');
            $table->unsignedInteger('delivery_amount')->default(0)->comment('配送费用');
            $table->unsignedBigInteger('delivery_id')->default(0)->comment('配送模板');

            $table->string('aftersale_status', 20)->comment('售后状态');
            $table->string('evaluate_status', 20)->comment('评价状态');
            $table->string('refund_status', 20)->comment('退款状态');
            $table->unsignedInteger('refunded_fee')->default(0)->comment('退款金额');
            $table->string('refund_msg')->nullable()->comment('退款原因');

            $table->unsignedBigInteger('express_package_id')->default(0)->comment('快递包裹');

            $table->json('fields_infos')->nullable()->comment('fields详细');
            $table->json('options')->nullable()->comment('选项');
            $table->timestamps();
            $table->index('scope_type');
            $table->index('scope_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('sn-order.order_item_table_name', 'sn_order_items'));
    }
};
