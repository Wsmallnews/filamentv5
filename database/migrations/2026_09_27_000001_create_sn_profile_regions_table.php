<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_profile_regions', function (Blueprint $table) {
            // 主键 = 区划码（AreaCity 数据的 id，如 440305003），静态导入数据，不设自增
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('上级区划码(0=省级)');
            $table->unsignedTinyInteger('level')->comment('层级:1省 2市 3区县 4乡镇街道');
            $table->string('name', 64)->comment('短名(如:北京)');
            $table->string('full_name', 128)->nullable()->comment('全称(如:北京市)');
            $table->string('ext_id', 32)->nullable()->comment('统计局12位区划码');
            $table->string('pinyin_prefix', 8)->nullable()->comment('拼音首字母');
            $table->string('pinyin', 128)->nullable()->comment('全拼');

            $table->index(['parent_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_profile_regions');
    }
};
