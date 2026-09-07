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
        Schema::create('sn_links', function (Blueprint $table) {
            $table->comment('友情链接');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type', 60)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');

            $table->string('name')->comment('名称');
            $table->string('url')->comment('链接');
            $table->string('logo')->nullable()->comment('LOGO');
            $table->string('group_name')->nullable()->comment('分组');
            $table->unsignedInteger('order_column')->nullable()->comment('排序');
            $table->boolean('nofollow')->default(false)->comment('nofollow');
            $table->string('status')->default('normal')->comment('状态');
            $table->timestamps();

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
        Schema::dropIfExists('sn_links');
    }
};
