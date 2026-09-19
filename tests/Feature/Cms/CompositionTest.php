<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Livewire\Components\Post\Posts;
use Wsmallnews\Cms\Models\Navigation;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Cms\Support\Utils;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Facades\CompositionRegistry;
use Wsmallnews\Support\Features\Composition\CompositionRenderer;
use Wsmallnews\Support\Filament\Resources\Compositions\Pages\CreateComposition;
use Wsmallnews\Support\Filament\Resources\Compositions\Pages\EditComposition;
use Wsmallnews\Support\Filament\Resources\Compositions\Pages\ListCompositions;
use Wsmallnews\Support\Models\Composition;
use Wsmallnews\Support\Models\Page;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');

    // 页头导航类型（无限级）
    $this->typeId = NavigationType::create([
        'name' => 'Cms',
        'level' => 0,
        'status' => NavigationTypeStatus::Normal,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ])->id;

    // 测试用假组件（渲染层不依赖 posts 组件的分类表）
    Livewire::component('test-composition-dummy', new class extends Component
    {
        public function render(): string
        {
            return '<div data-test-dummy>dummy</div>';
        }
    });
    // 捕获注册表 forms 闭包收到的 fields 参数（验证注入的 $livewire->data 与整表根状态一致）
    $GLOBALS['test_captured_fields'] = null;

    CompositionRegistry::register('sn-cms', [
        'type' => 'dummy-block',
        'label' => '假组件',
        // 带 forms 以覆盖 extras 动态表单路径（经注入的 Livewire 组件读取整表根状态）
        'forms' => function ($fields) {
            $GLOBALS['test_captured_fields'] = $fields;

            return [
                TextInput::make('limit')->label('条数'),
            ];
        },
        'components' => ['test-composition-dummy' => []],
    ]);
});

/**
 * 创建一条内容编排（默认已发布，挂在模块主 scope）
 */
function createComposition(array $attributes = []): Composition
{
    return Composition::create(array_merge([
        'title' => '示例编排',
        'status' => CompositionStatus::Published,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'components' => [],
    ], $attributes));
}

/**
 * 创建一条内容型导航
 */
function createPageNav(string $name, array $attributes = [], ?int $pageId = null): Navigation
{
    return Navigation::create(array_merge([
        'name' => $name,
        'type' => 'page',
        'status' => 'normal',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'type_id' => test()->typeId,
        'page_id' => $pageId,
        'options' => [],
    ], $attributes));
}

/**
 * 建一个绑编排的 Page（首页/页面节点引用用）
 */
function createBoundPage(string $title, int $compositionId, array $attributes = []): Page
{
    return Page::create(array_merge([
        'title' => $title,
        'slug' => 'page-'.Str::slug($title).'-'.uniqid(),
        'composition_id' => $compositionId,
        'status' => PageStatus::Published,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ], $attributes));
}

/**
 * 标准单行通栏编排数据（dummy-block 为测试注册的真实可渲染组件类型）
 */
function singleRowComponents(string $label = '文章区块', string $description = '区块描述文字'): array
{
    return [
        [
            'layout' => 'full',
            'left' => [
                ['type' => 'dummy-block', 'label' => $label, 'description' => $description, 'extras' => []],
            ],
            'right' => [],
        ],
    ];
}

/*

 * CompositionRenderer

 */

it('resolveRows 解析注册组件并透传标题描述与固定参数', function () {
    $rows = [
        [
            'layout' => '1-2',
            'left' => [
                ['type' => 'dummy-block', 'label' => '最新动态', 'description' => '站点公告', 'extras' => []],
            ],
            'right' => [
                ['type' => 'posts', 'label' => null, 'description' => null, 'extras' => []],
            ],
        ],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms');

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]['layout'])->toBe('1-2')
        ->and($resolved[0]['left'])->toHaveCount(1)
        ->and($resolved[0]['right'])->toHaveCount(1);

    $left = $resolved[0]['left'][0];
    expect($left['component_name'])->toBe('test-composition-dummy')
        ->and($left['extras']['componentInfo'])->toBe([
            'type' => 'dummy-block',
            'label' => '最新动态',
            'description' => '站点公告',
            'show_header' => true,
        ])
        ->and($left['extras']['contained'])->toBeTrue();

    // posts 为包内真实注册类型：组件名解析 + 固定参数（scopeType/scopeId）保留
    $right = $resolved[0]['right'][0];
    expect($right['component_name'])->toBe(Posts::class)
        ->and($right['extras']['scopeType'])->toBe('sn-cms');
});

