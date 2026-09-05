<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Wsmallnews\Cms\Filament\Resources\Posts\Pages\CreatePost;
use Wsmallnews\Cms\Filament\Resources\Posts\Pages\EditPost;
use Wsmallnews\Cms\Filament\Resources\Posts\Pages\ListPosts;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Enums\ContentType;

uses(RefreshDatabase::class);

use function Pest\Livewire\livewire;

beforeEach(function () {
    $admin = User::factory()->create();

    $this->actingAs($admin, 'admin');

    Storage::fake();

    // post 表单的分类树选择（select-tree）查询 sn_categories，测试库需补建 category 包的表（迁移未发布到应用目录）
    foreach (glob(base_path('addons/category/database/migrations/*.php.stub')) ?: [] as $migrationFile) {
        (require $migrationFile)->up();
    }
});

it('post 表单的类型切换包含全部内容类型', function () {
    $html = livewire(CreatePost::class)->html();

    // cms 配置 types => null，提供全部四种类型
    expect($html)->toContain('富文本')
        ->and($html)->toContain('Markdown')
        ->and($html)->toContain('纯文本')
        ->and($html)->toContain('纯图');
});

it('默认类型为富文本并写入 sn_contents 关联', function () {
    livewire(CreatePost::class)
        ->fillForm([
            'title' => '富文本文章',
            'slug' => 'rich-post',
            'post_image' => [UploadedFile::fake()->image('cover.jpg')],
            'scheduledTasks' => [],
            'content.content_richtext' => '<p>富文本正文</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::where('title', '富文本文章')->first();

    expect($post)->not->toBeNull()
        ->and($post->content)->not->toBeNull()
        ->and($post->content->content_type)->toBe(ContentType::Richtext)
        ->and($post->content->content)->toBe('<p>富文本正文</p>');
});

it('切换 Markdown 类型创建 post', function () {
    livewire(CreatePost::class)
        ->fillForm([
            'title' => 'Markdown 文章',
            'slug' => 'markdown-post',
            'post_image' => [UploadedFile::fake()->image('cover.jpg')],
            'scheduledTasks' => [],
            'content.content_type' => ContentType::Markdown->value,
            'content.content_markdown' => '# 标题',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::where('title', 'Markdown 文章')->first();

    expect($post->content->content_type)->toBe(ContentType::Markdown)
        ->and($post->content->content)->toBe('# 标题');
});

it('只配置一种类型时隐藏类型切换按钮', function () {
    config([
        'sn-cms.contents.post.types' => [ContentType::Markdown],
        'sn-cms.contents.post.default_type' => ContentType::Markdown,
    ]);

    $html = livewire(CreatePost::class)->html();

    // 切换按钮隐藏，仅显示 Markdown 编辑器
    expect($html)->not->toContain('编辑器类型')
        ->and($html)->toContain('Markdown');

    livewire(CreatePost::class)
        ->fillForm([
            'title' => '单类型文章',
            'slug' => 'single-type-post',
            'post_image' => [UploadedFile::fake()->image('cover.jpg')],
            'scheduledTasks' => [],
            'content.content_markdown' => '# 单类型',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::where('title', '单类型文章')->first();

    expect($post->content->content_type)->toBe(ContentType::Markdown)
        ->and($post->content->content)->toBe('# 单类型');
});

it('编辑 post 时回填当前类型内容并可切换为富文本', function () {
    $admin = User::factory()->create();
    $post = Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '旧文章',
        'slug' => 'old-post',
        'status' => 'published',
    ]);
    $post->content()->create([
        'content' => '# 原始内容',
        'content_type' => ContentType::Markdown->value,
    ]);

    livewire(EditPost::class, ['record' => $post->slug])
        ->assertFormSet([
            'content.content_type' => ContentType::Markdown,
            'content.content_markdown' => '# 原始内容',
        ])
        ->fillForm([
            'post_image' => [UploadedFile::fake()->image('new-cover.jpg')],
            'content.content_type' => ContentType::Richtext->value,
            'content.content_richtext' => '<p>切换后的内容</p>',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->refresh()->content->content_type)->toBe(ContentType::Richtext)
        ->and($post->content->content)->toBe('<p>切换后的内容</p>');
});

it('posts 表格 flags 列以原生 badge 渲染 enum 元数据', function () {
    $post = Post::create([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '徽章列文章',
        'slug' => 'badge-flags-post',
        'flags' => ['hot', 'top'],
        'status' => 'published',
    ]);

    livewire(ListPosts::class)
        ->assertSuccessful()
        ->assertSee('徽章列文章')
        // flag label 来自 PostFlag enum 的翻译键
        ->assertSee('热门')
        ->assertSee('置顶')
        // 原生 badge 走 Filament 色名解析
        ->assertSeeHtml('fi-color-danger')
        ->assertSeeHtml('fi-color-warning');
});
