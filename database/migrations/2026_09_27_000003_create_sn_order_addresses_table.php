<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_order_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('consignee', 64)->comment('收货人');
            $table->string('phone', 32)->comment('手机号');
            $table->char('country_code', 2)->default('CN')->comment('ISO 3166-1 国家码');

            // 国际通用区划槽位:code + 名称快照成对(下单时从地址簿复制,此后独立于地址簿变更)
            $table->string('administrative_area', 32)->nullable()->comment('一级行政区code(CN:省)');
            $table->string('administrative_area_name', 128)->nullable()->comment('一级行政区名称快照');
            $table->string('locality', 32)->nullable()->comment('二级行政区code(CN:市)');
            $table->string('locality_name', 128)->nullable()->comment('二级行政区名称快照');
            $table->string('dependent_locality', 32)->nullable()->comment('三级行政区code(CN:区县)');
            $table->string('dependent_locality_name', 128)->nullable()->comment('三级行政区名称快照');
            $table->string('township', 32)->nullable()->comment('四级行政区code(CN:乡镇街道)');
            $table->string('township_name', 128)->nullable()->comment('四级行政区名称快照');

            $table->string('address_line1')->comment('详细地址(街道/楼牌/门牌)');
            $table->string('address_line2')->nullable()->comment('地址补充行');
            $table->string('postal_code', 20)->nullable()->comment('邮编');
            $table->decimal('longitude', 10, 7)->nullable()->comment('经度');
            $table->decimal('latitude', 10, 7)->nullable()->comment('纬度');
            $table->unsignedBigInteger('source_address_id')->nullable()->comment('下单时引用的地址簿条目ID(溯源用,无外键约束)');
            $table->json('options')->nullable()->comment('扩展信息');
            $table->timestamps();

            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_order_addresses');
    }
};