it('条目开关：show_header/contained 关闭后透传 false，缺省回退 true', function () {
    $rows = [
        [
            'layout' => 'full',
            'left' => [
                ['type' => 'dummy-block', 'label' => '隐藏块头', 'description' => null, 'show_header' => false, 'contained' => false, 'extras' => []],
            ],
            'right' => [],
        ],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms');

    $block = $resolved[0]['left'][0];
    expect($block['extras']['componentInfo']['show_header'])->toBeFalse()
        ->and($block['extras']['contained'])->toBeFalse();
});

it('切换组件类型后 label 回填且 extras 初始化为关联数组（保存链路前置保证）', function () {
    // 起步用无表单的 dummy-block，避免 posts 的 SelectTree 在初始渲染查询分类表（测试库无该 stub 迁移）
    $composition = createComposition(['components' => [
        [
            'layout' => 'full',
            'left' => [['type' => 'dummy-block', 'label' => '占位', 'description' => null, 'extras' => []]],
            'right' => [],
        ],
    ]]);

    // 整个交互（含 fillForm 触发的 afterStateUpdated）都要在资源配置上下文内——闭包外调用会丢 moduleId，label/extras 不生效
    $item = withCompositionConfiguration(function () use ($composition) {
        $livewire = livewire(EditComposition::class, ['record' => $composition->id]);

        // repeater hydrate 会重新生成条目 uuid，从实例读实际路径；fillForm 走字段更新生命周期（触发 afterStateUpdated）
        $instance = $livewire->instance();
        $rowUuid = array_key_first($instance->data['components']);
        $itemUuid = array_key_first($instance->data['components'][$rowUuid]['left']);

        $livewire->fillForm([
            "components.{$rowUuid}.left.{$itemUuid}.type" => 'post-detail',
        ]);

        return $livewire->instance()->data['components'][$rowUuid]['left'][$itemUuid];
    });

    expect($item['type'])->toBe('post-detail')
        ->and($item['label'])->toBe(__('sn-cms::cms.content_type.post_detail'))
        // extras 必须是含注册表单字段键的关联数组：空索引数组会让前端 entangle 的字符串键在序列化时丢失
        ->and($item['extras'])->toBe(['id' => null]);
});

it('resolveRows 跳过未注册组件类型且未知布局回退通栏', function () {
    $rows = [
        [
            'layout' => 'unknown-layout',
            'left' => [
                ['type' => 'not-registered', 'label' => '幽灵', 'extras' => []],
                ['type' => 'dummy-block', 'label' => '有效', 'extras' => []],
            ],
            'right' => [],
        ],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms');

    expect($resolved[0]['layout'])->toBe(CompositionRenderer::LAYOUT_FULL)
        ->and($resolved[0]['left'])->toHaveCount(1)
        ->and($resolved[0]['left'][0]['extras']['componentInfo']['label'])->toBe('有效');
});

it('resolveRows 空数据返回空数组', function () {
    expect(CompositionRenderer::resolveRows(null, 'sn-cms'))->toBe([])
        ->and(CompositionRenderer::resolveRows([], 'sn-cms'))->toBe([]);
});

/*

 * 构建期上下文注入（provides / context 元数据）

 */

/**
 * 注册一对上下文提供者/消费者假类型（同一注册名重复注册会覆盖，测试间互不污染）
 */
function registerContextDummyTypes(): void
{
    CompositionRegistry::register('sn-cms', [
        'type' => 'ctx-provider',
        'label' => '提供者',
        'provides' => fn (array $extras) => ['post' => $extras['payload'] ?? null],
        'components' => ['test-composition-dummy' => []],
    ]);
    CompositionRegistry::register('sn-cms', [
        'type' => 'ctx-consumer',
        'label' => '消费者',
        'context' => ['post'],
        'components' => ['test-composition-dummy' => []],
    ]);
}

it('上下文注入：同行左槽提供，右槽消费者自动补齐缺失键', function () {
    registerContextDummyTypes();

    $rows = [
        [
            'layout' => '1-2',
            'left' => [['type' => 'ctx-provider', 'extras' => ['payload' => 'post-42']]],
            'right' => [['type' => 'ctx-consumer', 'extras' => []]],
        ],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms');

    expect($resolved[0]['left'][0]['extras'])->not->toHaveKey('post')
        ->and($resolved[0]['right'][0]['extras']['post'])->toBe('post-42');
});

it('extras 显式配置优先，不被上下文覆盖', function () {
    registerContextDummyTypes();

    $rows = [
        [
            'layout' => '2-1',
            'left' => [['type' => 'ctx-provider', 'extras' => ['payload' => 'ctx-value']]],
            'right' => [['type' => 'ctx-consumer', 'extras' => ['post' => 'own-value']]],
        ],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms');

    expect($resolved[0]['right'][0]['extras']['post'])->toBe('own-value');
});

it('上下文跨行隔离：第二行拿不到第一行的提供物', function () {
    registerContextDummyTypes();

    $rows = [
        ['layout' => 'full', 'left' => [['type' => 'ctx-provider', 'extras' => ['payload' => 'row-1']]], 'right' => []],
        ['layout' => 'full', 'left' => [['type' => 'ctx-consumer', 'extras' => []]], 'right' => []],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms');

    expect($resolved[1]['left'][0]['extras'])->not->toHaveKey('post');
});

it('pageContext 种子每行可用', function () {
    registerContextDummyTypes();

    $rows = [
        ['layout' => 'full', 'left' => [['type' => 'ctx-consumer', 'extras' => []]], 'right' => []],
        ['layout' => 'full', 'left' => [['type' => 'ctx-consumer', 'extras' => []]], 'right' => []],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms', ['post' => 'page-seed']);

    expect($resolved[0]['left'][0]['extras']['post'])->toBe('page-seed')
        ->and($resolved[1]['left'][0]['extras']['post'])->toBe('page-seed');
});

it('provides 空产物安全，未声明 context 的组件不注入', function () {
    CompositionRegistry::register('sn-cms', [
        'type' => 'ctx-provider',
        'label' => '提供者',
        'provides' => fn (array $extras) => [],
        'components' => ['test-composition-dummy' => []],
    ]);

    $rows = [
        ['layout' => 'full', 'left' => [['type' => 'ctx-provider', 'extras' => []]], 'right' => []],
    ];

    // 无异常 + dummy-block（未声明 context）不收到任何注入
    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms', ['post' => 'seed']);

    expect($resolved[0]['left'][0]['extras'])->not->toHaveKey('post');
});

it('post-detail 经 provides 提供真实文章模型，供同行消费者注入', function () {
    $admin = User::factory()->create();
    $post = Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'title' => '上下文源文章',
        'status' => 'published',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);

    registerContextDummyTypes();

    $rows = [
        [
            'layout' => '1-2',
            'left' => [['type' => 'post-detail', 'extras' => ['id' => $post->id]]],
            'right' => [['type' => 'ctx-consumer', 'extras' => []]],
        ],
    ];

    $resolved = CompositionRenderer::resolveRows($rows, 'sn-cms');

    $injected = $resolved[0]['right'][0]['extras']['post'];
    expect($injected)->toBeInstanceOf(Post::class)
        ->and($injected->id)->toBe($post->id);
});

it('related-posts 无来源时渲染空态提示', function () {
    livewire('sn-cms::components.post.related-posts')
        ->assertSuccessful()
        ->assertSee(__('sn-cms::cms.frontend.related_posts_no_source'), false);
});

it('related-posts 视图无 block-link 组件残留', function () {
    livewire('sn-cms::components.post.related-posts')
        ->assertSuccessful()
        ->assertSee(__('sn-cms::cms.frontend.related_posts_no_source'), false)
        ->assertDontSeeHtml('sn-cms-container-block-link');
});

it('index-posts 列表渲染 sn-link 裸链接行', function () {
    $admin = User::factory()->create();
    Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'title' => '轮播文章一',
        'slug' => 'index-post-1',
        'status' => 'published',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);
    Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'title' => '轮播文章二',
        'slug' => 'index-post-2',
        'status' => 'published',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);

    livewire('sn-cms::components.post.index-posts', ['limit' => 2, 'scopeType' => 'sn-cms'])
        ->assertSuccessful()
        ->assertSeeHtml('class="sn-link flex w-full h-28')
        ->assertDontSeeHtml('sn-cms-container-block-link');
});

/*

 * 首页解析链（is_home 导航 → Composition → 行渲染）

 */

it('首页未绑定编排时渲染空状态提示', function () {
    $this->get('/cms')
        ->assertOk()
        ->assertSee(__('sn-cms::cms.frontend.home_empty'), false);
});

it('is_home 节点经 Page 绑定编排后首页按编排渲染块头', function () {
    createComposition(['title' => '首页编排', 'components' => singleRowComponents('今日头条', '编辑精选内容')]);
    $page = createBoundPage('首页内容', Composition::first()->id);
    createPageNav('首页', ['options' => ['is_home' => true]], $page->id);

    $this->get('/cms')
        ->assertOk()
        ->assertSee('今日头条')
        ->assertSee('编辑精选内容')
        ->assertDontSee(__('sn-cms::cms.frontend.home_empty'), false);
});

it('条目关闭 show_header 后首页不渲染块头（标题仅在后台使用）', function () {
    $rows = [
        [
            'layout' => 'full',
            'left' => [
                ['type' => 'dummy-block', 'label' => '仅后台标题', 'description' => '仅后台描述', 'show_header' => false, 'extras' => []],
            ],
            'right' => [],
        ],
    ];
    createComposition(['title' => '首页编排', 'components' => $rows]);
    $page = createBoundPage('首页内容', Composition::first()->id);
    createPageNav('首页', ['options' => ['is_home' => true]], $page->id);

    $this->get('/cms')
        ->assertOk()
        ->assertDontSee('仅后台标题')
        ->assertDontSee('仅后台描述')
        ->assertDontSee(__('sn-cms::cms.frontend.home_empty'), false);
});

it('is_home 节点绑定的 Page 为草稿或编排失效时回退空状态', function () {
    // Page 绑定草稿编排：resolveRows 回退空，页面无自有内容 → 渲染页面空态（page-content 组件文案）
    $draft = createComposition(['status' => CompositionStatus::Draft, 'components' => singleRowComponents()]);
    $page = createBoundPage('草稿首页', $draft->id);
    createPageNav('首页', ['options' => ['is_home' => true]], $page->id);

    $this->get('/cms')->assertOk()->assertSee(__('sn-support::page.frontend.empty'), false);

    // Page 为草稿（首页链路第二环失效）→ 外层首页空态
    $draftPage = createBoundPage('草稿页面', createComposition(['components' => singleRowComponents()])->id, ['status' => PageStatus::Draft]);
    createPageNav('首页失效', ['options' => ['is_home' => true]], $draftPage->id);
    $this->get('/cms')->assertOk()->assertSee(__('sn-cms::cms.frontend.home_empty'), false);
});

it('is_home 节点入口指向模块首页地址', function () {
    $nav = createPageNav('首页', ['options' => ['is_home' => true]]);

    expect($nav->url_info['url'])->toBe(Utils::route('index'));
});

it('Page 型节点入口指向页面规范地址，未绑定页面为空链接', function () {
    $composition = createComposition(['components' => singleRowComponents('页面块头')]);
    $page = createBoundPage('关于我们页', $composition->id);
    $nav = createPageNav('关于我们', [], $page->id);

    expect($nav->url_info['url'])->toBe(Utils::route('pages.show', $page->slug));

    // 未绑定 page_id（配置未完成）
    $unbound = createPageNav('未完成节点');
    expect($unbound->url_info['url'])->toBe('#');
});

it('is_home 标记在同 scope 内互斥', function () {
    $first = createPageNav('首页一', ['options' => ['is_home' => true]]);
    $second = createPageNav('首页二', ['options' => ['is_home' => true]]);

    expect($first->refresh()->options['is_home'])->toBeFalse()
        ->and($second->refresh()->options['is_home'])->toBeTrue();
});

it('is_home 标记互斥限定在同一 scope 内', function () {
    $footerNav = createPageNav('底部首页', ['scope_type' => 'sn-cms-footer', 'options' => ['is_home' => true]]);

    // 主 scope 置位不影响 footer scope 的标记
    createPageNav('主首页', ['options' => ['is_home' => true]]);

    expect($footerNav->refresh()->options['is_home'])->toBeTrue();
});

it('is_home 标记互斥限定在同一导航类型（type_id）内', function () {
    // 同 scope 下第二棵导航树（不同 type_id）
    $otherTypeId = NavigationType::create([
        'name' => 'Other',
        'level' => 0,
        'status' => NavigationTypeStatus::Normal,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ])->id;

    $first = createPageNav('树一首页', ['options' => ['is_home' => true]]);

    // 不同 type_id 置位首页，不清掉第一棵树的标记
    $second = createPageNav('树二首页', ['type_id' => $otherTypeId, 'options' => ['is_home' => true]]);

    expect($first->refresh()->options['is_home'])->toBeTrue()
        ->and($second->refresh()->options['is_home'])->toBeTrue();

    // 同 type_id 内仍然互斥
    createPageNav('树二新首页', ['type_id' => $otherTypeId, 'options' => ['is_home' => true]]);

    expect($second->refresh()->options['is_home'])->toBeFalse();
});

it('is_home 节点无论提交何种类型，模型保存时强制为页面类型', function () {
    // 模拟表单异常/绕过 UI 直接写入：类型兜底由模型 saving 钩子保证
    $nav = createPageNav('兜底首页', ['type' => 'url', 'options' => ['is_home' => true, 'url' => 'https://example.com']]);

    expect($nav->refresh()->type)->toBe(Wsmallnews\Cms\Enums\NavigationType::Page);
});

it('退位的旧首页节点失去首页地址，按绑定回到页面规范地址', function () {
    $composition = createComposition(['components' => singleRowComponents()]);
    $page = createBoundPage('旧首页内容', $composition->id);
    $first = createPageNav('旧首页', ['options' => ['is_home' => true]], $page->id);

    createPageNav('新首页', ['options' => ['is_home' => true]], $page->id);

    $first->refresh();
    expect($first->options['is_home'])->toBeFalse()
        ->and($first->url_info['url'])->toBe(Utils::route('pages.show', $page->slug));
});

it('首页节点在后台树列表带首页标记，前台导航不带', function () {
    $nav = createPageNav('标记入口', ['options' => ['is_home' => true]]);

    // 前台语境（无当前面板）：name_label 不带标记
    expect($nav->name_label->toHtml())->not->toContain(__('sn-cms::cms.navigation_form.home_badge'));

    // 后台语境（面板内）：重新取实例断言（规避同一实例的 accessor 结果缓存），name_label 带首页标记
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    try {
        $panelNav = Navigation::find($nav->id);

        expect($panelNav->name_label->toHtml())->toContain(__('sn-cms::cms.navigation_form.home_badge'));
    } finally {
        Filament::setCurrentPanel(null);
    }
});

/*

 * 导航 content 节点引用编排渲染（前台入口 /cms/navigation/{slug} 已随 Page 实体化下线，
 * 编排渲染/空态的等价断言见 PageTest「页面路由渲染绑定编排的块头」等用例；
 * 导航收敛 P2 落地后 Content 类型整体移除）

 */

/**
 * 在编排资源配置上下文中执行回调。
 * livewire() 直连页面组件不经 resource-configuration 路由中间件（也不解析当前 panel），
 * 需显式进入配置上下文；生产环境由路由中间件承担
 */
function withCompositionConfiguration(callable $callback): mixed
{
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

/*

 * 后台 CompositionResource

 */

it('后台内容编排列表与创建页可访问', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    createComposition(['title' => '已有编排']);

    $this->get('/admin/compositions')->assertOk();
    $this->get('/admin/compositions/create')->assertOk();
});

it('后台创建编排：purpose 槽位与位置保存成功，留空默认通用编排', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    // 选槽位 + 位置（Registry 白名单来自 cms 模块注册）
    withCompositionConfiguration(fn () => livewire(CreateComposition::class)
        ->fillForm([
            'title' => '侧栏槽位编排',
            'status' => CompositionStatus::Published->value,
            'purpose' => 'post-sidebar',
            'options.position' => 'left',
        ])
        ->call('create')
        ->assertHasNoFormErrors());

    assertDatabaseHas(Composition::class, [
        'title' => '侧栏槽位编排',
        'purpose' => 'post-sidebar',
        'options' => json_encode(['position' => 'left']),
    ]);

    // 留空 purpose = 通用展示编排（默认态）
    withCompositionConfiguration(fn () => livewire(CreateComposition::class)
        ->fillForm([
            'title' => '通用编排表单创建',
            'status' => CompositionStatus::Published->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors());

    assertDatabaseHas(Composition::class, [
        'title' => '通用编排表单创建',
        'purpose' => null,
    ]);
});

it('后台可创建内容编排并进入编辑页', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    withCompositionConfiguration(fn () => livewire(CreateComposition::class)
        ->fillForm([
            'title' => '新建编排',
            'status' => CompositionStatus::Published->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors());

    assertDatabaseHas(Composition::class, [
        'title' => '新建编排',
        'scope_type' => 'sn-cms',
    ]);
    $composition = Composition::where('title', '新建编排')->first();
    // 挂上组件行后再渲染编辑页：覆盖 extras 动态表单（注册表 forms 经 $livewire->data 读根状态）
    $composition->update(['components' => singleRowComponents('编辑回显', '行式数据')]);

    $testable = withCompositionConfiguration(fn () => livewire(EditComposition::class, ['record' => $composition->id])
        ->assertSuccessful()
        // 行式布局数据在编辑页回显
        ->assertFormFieldExists('components'));

    // 注册表 forms 闭包收到的 fields 与 Livewire 组件整表根状态完全一致
    // （$livewire->data 即表单根数据；旧的 '../../../../../' 深层相对路径在双层 Repeater 下会越过根返回 null）
    expect($GLOBALS['test_captured_fields'])->toBeArray()
        ->and($GLOBALS['test_captured_fields'])->toEqual($testable->instance()->data);
});

it('编排 scope 隔离：其他范围的编排不进入列表', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    createComposition(['title' => '本范围编排', 'scope_id' => 0]);
    createComposition(['title' => '其他范围编排', 'scope_id' => 5]);

    withCompositionConfiguration(fn () => livewire(ListCompositions::class)
        ->assertCanSeeTableRecords(Composition::where('scope_id', 0)->get())
        ->assertCanNotSeeTableRecords(Composition::where('scope_id', 5)->get()));
});

/*

 * purpose 槽位（resolveForPurpose）

 */

it('resolveForPurpose 命中已发布编排并解析组件行（默认右侧栏）', function () {
    createComposition(['title' => '侧栏编排', 'purpose' => 'post-sidebar', 'components' => singleRowComponents('侧栏块')]);

    $result = CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0);

    expect($result)->not->toBeNull()
        ->and($result['position'])->toBe('right')
        ->and($result['rows'])->toHaveCount(1)
        ->and($result['rows'][0]['left'][0]['component_name'])->toBe('test-composition-dummy')
        ->and($result['rows'][0]['left'][0]['extras']['componentInfo']['label'])->toBe('侧栏块');
});

it('resolveForPurpose 读取编排 options.position，非法值回退 right', function () {
    createComposition(['title' => '左侧栏', 'purpose' => 'post-sidebar', 'options' => ['position' => 'left'], 'components' => singleRowComponents('左块')]);
    expect(CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0)['position'])->toBe('left');

    // 命中取 order_column 最前（desc），给非法位置的编排更高序号使其胜出
    createComposition(['title' => '非法位置', 'purpose' => 'post-sidebar', 'order_column' => 99, 'options' => ['position' => 'center'], 'components' => singleRowComponents('块')]);
    expect(CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0)['position'])->toBe('right');
});

it('resolveForPurpose 命中但编排行解析为空时回退 null', function () {
    createComposition(['title' => '空侧栏编排', 'purpose' => 'post-sidebar', 'components' => []]);

    expect(CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0))->toBeNull();
});

