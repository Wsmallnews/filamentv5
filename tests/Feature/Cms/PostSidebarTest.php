<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Wsmallnews\Cms\Enums\PostStatus;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Facades\CompositionRegistry;
use Wsmallnews\Support\Models\Composition;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');

    // 侧栏标记组件：纯 ASCII 标记供 DOM 顺序断言；data-post-id 断言 pageContext 注入
    Livewire::component('test-sidebar-marker', new class extends Component
    {
        public array $extras = [];

        public ?Post $post = null;

        public function render(): string
        {
            return '<div data-sidebar-marker data-post-id="'.e($this->post?->getKey() ?? '').'">sidebar-marker</div>';
        }
    });

    CompositionRegistry::register('sn-cms', [
        'type' => 'sidebar-block',
        'label' => '侧栏块',
        'context' => ['post'],
        'components' => ['test-sidebar-marker' => []],
    ]);
});

function createSidebarPost(): Post
{
    $post = Post::create([
        'title' => '侧栏测试文章',
        'slug' => 'sidebar-post',
        'status' => PostStatus::Published,
        'published_at' => '2026-09-01 10:00:00',
        'publisher_type' => 'user',
        'publisher_id' => 1,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);
    $post->content()->create(['content_type' => ContentType::Richtext, 'content' => '<p>正文标记段落</p>']);

    return $post;
}

function createSidebarComposition(array $attributes = []): Composition
{
    return Composition::create(array_merge([
        'title' => '侧栏编排',
        'status' => CompositionStatus::Published,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'purpose' => 'post-sidebar',
        'components' => [
            ['layout' => 'full', 'left' => [['type' => 'sidebar-block', 'label' => '侧栏推荐', 'description' => null, 'extras' => []]], 'right' => []],
        ],
    ], $attributes));
}

it('未命中 post-sidebar 编排时详情页全宽回退（无侧栏标记）', function () {
    $post = createSidebarPost();

    $this->get('/cms/posts/'.$post->slug)
        ->assertOk()
        ->assertSee('正文标记段落', false)
        // 文章自身 SEO 不受影响（非嵌入，标题归文章）
        ->assertSee('侧栏测试文章')
        ->assertDontSeeHtml('data-sidebar-marker');
});

it('命中 post-sidebar 编排时渲染右侧栏并注入当前文章上下文', function () {
    $post = createSidebarPost();
    createSidebarComposition();

    $this->get('/cms/posts/'.$post->slug)
        ->assertOk()
        ->assertSee('侧栏推荐')
        ->assertSeeHtml('data-sidebar-marker')
        // pageContext 注入：context 消费组件拿到当前文章
        ->assertSeeHtml('data-post-id="'.$post->id.'"')
        // 右侧栏：正文在前、侧栏在后（DOM 源顺序）
        ->assertSeeInOrder(['正文标记段落', 'sidebar-marker'], false);
});

it('编排 options.position=left 时侧栏渲染在左侧（DOM 在前）', function () {
    $post = createSidebarPost();
    createSidebarComposition(['options' => ['position' => 'left']]);

    $this->get('/cms/posts/'.$post->slug)
        ->assertOk()
        ->assertSeeHtml('data-sidebar-marker')
        // 左侧栏：侧栏在前、正文在后
        ->assertSeeInOrder(['sidebar-marker', '正文标记段落'], false);
});

it('未命中编排时 SEO 与正文组件行为不变（侧栏为条件渲染）', function () {
    // 草稿侧栏编排不参与
    $post = createSidebarPost();
    createSidebarComposition(['status' => CompositionStatus::Draft]);

    $this->get('/cms/posts/'.$post->slug)
        ->assertOk()
        ->assertDontSeeHtml('data-sidebar-marker')
        ->assertSee('正文标记段落', false);
});

it('文章不存在时详情页 404（context 提供者返回空不阻塞渲染链）', function () {
    // 槽位编排存在，但 context 提供者按 slug 查不到文章 → null 剔除，正文组件 404 兜底
    createSidebarComposition();

    $this->get('/cms/posts/missing-slug')->assertNotFound();
});
