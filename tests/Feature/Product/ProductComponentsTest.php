<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Livewire\Components\Product\Product as ProductComponent;
use Wsmallnews\Product\Livewire\Components\Product\Products as ProductsComponent;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Support\ProductSpecService;

uses(RefreshDatabase::class);

use function Pest\Livewire\livewire;

// ========================= 测试辅助 =========================

/**
 * 调用组件的 protected 方法（规格匹配等展示逻辑）。
 */
function invokeComponentMethod($testable, string $method, ...$args): mixed
{
    return Closure::bind(fn () => $this->{$method}(...$args), $testable->instance(), ProductComponent::class)();
}

/**
 * 单规格产品（带唯一变体）。
 */
function createSingleProduct(array $attributes = []): Product
{
    $product = Product::factory()->create($attributes);

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

    return $product->fresh();
}

/**
 * 多规格产品：颜色（红/蓝）× 尺码（M/L）= 4 组合，库存制（红/M 与 蓝/L 无库存）。
 */
function createMultiProduct(array $attributes = []): Product
{
    $product = Product::factory()->specType(ProductSpecType::Multiple)->create(array_merge([
        'stock_type' => ProductStockType::Stock->value,
    ], $attributes));

    ProductSpecService::save($product, [
        'variant' => [],
        'specs' => [
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
        ],
        'variants' => [
            'v1' => ['spec_names' => ['红', 'M'], 'product_spec_text' => '红,M', 'image' => null, 'product_sn' => 'A1', 'price' => '10.00', 'stock' => 0, 'weight' => 0, 'stock_convert_num' => 1],
            'v2' => ['spec_names' => ['红', 'L'], 'product_spec_text' => '红,L', 'image' => null, 'product_sn' => 'A2', 'price' => '20.00', 'stock' => 2, 'weight' => 0, 'stock_convert_num' => 1],
            'v3' => ['spec_names' => ['蓝', 'M'], 'product_spec_text' => '蓝,M', 'image' => null, 'product_sn' => 'A3', 'price' => '30.00', 'stock' => 3, 'weight' => 0, 'stock_convert_num' => 1],
            'v4' => ['spec_names' => ['蓝', 'L'], 'product_spec_text' => '蓝,L', 'image' => null, 'product_sn' => 'A4', 'price' => '40.00', 'stock' => 0, 'weight' => 0, 'stock_convert_num' => 1],
        ],
    ]);

    return $product->fresh(['specs.children', 'variants']);
}

// ========================= 列表组件 =========================

it('产品列表组件渲染上架产品并按 scope 过滤', function () {
    $product = createSingleProduct(['scope_type' => 'sn-test', 'scope_id' => 0]);
    createSingleProduct(['title' => '其他作用域产品', 'scope_type' => 'sn-other', 'scope_id' => 0]);
    createSingleProduct(['title' => '已下架产品', 'status' => ProductStatus::Down, 'scope_type' => 'sn-test', 'scope_id' => 0]);

    livewire(ProductsComponent::class, ['scopeType' => 'sn-test', 'scopeId' => 0])
        ->assertSee($product->title)
        ->assertDontSee('其他作用域产品')
        ->assertDontSee('已下架产品');
});

it('产品列表组件按 hrefRoute 生成详情链接', function () {
    $product = createSingleProduct(['scope_type' => 'sn-test', 'scope_id' => 0]);

    // 传入消费模块的路由名 → 卡片链接指向该模块详情页
    $component = livewire(ProductsComponent::class, [
        'scopeType' => 'sn-test',
        'scopeId' => 0,
        'hrefRoute' => 'sn-shop.product.detail',
    ]);
    expect($component->instance()->getDetailUrl($product))->toBe(route('sn-shop.product.detail', ['id' => $product->id]));

    // 未传路由名 → 卡片不生成链接（产品包自身不设路由）
    $component = livewire(ProductsComponent::class, ['scopeType' => 'sn-test', 'scopeId' => 0]);
    expect($component->instance()->getDetailUrl($product))->toBeNull();
});

// ========================= 详情组件 =========================