it('resolveForPurpose 未命中回退 null（无编排/通用编排/草稿/他 scope）', function () {
    // 无任何编排
    expect(CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0))->toBeNull();

    // 通用展示编排（purpose=null）不参与 purpose 匹配
    createComposition(['title' => '通用编排']);

    // 草稿不参与
    createComposition(['title' => '草稿侧栏', 'purpose' => 'post-sidebar', 'status' => CompositionStatus::Draft, 'components' => singleRowComponents('草稿侧栏块')]);

    // 他 scope 的同 purpose 编排不命中
    createComposition(['title' => '他scope侧栏', 'purpose' => 'post-sidebar', 'scope_type' => 'sn-shop']);

    expect(CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0))->toBeNull();

    // 发布本 scope 草稿后命中
    Composition::where('title', '草稿侧栏')->update(['status' => CompositionStatus::Published->value]);
    expect(CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0))->not->toBeNull();
});

it('resolveForPurpose 多条命中取 order_column 最大（与后台列表同序）', function () {
    createComposition(['title' => '旧侧栏', 'purpose' => 'post-sidebar', 'order_column' => 1, 'components' => singleRowComponents('旧块')]);
    createComposition(['title' => '新侧栏', 'purpose' => 'post-sidebar', 'order_column' => 2, 'components' => singleRowComponents('新块')]);

    $result = CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0);

    expect($result['rows'][0]['left'][0]['extras']['componentInfo']['label'])->toBe('新块');
});

