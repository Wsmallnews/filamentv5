<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Facades\CompositionRegistry;
use Wsmallnews\Support\Filament\Resources\Pages\Pages\CreatePage;
use Wsmallnews\Support\Filament\Resources\Pages\Pages\EditPage;
use Wsmallnews\Support\Filament\Resources\Pages\Pages\ListPages;
use Wsmallnews\Support\Models\Composition;
use Wsmallnews\Support\Models\Page;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');

    // 测试用假组件（渲染层不依赖 posts 组件的分类表）
    Livewire::component('test-page-dummy', new class extends Component
    {
        public function render(): string
        {
            return '<div data-test-dummy>dummy</div>';
        }
    });

    CompositionRegistry::register('sn-cms', [
        'type' => 'dummy-block',
        'label' => '假组件',
        'components' => ['test-page-dummy' => []],
    ]);
});

function createCompositionWithBlock(string $label): Composition
{
    return Composition::create([
        'title' => '页面编排',
        'status' => CompositionStatus::Published,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'components' => [
            [
                'layout' => 'full',
                'left' => [['type' => 'dummy-block', 'label' => $label, 'description' => null, 'extras' => []]],
                'right' => [],
            ],
        ],
    ]);
}

function createPage(array $attributes = []): Page
{
    return Page::create(array_merge([
        'title' => '关于我们',
        'slug' => 'about-us',
        'status' => PageStatus::Published,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ], $attributes));
}

/*
 * 后台管理（/admin/pages）
 */

function withPagesPanelContext(callable $callback): mixed
{
    // 资源经 config panel_register 注册，测试中需显式进入 admin 面板 + 默认资源配置上下文
    $previousPanel = Filament::getCurrentPanel();
    $previousKey = Filament::getCurrentResourceConfigurationKey();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setCurrentResourceConfigurationKey('default');

    try {
        return $callback();
    } finally {
        Filament::setCurrentPanel($previousPanel);
        Filament::setCurrentResourceConfigurationKey($previousKey);
    }
}

it('后台页面列表按 scope 隔离展示', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    createPage();
    createPage(['slug' => 'other-scope', 'scope_type' => 'sn-shop']);

    withPagesPanelContext(fn () => livewire(ListPages::class)
        ->assertCanSeeTableRecords(Page::where('scope_type', 'sn-cms')->get())
        ->assertCanNotSeeTableRecords(Page::where('scope_type', 'sn-shop')->get()));
});

