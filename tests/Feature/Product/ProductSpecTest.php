<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Filament\Resources\Products\Pages\CreateProduct;
use Wsmallnews\Product\Filament\Resources\Products\Schemas\ProductSpecForm;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Support\ProductSpecService;

uses(RefreshDatabase::class);

use function Pest\Livewire\livewire;

// ========================= 测试数据 =========================

/**
 * 2 个规格项（颜色 2 值 / 尺码 2 值），笛卡尔积 = 4 组合。
 *
 * @return array<string, mixed>
 */
function twoByTwoSpecs(): array
{
    return [
        'u-color' => [
            'name' => '颜色',
            'children' => [
                'c-red' => ['name' => '红', 'image' => null],
                'c-blue' => ['name' => '蓝', 'image' => null],
            ],
        ],
        'u-size' => [
            'name' => '尺码',
            'children' => [
                's-m' => ['name' => 'M', 'image' => null],
                's-l' => ['name' => 'L', 'image' => null],
            ],
        ],
    ];
}

/**
 * 金额断言辅助（cknow/money 的 getAmount() 返回 string）。
 */
function amount(mixed $money): int
{
    return (int) $money->getAmount();
}

// ========================= 组合重算（纯逻辑） =========================

it('按笛卡尔积生成规格组合', function () {
    $variants = ProductSpecForm::computeVariants(twoByTwoSpecs(), []);

    expect($variants)->toHaveCount(4);

    $texts = collect($variants)->pluck('product_spec_text')->sort()->values()->all();
    expect($texts)->toBe(['红,L', '红,M', '蓝,L', '蓝,M']);

    // 每个组合携带规格值名称
    $fingerprints = collect($variants)->pluck('spec_names')->map(fn (array $names): string => implode('|', $names))->unique();
    expect($fingerprints)->toHaveCount(4);
    expect(collect($variants)->first()['spec_names'])->toBe(['红', 'M']);
});

it('无有效规格时不生成组合', function () {
    $specs = [
        'u-empty' => ['name' => '颜色', 'children' => []],
        'u-blank' => ['name' => '尺码', 'children' => ['s-x' => ['name' => '', 'image' => null]]],
    ];

    expect(ProductSpecForm::computeVariants($specs, []))->toBe([]);
});

it('重算时保留用户已填的组合属性', function () {
    $variants = ProductSpecForm::computeVariants(twoByTwoSpecs(), []);

    // 用户给"红,M"组合填了价格和库存
    $filled = collect($variants)->first(fn (array $item): bool => $item['product_spec_text'] === '红,M');
    $filled['price'] = '12.50';
    $filled['stock'] = 8;
    $filledKey = array_search($filled, $variants);
    $variants[$filledKey] = $filled;

    // 结构不变重算：已填的值应保留
    $recomputed = ProductSpecForm::computeVariants(twoByTwoSpecs(), $variants);
    $kept = collect($recomputed)->first(fn (array $item): bool => $item['product_spec_text'] === '红,M');

    expect($kept['price'])->toBe('12.50')
        ->and($kept['stock'])->toBe(8);

    // 删除"红色"后只剩 2 个组合，且 M,红 组合消失
    $specs = twoByTwoSpecs();
    unset($specs['u-color']['children']['c-red']);

    $reduced = ProductSpecForm::computeVariants($specs, $recomputed);

    expect($reduced)->toHaveCount(2);
    expect(collect($reduced)->pluck('product_spec_text'))->not->toContain('红,M');
});

// ========================= 保存（服务层） =========================

it('保存单规格产品：唯一变体并同步主表价格', function () {
    $product = Product::factory()->create();

    ProductSpecService::save($product, [
        'specs' => [],
        'variants' => [],
        'variant' => [
            'price' => '12.50',
            'stock' => 5,
            'product_sn' => 'SN-001',
            'weight' => '0.5',
        ],
    ]);

    expect($product->variants()->count())->toBe(1);

    $variant = $product->variants()->first();
    expect($variant->spec_type)->toBe(ProductSpecType::Single)
        ->and(amount($variant->price))->toBe(1250)
        ->and((int) $variant->stock)->toBe(5)
        ->and($variant->product_sn)->toBe('SN-001')
        ->and((float) $variant->weight)->toBe(0.5)
        ->and($variant->specs()->count())->toBe(0)
        // 主表价格同步为变体价格
        ->and(amount($product->fresh()->price))->toBe(1250);
});

