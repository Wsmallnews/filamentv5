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
        ->assertSet('id', $product->id)
        // 单规格：schema 无规格组、唯一可售变体；初始价格件取变体价（SEO / Alpine 加载前回退）
        ->assertViewHas('specSchema', fn (array $schema) => $schema['specs'] === []
            && count($schema['variants']) === 1
            && $schema['variants'][0]['id'] === $product->variants->first()->id)
        ->assertViewHas('initialPriceParts', fn (array $parts) => $parts['amount'] === '12.50');

    // 浏览计数增加（登录用户同时记录浏览足迹）
    expect((int) $product->fresh()->counter['view_num'])->toBe(1);
});

it('多规格产品：specSchema 契约输出与 buy 事件服务端校验', function () {
    $product = createMultiProduct(['scope_type' => 'sn-test', 'scope_id' => 0]);

    $v2 = $product->variants->firstWhere('product_sn', 'A2');     // 红/L 有货
    $v3 = $product->variants->firstWhere('product_sn', 'A3');     // 蓝/M 有货
    $v1 = $product->variants->firstWhere('product_sn', 'A1');     // 红/M 无库存

    $component = livewire(ProductComponent::class, ['scopeType' => 'sn-test', 'scopeId' => 0, 'id' => $product->id]);

    // schema 契约（PC Alpine 与 uniapp 接口共用）：规格组树 + 仅可售变体 + addons 预留节点
    $component->assertViewHas('specSchema', function (array $schema) use ($v2, $v3) {
        $saleableIds = collect($schema['variants'])->pluck('id')->all();

        return $schema['schema_version'] === 1
            && $schema['spec_type'] === 'multiple'
            && count($schema['specs']) === 2
            && count($schema['specs'][0]['children']) === 2
            && $schema['specs'][0]['children'][0]['name'] === '红'
            && $saleableIds === [$v2->id, $v3->id]
            && collect($schema['variants'])->firstWhere('id', $v2->id)['spec_ids'] === $v2->specs->pluck('id')->values()->all()
            && collect($schema['variants'])->firstWhere('id', $v2->id)['price'] === 2000
            && collect($schema['variants'])->firstWhere('id', $v2->id)['price_parts']['amount'] === '20.00'
            && collect($schema['variants'])->firstWhere('id', $v2->id)['spec_text'] === ['红', 'L']
            && $schema['extras'] === [];
    });

    // buy：可售变体 → 派发 sn-product-buy（携带变体 id 与数量）
    $component->call('buy', $v2->id, 2)
        ->assertDispatched('sn-product-buy', function (string $name, array $params) use ($v2) {
            $items = json_decode($params['relate_items'], true);

            return $params['type'] === 'product'
                && $items[0]['product_variant_id'] === $v2->id
                && $items[0]['product_num'] === 2;
        });

    // buy：无库存变体 / 未选规格（0）/ 不存在变体 → 服务端拒绝，不派发
    $component->call('buy', $v1->id, 1)->assertNotDispatched('sn-product-buy');
    $component->call('buy', 0, 1)->assertNotDispatched('sn-product-buy');
    $component->call('buy', 999999, 1)->assertNotDispatched('sn-product-buy');

    // buy：数量下限保护（0/负数回退为 1）
    $component->call('buy', $v3->id, 0)
        ->assertDispatched('sn-product-buy', function (string $name, array $params) use ($v3) {
            $items = json_decode($params['relate_items'], true);

            return $items[0]['product_variant_id'] === $v3->id
                && $items[0]['product_num'] === 1;
        });
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