it('resolveForPurpose 经槽位 context 提供者注入页面级上下文（null 未命中剔除）', function () {
    CompositionRegistry::register('sn-cms', [
        'type' => 'ctx-block',
        'label' => '上下文块',
        'context' => ['post'],
        'components' => ['test-composition-dummy' => []],
    ]);

    createComposition(['title' => '上下文侧栏', 'purpose' => 'post-sidebar', 'components' => [
        ['layout' => 'full', 'left' => [['type' => 'ctx-block', 'label' => '上下文块', 'description' => null, 'extras' => []]], 'right' => []],
    ]]);

    // 覆盖槽位 meta 的 context 提供者：params/scopeable 均可注入，null 值剔除
    CompositionRegistry::registerPurposes('sn-cms', [
        'post-sidebar' => [
            'label' => '上下文侧栏',
            'positions' => ['left', 'right'],
            'context' => fn (array $params, array $scopeable): array => [
                // params 与 scopeable 拼接进同一消费键，同时验证两个注入
                'post' => ($params['slug'] ?? '').'@'.$scopeable['scope_type'],
                'missing' => null,
            ],
        ],
    ]);

    $result = CompositionRenderer::resolveForPurpose('post-sidebar', 'sn-cms', 'sn-cms', 0, ['slug' => 'post-token']);

    expect($result)->not->toBeNull()
        ->and($result['rows'][0]['left'][0]['extras']['post'])->toBe('post-token@sn-cms')
        // null 上下文未注入（键不存在，消费者 extras 显式配置优先逻辑不受影响）
        ->and(array_key_exists('missing', $result['rows'][0]['left'][0]['extras']))->toBeFalse();
});