it('保存多规格产品：规格树、组合与关联', function () {
    $product = Product::factory()->specType(ProductSpecType::Multiple)->create();

    ProductSpecService::save($product, [
        'variant' => [],
        'specs' => twoByTwoSpecs(),
        'variants' => [
            'v1' => ['spec_names' => ['红', 'M'], 'product_spec_text' => '红,M', 'image' => null, 'product_sn' => 'A1', 'price' => '10.00', 'stock' => 1, 'weight' => 0, 'stock_convert_num' => 1],
            'v2' => ['spec_names' => ['红', 'L'], 'product_spec_text' => '红,L', 'image' => null, 'product_sn' => 'A2', 'price' => '20.00', 'stock' => 2, 'weight' => 0, 'stock_convert_num' => 1],
            'v3' => ['spec_names' => ['蓝', 'M'], 'product_spec_text' => '蓝,M', 'image' => null, 'product_sn' => 'A3', 'price' => '30.00', 'stock' => 3, 'weight' => 0, 'stock_convert_num' => 1],
            'v4' => ['spec_names' => ['蓝', 'L'], 'product_spec_text' => '蓝,L', 'image' => null, 'product_sn' => 'A4', 'price' => '40.00', 'stock' => 4, 'weight' => 0, 'stock_convert_num' => 1],
        ],
    ]);

    // 规格树：2 父 + 4 子
    expect($product->specs()->count())->toBe(6)
        ->and($product->parentSpecs()->get()->map->name->all())->toBe(['颜色', '尺码']);

    $color = $product->parentSpecs()->first();
    expect($color->children->map->name->all())->toBe(['红', '蓝']);

    // 变体与关联
    expect($product->variants()->count())->toBe(4);

    $redM = $product->variants()->where('product_sn', 'A1')->first();
    expect($redM->product_spec_text)->toBe(['红', 'M'])
        ->and($redM->spec_type)->toBe(ProductSpecType::Multiple)
        ->and(amount($redM->price))->toBe(1000)
        ->and($redM->specs->map->name->sort()->values()->all())->toBe(['M', '红']);

    // 主表价格 = 最低变体价格
    expect(amount($product->fresh()->price))->toBe(1000)
        ->and(DB::table('sn_product_spec_variants')->count())->toBe(8);
});

it('保存主多规格产品：主规格值属性同步到所有相关组合', function () {
    $product = Product::factory()->specType(ProductSpecType::MainMultiple)->create();

    ProductSpecService::save($product, [
        'variant' => [],
        // 主规格：颜色（规格值携带属性）
        'specs' => [
            'u-color' => [
                'name' => '颜色',
                'children' => [
                    'c-red' => ['name' => '红', 'image' => null, 'price' => '99.00', 'stock' => 9, 'product_sn' => 'RED-SN', 'weight' => '1.5'],
                    'c-blue' => ['name' => '蓝', 'image' => null, 'price' => '59.00', 'stock' => 5, 'product_sn' => null, 'weight' => 0],
                ],
            ],
        ],
        // 附加规格项：尺码（不带属性）
        'extra_specs' => [
            'u-size' => [
                'name' => '尺码',
                'children' => [
                    's-m' => ['name' => 'M', 'image' => null],
                    's-l' => ['name' => 'L', 'image' => null],
                ],
            ],
        ],
        // 组合 state 属性为空 —— 保存时以主规格值的设置为准
        'variants' => [
            'v1' => ['spec_names' => ['红', 'M']],
            'v2' => ['spec_names' => ['红', 'L']],
            'v3' => ['spec_names' => ['蓝', 'M']],
            'v4' => ['spec_names' => ['蓝', 'L']],
        ],
    ]);

    // 规格树：主规格 + 附加规格（2 父 + 4 子）
    expect($product->specs()->count())->toBe(6)
        ->and($product->parentSpecs()->get()->map->name->all())->toBe(['颜色', '尺码']);

    // 组合：2 × 2 = 4，属性来自主规格值
    expect($product->variants()->count())->toBe(4);

    foreach ($product->variants()->get() as $variant) {
        $isRed = in_array('红', $variant->specs->pluck('name')->all());

        expect($variant->spec_type)->toBe(ProductSpecType::MainMultiple)
            ->and(amount($variant->price))->toBe($isRed ? 9900 : 5900)
            ->and((int) $variant->stock)->toBe($isRed ? 9 : 5)
            ->and($variant->product_sn)->toBe($isRed ? 'RED-SN' : null)
            ->and((float) $variant->weight)->toBe($isRed ? 1.5 : 0.0)
            ->and($variant->specs()->count())->toBe(2);
    }

    expect(amount($product->fresh()->price))->toBe(5900);
});

