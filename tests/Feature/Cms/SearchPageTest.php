<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Facades\Search;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');

    // 容器页头部导航依赖 navigation type，缺失时会 404
    NavigationType::create([
        'name' => 'Cms',
        'level' => 2,
        'status' => NavigationTypeStatus::Normal,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);

    // 搜索来源注册时 with('categories') 预加载分类标签，测试库需补建 category 包的表（迁移未发布到应用目录）
    foreach (glob(base_path('addons/category/database/migrations/*.php.stub')) ?: [] as $migrationFile) {
        (require $migrationFile)->up();
    }
});

function createCmsSearchPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '默认标题',
        'slug' => 'post-'.Str::random(8),
        'status' => 'published',
    ], $attributes));
}

it('cms 搜索结果页路由可访问并渲染核心结果组件', function () {
    $this->get('/cms/search')
        ->assertOk()
        ->assertSee('搜索结果')
        ->assertSee('输入关键词开始搜索');
});

it('cms 搜索结果页按地址栏 q 参数渲染搜索结果', function () {
    createCmsSearchPost(['title' => '页面集成搜索文章', 'slug' => 'page-search-post']);

    $this->get('/cms/search?q=页面集成')
        ->assertOk()
        ->assertSee('图文')
        ->assertSeeHtml('<mark class="sn-text-highlight">页面集成</mark>搜索文章')
        ->assertSee('cms/posts/page-search-post');
});

it('ServiceProvider 声明的结果页地址闭包接收关键词并返回完整 URL', function () {
    expect(Search::getConfig('sn-cms', 'page'))->toBeInstanceOf(Closure::class)
        ->and(Search::resolvePage('sn-cms'))->toBe(route('sn-cms.search'))
        ->and(Search::resolvePage('sn-cms', '关键词'))->toBe(route('sn-cms.search', ['q' => '关键词']));
});

it('搜索结果使用 cms 注册的 post 自定义条目视图', function () {
    $post = createCmsSearchPost(['title' => '自定义视图文章', 'slug' => 'custom-view-post']);

    $this->get('/cms/search?q=自定义视图')
        ->assertOk()
        ->assertSeeHtml('<mark class="sn-text-highlight">自定义视图</mark>文章')
        ->assertSee($post->updated_at->format('Y-m-d'))
        ->assertSee('cms/posts/custom-view-post');
});
