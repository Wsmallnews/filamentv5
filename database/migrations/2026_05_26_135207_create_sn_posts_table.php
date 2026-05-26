<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wsmallnews\Category\Models\Category;
use Wsmallnews\Cms\Models\Post;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sn_posts', function (Blueprint $table) {
            $table->comment('图文内容');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type', 60)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');

            $table->morphs('publisher');
            $table->string('title')->nullable()->comment('标题');
            $table->string('slug')->nullable()->comment('路径');
            $table->string('description')->nullable()->comment('描述');
            $table->json('counter')->nullable()->comment('计数器:view_num,like_num,collect_num等');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->timestamp('scheduled_at')->nullable()->comment('定时发布时间');

            $table->json('flags')->nullable()->comment('标志');
            $table->json('options')->nullable()->comment('选项');
            $table->string('status')->nullable()->comment('状态');
            $table->unsignedInteger('order_column')->nullable()->comment('排序');
            $table->timestamps();
            $table->softDeletes();
            $table->index('team_id');
            $table->index(['scope_type', 'scope_id']);
            $table->index('order_column');
            $table->index('slug');
        });

        Schema::create('sn_category_post', function (Blueprint $table) {
            $table->foreignIdFor(Category::class)->constrained(table: 'sn_categories')->cascadeOnDelete();
            $table->foreignIdFor(Post::class)->constrained(table: 'sn_posts')->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_category_post');
        Schema::dropIfExists('sn_posts');
    }
};