it('产品详情组件按 id 渲染单规格产品（唯一变体自动选中）', function () {
    $user = User::factory()->create();
    $product = createSingleProduct(['scope_type' => 'sn-test', 'scope_id' => 0]);

    livewire(ProductComponent::class, [
        'scopeType' => 'sn-test',
        'scopeId' => 0,
        'id' => $product->id,
        'authUser' => $user,
    ])
        ->assertSee($product->title)
        ->assertSet('id', $product->id);

    // 浏览计数增加（登录用户同时记录浏览足迹）
    expect((int) $product->fresh()->counter['view_num'])->toBe(1);
});

it('多规格产品：规格选择、组合匹配、buy 事件与不可选规格', function () {
    $product = createMultiProduct(['scope_type' => 'sn-test', 'scope_id' => 0]);

    $colorSpec = $product->parentSpecs->first();
    $sizeSpec = $product->parentSpecs->last();
    $redSpec = $colorSpec->children->firstWhere('name', '红');
    $mSpec = $sizeSpec->children->firstWhere('name', 'M');
    $lSpec = $sizeSpec->children->firstWhere('name', 'L');

    $component = livewire(ProductComponent::class, ['scopeType' => 'sn-test', 'scopeId' => 0, 'id' => $product->id]);

    // 未选满全部规格组 → 无已选变体
    $component->call('chooseSpec', $colorSpec->id, $redSpec->id)
        ->assertSet('selectedSpecs', [$colorSpec->id => $redSpec->id]);
    expect(invokeComponentMethod($component, 'getChoosedVariant', ...invokeSelectionArgs($component)))->toBeNull();

    // 选满组合 红+L → 命中唯一有货变体 v2（红/L）
    $component->call('chooseSpec', $sizeSpec->id, $lSpec->id);
    $choosedVariant = invokeComponentMethod($component, 'getChoosedVariant', ...invokeSelectionArgs($component));
    expect($choosedVariant->product_spec_text)->toBe(['红', 'L']);

    // buy 派发 product-buy 事件（携带变体 id 与数量）
    $component->call('buy')
        ->assertDispatched('product-buy', function (string $name, array $params) use ($choosedVariant) {
            $items = json_decode($params['relate_items'], true);

            return $params['type'] === 'product'
                && $items[0]['product_variant_id'] === $choosedVariant->id
                && $items[0]['product_num'] === 1;
        });

    // 已选红色时：尺码 M 的组合无库存 → 不可选，L 可选
    $selectable = invokeComponentMethod($component, 'getSpecSelectable', ...invokeSelectableArgs($component));
    expect($selectable[$sizeSpec->id][$mSpec->id])->toBeFalse()
        ->and($selectable[$sizeSpec->id][$lSpec->id])->toBeTrue();

    // 取消颜色选择（再次点击红）→ 两组约束解除，全部规格值恢复可选（红/M、蓝/L 组合本身无库存除外）
    $component->call('chooseSpec', $colorSpec->id, $redSpec->id);
    $selectable = invokeComponentMethod($component, 'getSpecSelectable', ...invokeSelectableArgs($component));
    expect($selectable[$colorSpec->id][$redSpec->id])->toBeTrue()
        ->and($selectable[$sizeSpec->id][$mSpec->id])->toBeTrue();
});

it('隐藏产品详情可访问（隐藏 = 不列表展示，直达链接可买）', function () {
    $product = createSingleProduct([
        'status' => ProductStatus::Hidden,
        'scope_type' => 'sn-test',
        'scope_id' => 0,
    ]);

    livewire(ProductComponent::class, ['scopeType' => 'sn-test', 'scopeId' => 0, 'id' => $product->id])
        ->assertSee($product->title);
});

/**
 * 组装 getChoosedVariant 的参数（当前产品 + 规格组 + 可售变体）。
 */
function invokeSelectionArgs($component): array
{
    $product = invokeComponentMethod($component, 'loadProduct');

    return [
        $product,
        invokeComponentMethod($component, 'getParentSpecs', $product),
        invokeComponentMethod($component, 'getSaleableVariants', $product),
    ];
}

/**
 * 组装 getSpecSelectable 的参数。
 */
function invokeSelectableArgs($component): array
{
    $product = invokeComponentMethod($component, 'loadProduct', false);

    return [
        invokeComponentMethod($component, 'getParentSpecs', $product),
        invokeComponentMethod($component, 'getSaleableVariants', $product),
    ];
}