it('purpose 槽位注册：meta 归一化 + 标签覆盖合并 + 模块隔离', function () {
    // cms 已在 ServiceProvider 注册 post-sidebar（label 闭包形态，getPurpose 消费时求值）
    $meta = CompositionRegistry::getPurpose('sn-cms', 'post-sidebar');
    expect($meta)->not->toBeNull()
        ->and($meta['label'])->not->toBeEmpty()
        // 标准位置 label 由 Registry 自动生成为翻译键闭包，getPurpose 消费时求值（无 boot 顺序竞态）
        ->and($meta['positions'])->toBe(['left' => '左侧', 'right' => '右侧'])
        ->and($meta['default'])->toBe('right')
        ->and($meta['context'])->toBeInstanceOf(Closure::class);

    // 字符串形态重复注册 = 仅覆盖标签（保留既有 positions/default/context）
    CompositionRegistry::registerPurposes('sn-cms', ['post-sidebar' => '覆盖标签']);
    expect(CompositionRegistry::getPurpose('sn-cms', 'post-sidebar')['label'])->toBe('覆盖标签')
        ->and(CompositionRegistry::getPurpose('sn-cms', 'post-sidebar')['context'])->not->toBeNull();

    // 数组形态扩充新槽位：混合位置形态（标准值 + 自定义 键=>标签），default 未声明取首个
    CompositionRegistry::registerPurposes('sn-cms', [
        'home-hero' => ['label' => '首页横幅', 'positions' => ['top', 'spotlight' => '聚光灯位'], 'default' => 'top'],
    ]);
    $hero = CompositionRegistry::getPurpose('sn-cms', 'home-hero');
    expect($hero['positions'])->toBe(['top' => '顶部', 'spotlight' => '聚光灯位'])
        ->and($hero['default'])->toBe('top')
        // 未注册 purpose 的模块拿到空集（如纯 support 场景不会被 cms 语义污染）
        ->and(CompositionRegistry::getPurposes('sn-shop')->isEmpty())->toBeTrue();
});

