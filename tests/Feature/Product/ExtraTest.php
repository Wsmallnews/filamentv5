<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Product\Enums\ExtraStatus;
use Wsmallnews\Product\Enums\ExtraType;
use Wsmallnews\Product\Exceptions\ProductException;
use Wsmallnews\Product\Livewire\Components\Product\Product as ProductComponent;
use Wsmallnews\Product\Models\Extra;
use Wsmallnews\Product\Models\ExtraItem;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Support\ExtraManager;
use Wsmallnews\Product\Support\ProductSpecService;

uses(RefreshDatabase::class);

use function Pest\Livewire\livewire;

// ========================= 测试辅助 =========================

/**
 * 附加项库：小料（多选 + 数量计价）+ 杯型（单选 + 必选）。
 * 珍珠 200 分（挂载时覆盖 250）、椰果 300 分、芝士今日售罄；大杯 100 / 中杯 0。
 */
function createExtraLibrary(): array
{
    $toppings = Extra::create([
        'scope_type' => 'sn-test', 'scope_id' => 0, 'parent_id' => 0,
        'name' => '小料', 'type' => ExtraType::Multiple, 'is_require' => false, 'is_num' => true,
        'status' => ExtraStatus::Up,
    ]);
    $pearl = Extra::create(['parent_id' => $toppings->id, 'name' => '珍珠', 'price' => '2.00', 'status' => ExtraStatus::Up, 'options' => ['limit_min_num' => 1, 'limit_max_num' => 3]]);
    $coconut = Extra::create(['parent_id' => $toppings->id, 'name' => '椰果', 'price' => '3.00', 'status' => ExtraStatus::Up]);
    $cheese = Extra::create(['parent_id' => $toppings->id, 'name' => '芝士', 'price' => '4.00', 'status' => ExtraStatus::Up, 'sold_out_until' => now()->endOfDay()]);

    $cup = Extra::create([
        'scope_type' => 'sn-test', 'scope_id' => 0, 'parent_id' => 0,
        'name' => '杯型', 'type' => ExtraType::Single, 'is_require' => true, 'is_num' => false,
        'status' => ExtraStatus::Up,
    ]);
    $large = Extra::create(['parent_id' => $cup->id, 'name' => '大杯', 'price' => '1.00', 'status' => ExtraStatus::Up]);
    $middle = Extra::create(['parent_id' => $cup->id, 'name' => '中杯', 'price' => '0.00', 'status' => ExtraStatus::Up]);

    return compact('toppings', 'pearl', 'coconut', 'cheese', 'cup', 'large', 'middle');
}

/**
 * 挂载附加项的单规格产品（珍珠覆盖价 2.50）。
 */
function createProductWithExtras(array $extras): Product
{
    $product = Product::factory()->create(['scope_type' => 'sn-test', 'scope_id' => 0]);

    ProductSpecService::save($product, [
        'specs' => [],
        'variants' => [],
        'variant' => ['price' => '12.00', 'stock' => 10, 'product_sn' => 'SN-EX-001', 'weight' => '0.5'],
    ]);

    ExtraItem::create(['product_id' => $product->id, 'extra_id' => $extras['pearl']->id, 'price_override' => '2.50']);
    ExtraItem::create(['product_id' => $product->id, 'extra_id' => $extras['coconut']->id]);
    ExtraItem::create(['product_id' => $product->id, 'extra_id' => $extras['cheese']->id]);
    ExtraItem::create(['product_id' => $product->id, 'extra_id' => $extras['large']->id]);
    ExtraItem::create(['product_id' => $product->id, 'extra_id' => $extras['middle']->id]);

    return $product->fresh(['extraItems.extra.parent']);
}

// ========================= ExtraManager =========================