it('回填主多规格产品：主规格值携带属性并拆分附加规格', function () {
    $product = Product::factory()->specType(ProductSpecType::MainMultiple)->create();

    ProductSpecService::save($product, [
        'variant' => [],
        'specs' => [
            'u-color' => [
                'name' => '颜色',
                'children' => [
                    'c-red' => ['name' => '红', 'image' => null, 'price' => '99.00', 'stock' => 9, 'product_sn' => 'RED-SN', 'weight' => '1.5'],
                ],
            ],
        ],
        'extra_specs' => [
            'u-size' => [
                'name' => '尺码',
                'children' => [
                    's-m' => ['name' => 'M', 'image' => null],
                ],
            ],
        ],
        'variants' => [
            'v1' => ['spec_names' => ['红', 'M']],
        ],
    ]);

    $state = ProductSpecService::toFormState($product->fresh());

    // 主规格 → specs，附加规格 → extra_specs
    expect(count($state['specs']))->toBe(1)
        ->and(count($state['extra_specs']))->toBe(1)
        ->and($state['variants'])->toHaveCount(1);

    // 主规格值回填变体属性
    $mainChild = collect(collect($state['specs'])->first()['children'])->first();
    expect($mainChild['name'])->toBe('红')
        ->and($mainChild['price'])->toBe('99.00')
        ->and($mainChild['stock'])->toBe(9)
        ->and($mainChild['product_sn'])->toBe('RED-SN')
        ->and((float) $mainChild['weight'])->toBe(1.5);

    // 附加规格值不带属性
    $extraChild = collect(collect($state['extra_specs'])->first()['children'])->first();
    expect($extraChild['name'])->toBe('M')
        ->and(array_key_exists('price', $extraChild))->toBeFalse();

    // 组合 state 带属性（disabled 展示用）
    $variant = collect($state['variants'])->first();
    expect($variant['spec_names'])->toBe(['红', 'M'])
        ->and($variant['price'])->toBe('99.00');
});

it('保存多单位产品：规格名强制为单位并写入换算比例', function () {
    $product = Product::factory()->specType(ProductSpecType::Unit)->create([
        'stock_unit' => '瓶',
    ]);

    ProductSpecService::save($product, [
        'variant' => [],
        'specs' => [
            'u-unit' => [
                'name' => '随便填', // 应被强制为「单位」
                'children' => [
                    'c-p' => ['name' => '瓶', 'image' => null],
                    'c-x' => ['name' => '箱', 'image' => null],
                ],
            ],
        ],
        'variants' => [
            'v1' => ['spec_names' => ['瓶'], 'stock_convert_num' => 1, 'price' => '1.00', 'stock' => 100, 'product_sn' => null, 'weight' => 0, 'image' => null],
            'v2' => ['spec_names' => ['箱'], 'stock_convert_num' => 10, 'price' => '10.00', 'stock' => 10, 'product_sn' => null, 'weight' => 0, 'image' => null],
        ],
    ]);

    // 父级规格名固定为「单位」，且只有一项
    expect($product->parentSpecs()->count())->toBe(1)
        ->and($product->parentSpecs()->first()->name)->toBe('单位');

    $box = $product->variants()->where('product_spec_text', '箱')->first();
    expect($box->stock_unit)->toBe('箱')
        ->and((int) $box->stock_convert_num)->toBe(10)
        ->and($box->spec_type)->toBe(ProductSpecType::Unit);
});

it('重新保存时全量替换旧的规格与变体', function () {
    $product = Product::factory()->specType(ProductSpecType::Multiple)->create();

    $first = [
        'variant' => [],
        'specs' => twoByTwoSpecs(),
        'variants' => ProductSpecForm::computeVariants(twoByTwoSpecs(), []),
    ];
    ProductSpecService::save($product, $first);

    expect($product->variants()->count())->toBe(4)
        ->and(DB::table('sn_product_spec_variants')->count())->toBe(8);

    // 第二次保存：切换为单规格（页面保存时会先更新 record 的 spec_type）
    $product = $product->fresh();
    $product->update(['spec_type' => ProductSpecType::Single]);

    ProductSpecService::save($product, [
        'specs' => [],
        'variants' => [],
        'variant' => ['price' => '5.00', 'stock' => 1, 'product_sn' => null, 'weight' => 0],
    ]);

    expect($product->fresh()->variants()->count())->toBe(1)
        ->and($product->fresh()->specs()->count())->toBe(0)
        // 无孤儿关联
        ->and(DB::table('sn_product_spec_variants')->count())->toBe(0)
        ->and(amount($product->fresh()->price))->toBe(500);
});

