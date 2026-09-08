<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Wsmallnews\Cms\Filament\Resources\Posts\Pages\CreatePost;
use Wsmallnews\Cms\Models\Post;

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

it('超长标题自动生成的 slug 按词边界截断到 80 字符内', function () {
    $title = str_repeat('lorem-ipsum-dolor-', 12);

    $livewire = livewire(CreatePost::class)
        ->fillForm(['title' => $title]);

    $slug = $livewire->instance()->data['slug'] ?? null;

    expect($slug)->toBeString()
        ->and($slug)->not->toBe('')
        ->and(Str::length($slug))->toBeLessThanOrEqual(80)
        ->and($slug)->not->toEndWith('-')
        // 截断发生在连字符词边界，最后一个单词是完整的（80 字符窗口止于第 5 组的 lorem-）
        ->and(Str::afterLast($slug, '-'))->toBe('lorem');
});

it('超长中文标题转写的拼音 slug 同样按词边界截断', function () {
    $livewire = livewire(CreatePost::class)
        ->fillForm(['title' => str_repeat('这是一篇很长的中文标题', 10)]);

    $slug = $livewire->instance()->data['slug'] ?? null;

    // 无 intl 环境 Str::slug 结果为空，走 post- 兜底，断言同样成立
    expect($slug)->toBeString()
        ->and($slug)->not->toBe('')
        ->and(Str::length($slug))->toBeLessThanOrEqual(80)
        ->and($slug)->not->toEndWith('-');
});

it('纯中文标题也能生成非空 slug（拼音转写或 post- 兜底）', function () {
    $livewire = livewire(CreatePost::class)
        ->fillForm(['title' => '这是一篇纯中文标题的文章']);

    $slug = $livewire->instance()->data['slug'] ?? null;

    // 是否安装 intl 决定中文转写结果（有 = 拼音，无 = 空结果走兜底），两种环境都必须非空
    expect($slug)->toBeString()
        ->and($slug)->not->toBe('');
});

it('无法转写的标题兜底生成 post- 随机串', function () {
    $livewire = livewire(CreatePost::class)
        ->fillForm(['title' => '!!!###']);

    $slug = $livewire->instance()->data['slug'] ?? null;

    expect($slug)->toStartWith('post-')
        ->and(Str::length($slug))->toBe(13);
});

it('创建文章时自动生成的 slug 入库且通过唯一校验', function () {
    livewire(CreatePost::class)
        ->fillForm([
            'title' => '这是一篇纯中文标题的文章',
            'post_image' => [UploadedFile::fake()->image('cover.jpg')],
            'scheduledTasks' => [],
            'content.content_richtext' => '<p>正文</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::where('title', '这是一篇纯中文标题的文章')->first();

    expect($post)->not->toBeNull()
        ->and($post->slug)->not->toBe('');
});