it('resolveProductExtras：组过滤/有效价/售罄标记', function () {
    $extras = createExtraLibrary();
    $product = createProductWithExtras($extras);

    $resolved = (new ExtraManager)->resolveProductExtras($product);

    // 组按挂载顺序聚合：小料（3 选项）+ 杯型（2 选项）
    expect($resolved)->toHaveCount(2)
        ->and($resolved[0]['name'])->toBe('小料')
        ->and($resolved[0]['type'])->toBe('multiple')
        ->and($resolved[0]['is_num'])->toBeTrue()
        ->and($resolved[0]['is_require'])->toBeFalse()
        ->and(count($resolved[0]['children']))->toBe(3)
        // 珍珠覆盖价 250 分生效；椰果用库价 300；芝士售罄保留标记
        ->and(collect($resolved[0]['children'])->firstWhere('name', '珍珠')['price'])->toBe(250)
        ->and(collect($resolved[0]['children'])->firstWhere('name', '椰果')['price'])->toBe(300)
        ->and(collect($resolved[0]['children'])->firstWhere('name', '芝士')['sold_out'])->toBeTrue()
        ->and(collect($resolved[0]['children'])->firstWhere('name', '珍珠')['sold_out'])->toBeFalse()
        ->and($resolved[1]['name'])->toBe('杯型')
        ->and($resolved[1]['type'])->toBe('single')
        ->and($resolved[1]['is_require'])->toBeTrue();
});

it('resolveProductExtras：下架组/下架选项剔除', function () {
    $extras = createExtraLibrary();
    $extras['cup']->update(['status' => ExtraStatus::Down]);
    $extras['coconut']->update(['status' => ExtraStatus::Down]);

    $product = createProductWithExtras($extras);
    $resolved = (new ExtraManager)->resolveProductExtras($product);

    // 杯型组下架 → 整组消失；椰果下架 → 选项消失
    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]['name'])->toBe('小料')
        ->and(collect($resolved[0]['children'])->pluck('name')->all())->toBe(['珍珠', '芝士']);
});

it('buyCheckAndGet：必选/售罄/单多选/数量限制/未知组', function () {
    $extras = createExtraLibrary();
    $product = createProductWithExtras($extras);
    $manager = new ExtraManager;
    $productExtras = $manager->resolveProductExtras($product);

    // 必选组未选 → 拒绝
    $manager->buyCheckAndGet($productExtras, []);
})->throws(ProductException::class, '杯型');

it('buyCheckAndGet：合法选择规整（数量归一/价格快照）', function () {
    $extras = createExtraLibrary();
    $product = createProductWithExtras($extras);
    $manager = new ExtraManager;
    $productExtras = $manager->resolveProductExtras($product);

    $checked = $manager->buyCheckAndGet($productExtras, [
        ['id' => $extras['cup']->id, 'children' => [['id' => $extras['large']->id]]],
        ['id' => $extras['toppings']->id, 'children' => [
            ['id' => $extras['pearl']->id, 'num' => 2],
            ['id' => $extras['coconut']->id],
        ]],
    ]);

    // 非数量型 num 固定 1；数量型保留；价格取有效价
    expect($checked)->toHaveCount(2)
        ->and(collect($checked)->firstWhere('name', '杯型')['children'][0]['num'])->toBe(1)
        ->and(collect($checked)->firstWhere('name', '杯型')['children'][0]['price'])->toBe(100)
        ->and(collect($checked)->firstWhere('name', '小料')['children'][0]['num'])->toBe(2)
        ->and(collect($checked)->firstWhere('name', '小料')['children'][0]['price'])->toBe(250);
});