it('回填表单 state 并可无损再次保存', function () {
    $product = Product::factory()->specType(ProductSpecType::Multiple)->create();

    $data = [
        'variant' => [],
        'specs' => twoByTwoSpecs(),
        'variants' => [
            'v1' => ['spec_names' => ['红', 'M'], 'product_spec_text' => '红,M', 'image' => null, 'product_sn' => 'A1', 'price' => '10.00', 'stock' => 1, 'weight' => 0, 'stock_convert_num' => 1],
            'v2' => ['spec_names' => ['蓝', 'L'], 'product_spec_text' => '蓝,L', 'image' => null, 'product_sn' => 'A2', 'price' => '20.00', 'stock' => 2, 'weight' => 0, 'stock_convert_num' => 1],
        ],
    ];
    ProductSpecService::save($product, $data);

    // 回填
    $state = ProductSpecService::toFormState($product->fresh());

    expect($state['specs'])->toHaveCount(2)
        ->and($state['variants'])->toHaveCount(2);

    $variants = collect($state['variants']);
    expect($variants->pluck('price')->sort()->values()->all())->toBe(['10.00', '20.00'])
        ->and($variants->pluck('product_spec_text')->sort()->values()->all())->toBe(['红,M', '蓝,L']);

    // spec_keys 与新 uuid 对齐（回填后仍能再次保存成功）
    ProductSpecService::save($product->fresh(), $state);

    expect($product->fresh()->variants()->count())->toBe(2)
        ->and(amount($product->fresh()->variants()->where('product_sn', 'A1')->first()->price))->toBe(1000);
});

// ========================= 后台表单（Livewire） =========================

it('通过后台表单创建单规格产品', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    Storage::fake();

    livewire(CreateProduct::class)
        ->fillForm([
            'title' => '测试产品',
            'params' => ['产地' => '上海'],
            'scheduledTasks' => [],
            'spec_type' => 'single',
            'variant' => [
                'price' => '15.00',
                'stock' => 3,
                'product_sn' => 'SN-TEST',
                'weight' => '0.2',
            ],
            'product_image' => [UploadedFile::fake()->image('cover.jpg')],
            'product_images' => [UploadedFile::fake()->image('gallery.jpg')],
            'content.content_richtext' => '<p>测试产品详情</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('title', '测试产品')->first();

    expect($product)->not->toBeNull()
        ->and($product->variants()->count())->toBe(1)
        ->and(amount($product->variants()->first()->price))->toBe(1500)
        ->and(amount($product->price))->toBe(1500);
});

it('切换规格类型联动显示对应的编辑器', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    $component = livewire(CreateProduct::class);

    // 默认单规格：显示平铺的规格字段，不显示规格项编辑器
    expect($component->html())->toContain('data.variant.price')
        ->and($component->html())->not->toContain('data.specs');

    // 切换为多规格：显示规格项与组合编辑器，隐藏平铺字段
    $component->fillForm(['spec_type' => ProductSpecType::Multiple->value]);

    expect($component->html())->toContain('data.specs')
        ->and($component->html())->not->toContain('data.variant.price');

    // 切换为多单位：显示规格项编辑器，规格名锁定为「单位」
    $component->fillForm(['spec_type' => ProductSpecType::Unit->value]);
    $component->set('data.specs', [
        'u1' => ['name' => '任意名', 'children' => ['c1' => ['name' => '瓶', 'image' => null]]],
    ]);

    expect($component->html())->toContain('data.specs')
        ->and($component->html())->toContain('单位');
});

