<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_preferences', function (Blueprint $table) {
            $table->comment('偏好');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type')->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');

            $table->string('type')->nullable()->comment('偏好类型:like=喜欢,view=浏览记录等');

            $table->morphs('preferencer');      // 关联人
            $table->morphs('preferenceable');   // 关联模型

            $table->json('options')->nullable()->comment('选项');
            $table->timestamps();
            $table->softDeletes();
            $table->index('team_id');
            $table->index(['scope_type', 'scope_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_preferences');
    }
};