it('buyCheckAndGet：售罄/多选违规/数量超限/未知组拒绝', function () {
    $extras = createExtraLibrary();
    $product = createProductWithExtras($extras);
    $manager = new ExtraManager;
    $productExtras = $manager->resolveProductExtras($product);

    // 售罄选项
    try {
        $manager->buyCheckAndGet($productExtras, [
            ['id' => $extras['cup']->id, 'children' => [['id' => $extras['large']->id]]],
            ['id' => $extras['toppings']->id, 'children' => [['id' => $extras['cheese']->id]]],
        ]);
        $this->fail('should throw for sold out');
    } catch (ProductException $e) {
        expect($e->getMessage())->toContain('芝士');
    }

    // 单选组选多个
    try {
        $manager->buyCheckAndGet($productExtras, [
            ['id' => $extras['cup']->id, 'children' => [
                ['id' => $extras['large']->id], ['id' => $extras['middle']->id],
            ]],
        ]);
        $this->fail('should throw for multiple on single');
    } catch (ProductException $e) {
        expect($e->getMessage())->toContain('杯型');
    }

    // 数量超上限（珍珠 max 3）
    try {
        $manager->buyCheckAndGet($productExtras, [
            ['id' => $extras['cup']->id, 'children' => [['id' => $extras['large']->id]]],
            ['id' => $extras['toppings']->id, 'children' => [['id' => $extras['pearl']->id, 'num' => 5]]],
        ]);
        $this->fail('should throw for num max');
    } catch (ProductException $e) {
        expect($e->getMessage())->toContain('珍珠');
    }

    // 未挂载的组（伪造；必选组正常已选，避免先命中必选校验）
    try {
        $manager->buyCheckAndGet($productExtras, [
            ['id' => $extras['cup']->id, 'children' => [['id' => $extras['large']->id]]],
            ['id' => 99999, 'children' => [['id' => 1]]],
        ]);
        $this->fail('should throw for unknown group');
    } catch (ProductException $e) {
        expect($e->getMessage())->toBe(__('sn-product::product.pipes.extra_not_selectable'));
    }
});

it('buyCalc：整数分合计与展示文本', function () {
    $manager = new ExtraManager;

    [$amount, $texts] = $manager->buyCalc([
        ['id' => 1, 'name' => '小料', 'children' => [
            ['id' => 11, 'name' => '珍珠', 'num' => 2, 'price' => 250],
            ['id' => 12, 'name' => '椰果', 'num' => 1, 'price' => 300],
        ]],
        ['id' => 2, 'name' => '杯型', 'children' => [
            ['id' => 21, 'name' => '大杯', 'num' => 1, 'price' => 100],
        ]],
    ]);

    expect($amount)->toBe(900)      // 250×2 + 300 + 100
        ->and($texts)->toBe(['珍珠×2', '椰果', '大杯']);
});

// ========================= 详情组件 schema 与 buy 载荷 =========================

it('详情组件：specSchema.extras 结构与 buy 事件携带附加项', function () {
    $user = User::factory()->create();
    $extras = createExtraLibrary();
    $product = createProductWithExtras($extras);

    $component = livewire(ProductComponent::class, [
        'scopeType' => 'sn-test', 'scopeId' => 0, 'id' => $product->id, 'authUser' => $user,
    ]);

    $component->assertViewHas('specSchema', function (array $schema) {
        $extrasNode = $schema['extras'];

        return count($extrasNode) === 2
            && $extrasNode[0]['options'][0]['price'] === 250
            && $extrasNode[0]['options'][0]['price_parts']['amount'] === '2.50'
            && collect($extrasNode[0]['options'])->firstWhere('name', '芝士')['sold_out'] === true
            && $extrasNode[1]['is_require'] === true;
    });

    // buy：携带附加项载荷（珍珠×2 + 大杯）
    $variant = $product->variants->first();
    $component->call('buy', $variant->id, 1, [
        ['id' => $extras['toppings']->id, 'children' => [['id' => $extras['pearl']->id, 'num' => 2]]],
        ['id' => $extras['cup']->id, 'children' => [['id' => $extras['large']->id]]],
    ])->assertDispatched('sn-product-buy', function (string $name, array $params) use ($variant, $extras) {
        $items = json_decode($params['relate_items'], true);

        return $items[0]['product_variant_id'] === $variant->id
            && $items[0]['extras'] === [
                ['id' => $extras['toppings']->id, 'children' => [['id' => $extras['pearl']->id, 'num' => 2]]],
                ['id' => $extras['cup']->id, 'children' => [['id' => $extras['large']->id]]],
            ];
    });
});