it('通过后台表单创建多规格产品（组合由规格项自动生成）', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    Storage::fake();

    $component = livewire(CreateProduct::class);

    // 模拟真实用户操作：先填基础信息，再切换类型并填写规格项
    $component->fillForm([
        'title' => '多规格产品',
        'params' => ['产地' => '上海'],
        'scheduledTasks' => [],
        'product_image' => [UploadedFile::fake()->image('cover.jpg')],
        'product_images' => [UploadedFile::fake()->image('gallery.jpg')],
        'content.content_richtext' => '<p>多规格产品详情</p>',
    ]);

    // 规格变更触发组合重算（1 规格 × 2 规格值 = 2 组合）
    $component->fillForm([
        'spec_type' => 'multiple',
        'specs' => [
            'u1' => [
                'name' => '颜色',
                'children' => [
                    'c1' => ['name' => '红', 'image' => null],
                    'c2' => ['name' => '蓝', 'image' => null],
                ],
            ],
        ],
    ]);

    // 重算生成的组合自动填入 variants
    $variants = $component->instance()->data['variants'] ?? [];
    expect(count($variants))->toBe(2);

    $component->call('create')->assertHasNoFormErrors();

    $product = Product::where('title', '多规格产品')->first();

    expect($product)->not->toBeNull()
        ->and($product->spec_type)->toBe(ProductSpecType::Multiple)
        // 1 父规格 + 2 规格值
        ->and($product->specs()->count())->toBe(3)
        ->and($product->variants()->count())->toBe(2)
        ->and($product->variants()->pluck('product_spec_text')->sort()->values()->all())->toBe([['红'], ['蓝']])
        // 未填价格时兜底为 0
        ->and(amount($product->price))->toBe(0);
});

// ========================= 表格列与标签（Livewire 渲染） =========================

it('多单位模式下规格项标签显示「单位」而非「未命名规格」', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    $component = livewire(CreateProduct::class);

    // name 为空的规格项（如切换类型前创建），标签应兜底为「单位」
    $component->fillForm([
        'spec_type' => ProductSpecType::Unit->value,
        'specs' => [
            'u1' => ['name' => '', 'children' => ['c1' => ['name' => '瓶', 'image' => null]]],
        ],
    ]);

    expect($component->html())->not->toContain('未命名规格');
});

it('多单位模式切换时归一化已有规格项的名称为「单位」', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    $component = livewire(CreateProduct::class);

    // 多规格下创建未命名规格项，再切到多单位
    $component->fillForm([
        'spec_type' => ProductSpecType::Multiple->value,
        'specs' => [
            'u1' => ['name' => '', 'children' => ['c1' => ['name' => '瓶', 'image' => null]]],
        ],
    ]);

    $component->fillForm(['spec_type' => ProductSpecType::Unit->value]);

    $specs = $component->instance()->data['specs'] ?? [];

    expect($specs)->toHaveCount(1)
        ->and(collect($specs)->first()['name'])->toBe('单位');
});

it('换算比例列仅多单位模式显示', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    $component = livewire(CreateProduct::class);

    $specs = [
        'u1' => ['name' => '颜色', 'children' => [
            'c1' => ['name' => '红', 'image' => null],
            'c2' => ['name' => '蓝', 'image' => null],
        ]],
    ];

    // 多规格：组合表无换算比例列
    $component->fillForm(['spec_type' => ProductSpecType::Multiple->value, 'specs' => $specs]);

    expect(count($component->instance()->data['variants']))->toBe(2)
        ->and($component->html())->not->toContain('换算比例');

    // 多单位：换算比例列出现
    $component->fillForm(['spec_type' => ProductSpecType::Unit->value]);

    expect(count($component->instance()->data['variants']))->toBe(2)
        ->and($component->html())->toContain('换算比例');
});

it('规格组合首列以纯文本展示规格', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    $component = livewire(CreateProduct::class);

    $component->fillForm([
        'spec_type' => ProductSpecType::Multiple->value,
        'specs' => [
            'u1' => ['name' => '颜色', 'children' => [
                'c1' => ['name' => '红', 'image' => null],
                'c2' => ['name' => '蓝', 'image' => null],
            ]],
            'u2' => ['name' => '尺码', 'children' => [
                's1' => ['name' => 'M', 'image' => null],
            ]],
        ],
    ]);

    $html = $component->html();

    // TextEntry 纯文本渲染（fi-in-text），组合文本直接可见
    expect($html)->toContain('fi-in-text')
        ->and($html)->toContain('红,M')
        ->and($html)->toContain('蓝,M');
});

it('支持通过 session 偏好隐藏可选列', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    session(['sn-product.hidden_variant_columns' => ['weight']]);

    $component = livewire(CreateProduct::class);

    $component->fillForm([
        'spec_type' => ProductSpecType::Multiple->value,
        'specs' => [
            'u1' => ['name' => '颜色', 'children' => [
                'c1' => ['name' => '红', 'image' => null],
            ]],
        ],
    ]);

    // 重量列被隐藏（表头与字段均不渲染），其余列不受影响
    expect($component->html())->not->toContain('重量(KG)')
        ->and($component->html())->toContain('货号')
        ->and($component->html())->toContain('价格');

    // 偏好只隐藏表列，数据层不受影响
    $variants = $component->instance()->data['variants'];
    expect(collect($variants)->first())->toHaveKey('weight');
});