it('槽位编辑布局模式：左右堆叠、上下行式、meta 显式声明优先', function () {
    // post-sidebar 未声明 layout_mode：按位置语义推导
    expect(CompositionRegistry::getLayoutMode('sn-cms', 'post-sidebar', 'left'))->toBe(CompositionRenderer::LAYOUT_MODE_STACK)
        ->and(CompositionRegistry::getLayoutMode('sn-cms', 'post-sidebar', 'right'))->toBe(CompositionRenderer::LAYOUT_MODE_STACK)
        ->and(CompositionRegistry::getLayoutMode('sn-cms', 'post-sidebar', 'top'))->toBe(CompositionRenderer::LAYOUT_MODE_ROWS)
        // 无槽位（通用编排）恒为行式
        ->and(CompositionRegistry::getLayoutMode('sn-cms', null, 'left'))->toBe(CompositionRenderer::LAYOUT_MODE_ROWS)
        ->and(CompositionRegistry::getLayoutMode('sn-cms', null, null))->toBe(CompositionRenderer::LAYOUT_MODE_ROWS);

    // meta 显式声明 layout_mode 优先于位置推导（自定义位置的兜底手段）
    CompositionRegistry::registerPurposes('sn-cms', [
        'wide-hero' => ['label' => '宽幅位', 'positions' => ['top', 'bottom'], 'default' => 'top', 'layout_mode' => 'stack'],
    ]);
    expect(CompositionRegistry::getLayoutMode('sn-cms', 'wide-hero', 'top'))->toBe(CompositionRenderer::LAYOUT_MODE_STACK);
});
