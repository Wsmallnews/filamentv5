<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sn_category_types', function (Blueprint $table) {
            $table->comment('分类类别');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type', 60)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');

            $table->string('name')->nullable()->comment('名称');
            $table->tinyInteger('level')->nullable()->comment('层级');
            $table->string('description')->nullable()->comment('描述');

            $table->json('options')->nullable()->comment('选项');
            $table->string('status')->nullable()->comment('状态');
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
        Schema::dropIfExists('sn_category_types');
    }
};