it('后台创建页面：保存表单并自动写入 scopeable', function () {
    $this->actingAs(User::factory()->create(), 'admin');
    $composition = createCompositionWithBlock('创建测试块');

    withPagesPanelContext(fn () => livewire(CreatePage::class)
        ->fillForm([
            'title' => '新页面',
            'slug' => 'new-page',
            'composition_id' => $composition->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect());

    assertDatabaseHas(Page::class, [
        'title' => '新页面',
        'slug' => 'new-page',
        'composition_id' => $composition->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);
});

it('后台创建页面：同 scope 重复 slug 被拦截（软删除行不占位）', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    // 存活的同 slug 行 + 软删除的同 slug 行：前者拦截、后者不占位
    createPage(['slug' => 'dup-slug']);
    createPage(['slug' => 'dup-slug', 'title' => '已删除页'])->delete();

    withPagesPanelContext(fn () => livewire(CreatePage::class)
        ->fillForm(['title' => '重复页', 'slug' => 'dup-slug'])
        ->call('create')
        // scopedUnique 为 Closure 规则，错误 bag 不带 unique 规则名，只断字段级错误
        ->assertHasFormErrors(['slug']));

    expect(Page::where('slug', 'dup-slug')->count())->toBe(1);
});

it('后台创建页面：不同 scope 的相同 slug 不受影响', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    // sn-shop scope 已有相同 slug，sn-cms scope 下创建不应被误拦
    createPage(['slug' => 'cross-scope', 'scope_type' => 'sn-shop']);

    withPagesPanelContext(fn () => livewire(CreatePage::class)
        ->fillForm(['title' => '跨scope页', 'slug' => 'cross-scope'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect());

    assertDatabaseHas(Page::class, ['slug' => 'cross-scope', 'scope_type' => 'sn-cms']);
    assertDatabaseHas(Page::class, ['slug' => 'cross-scope', 'scope_type' => 'sn-shop']);
});

it('后台编辑页面：保留自身 slug 不触发唯一校验', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    $page = createPage(['slug' => 'keep-slug']);
    createPage(['slug' => 'other-slug', 'title' => '另一页']);

    withPagesPanelContext(fn () => livewire(EditPage::class, ['record' => $page->id])
        ->fillForm(['title' => '改标题不改slug'])
        ->call('save')
        ->assertHasNoFormErrors());

    assertDatabaseHas(Page::class, ['id' => $page->id, 'title' => '改标题不改slug', 'slug' => 'keep-slug']);
});

/*

 * Page 模型机制

 */

it('resolveRows 渲染绑定编排的行，未绑定与绑定草稿均回退空', function () {
    $composition = createCompositionWithBlock('页面块头');

    $page = createPage(['composition_id' => $composition->id]);
    $rows = $page->resolveRows('sn-cms');
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['left'][0]['extras']['componentInfo']['label'])->toBe('页面块头')
        // 编排块统一带 embedded 标记：块内组件据此让渡页面级职责（如 SEO）
        ->and($rows[0]['left'][0]['extras']['embedded'])->toBeTrue();

    // 未绑定
    expect(createPage(['slug' => 'no-binding'])->resolveRows('sn-cms'))->toBe([]);

    // 绑定的编排为草稿
    $composition->update(['status' => CompositionStatus::Draft]);
    expect($page->resolveRows('sn-cms'))->toBe([]);
});

it('resolveRows 透传 pageContext 供行内组件消费', function () {
    $composition = createCompositionWithBlock('上下文页');
    $page = createPage(['slug' => 'ctx-page', 'composition_id' => $composition->id]);

    // 无消费者时透传无副作用；核心断言是机制不报错且行为与 CompositionRenderer 一致
    $rows = $page->resolveRows('sn-cms', ['post' => null]);
    expect($rows)->toHaveCount(1);
});

/*

 * 前台路由（/cms/pages/{slug}）

 */

it('页面路由渲染绑定编排的块头', function () {
    $composition = createCompositionWithBlock('关于我们块头');
    createPage(['composition_id' => $composition->id]);

    $this->get('/cms/pages/about-us')
        ->assertOk()
        ->assertSee('关于我们块头');
});

it('页面不存在或未发布返回 404', function () {
    $this->get('/cms/pages/not-exists')->assertNotFound();

    createPage(['status' => PageStatus::Draft]);
    $this->get('/cms/pages/about-us')->assertNotFound();

    // scope 隔离：其他模块 scope 的同 slug 页面不可见
    createPage(['slug' => 'shop-page', 'scope_type' => 'sn-shop']);
    $this->get('/cms/pages/shop-page')->assertNotFound();
});

it('未绑定编排的页面渲染空态提示', function () {
    createPage();

    $this->get('/cms/pages/about-us')
        ->assertOk()
        ->assertSee(__('sn-support::page.frontend.empty'), false);
});

it('未绑定编排且带内容的页面渲染自有内容', function () {
    $page = createPage();
    $page->content()->create([
        'content_type' => ContentType::Richtext,
        'content' => '<p>关于我们的正文内容</p>',
    ]);

    $this->get('/cms/pages/about-us')
        ->assertOk()
        ->assertSee('关于我们的正文内容', false);
});

it('内容通道与编排互斥：绑定编排后渲染编排且忽略自有内容', function () {
    $composition = createCompositionWithBlock('编排优先块头');
    $page = createPage(['composition_id' => $composition->id]);
    $page->content()->create([
        'content_type' => ContentType::Richtext,
        'content' => '<p>被编排忽略的内容</p>',
    ]);

    $response = $this->get('/cms/pages/about-us');
    $response->assertOk()
        ->assertSee('编排优先块头')
        ->assertDontSee('被编排忽略的内容', false);
});

it('编辑页绑定编排保存后不删除已有内容记录', function () {
    $this->actingAs(User::factory()->create(), 'admin');
    $composition = createCompositionWithBlock('后绑编排');

    $page = createPage();
    $content = $page->content()->create([
        'content_type' => ContentType::Richtext,
        'content' => '<p>暂存的页面内容</p>',
    ]);

    withPagesPanelContext(fn () => livewire(EditPage::class, ['record' => $page->id])
        ->fillForm(['composition_id' => $composition->id])
        ->call('save')
        ->assertHasNoFormErrors());

    expect($page->content()->first())->not->toBeNull()
        ->and($page->content()->first()->id)->toBe($content->id)
        ->and($page->fresh()->composition_id)->toBe($composition->id);
});

it('嵌入编排块的文章组件不覆盖页面 SEO 标题', function () {
    $admin = User::factory()->create();
    $post = Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'title' => '块内文章标题',
        'status' => 'published',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);

    $composition = Composition::create([
        'title' => '文章页编排',
        'status' => CompositionStatus::Published,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'components' => [
            [
                'layout' => 'full',
                'left' => [['type' => 'post-detail', 'extras' => ['id' => $post->id]]],
                'right' => [],
            ],
        ],
    ]);
    createPage(['composition_id' => $composition->id]);

    $response = $this->get('/cms/pages/about-us');
    $response->assertOk()->assertSee('块内文章标题');

    preg_match('/<title>(.*?)<\/title>/s', $response->getContent(), $match);
    expect($match)->not->toBeEmpty()
        ->and($match[1])->toContain('关于我们')
        ->and($match[1])->not->toContain('块内文章标题');
});
