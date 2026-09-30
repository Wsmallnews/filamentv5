<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_profile_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID(NULL=全局地址,如跨租户共享的用户地址)');
            $table->string('owner_type', 100)->comment('所有者类型(User/Member)');
            $table->unsignedBigInteger('owner_id')->comment('所有者ID');
            $table->string('label', 32)->nullable()->comment('地址标签(家/公司)');
            $table->string('consignee', 64)->comment('收货人');
            $table->string('phone', 32)->comment('手机号');
            $table->char('country_code', 2)->default('CN')->comment('ISO 3166-1 国家码');

            // 国际通用区划槽位:code + 名称快照成对(名称快照不随区划数据更新漂移)
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
            $table->decimal('longitude', 10, 7)->nullable()->comment('经度(地图选址预留)');
            $table->decimal('latitude', 10, 7)->nullable()->comment('纬度(地图选址预留)');
            $table->boolean('is_default')->default(false)->comment('默认地址(同一owner内唯一,应用层保证)');
            $table->unsignedInteger('used_num')->default(0)->comment('下单使用次数');
            $table->json('options')->nullable()->comment('扩展信息');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
            $table->index('team_id');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_profile_addresses');
    }
};
