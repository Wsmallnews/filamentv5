<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Order\Exceptions\OrderCreateException;
use Wsmallnews\Order\OrderCreate;
use Wsmallnews\Order\Support\OrderPipes;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Enums\VariantStatus;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\Variant;

uses(RefreshDatabase::class);

// 管道注册表是进程级静态状态，测试间强制归零
beforeEach(function () {
    OrderPipes::flush();
});

/*
|--------------------------------------------------------------------------
| 测试数据
|--------------------------------------------------------------------------
*/

/**
 * 演示商品（单规格 + 一个 SKU）
 */
function addressTestProduct(): Product
{
    $product = Product::create([
        'publisher_type' => (new User)->getMorphClass(),
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-shop',
        'scope_id' => 0,
        'title' => '演示商品',
        'subtitle' => '演示副标题',
        'spec_type' => ProductSpecType::Single,
        'price' => sn_money()->fromDecimal(sn_money()->decimal(6900)),
        'stock_type' => ProductStockType::Stock,
        'stock_unit' => '件',
        'status' => ProductStatus::Up,
        'published_at' => now(),
    ]);

    Variant::create([
        'product_id' => $product->id,
        'product_spec_text' => [],
        'product_sn' => 'ADDR-' . $product->id,
        'spec_type' => ProductSpecType::Single,
        'price' => sn_money()->fromDecimal(sn_money()->decimal(6900)),
        'stock' => 500,
        'stock_unit' => '件',
        'stock_convert_num' => 1,
        'status' => VariantStatus::Up,
    ]);

    return $product->fresh()->load('variants');
}

/**
 * 买家的收货地址
 */
function addressTestAddress(Model $buyer)
{
    return $buyer->addresses()->create([
        'consignee' => '王五',
        'phone' => '13700137000',
        'country_code' => 'CN',
        'administrative_area' => '44',
        'administrative_area_name' => '广东省',
        'locality' => '4403',
        'locality_name' => '深圳市',
        'dependent_locality' => '440305',
        'dependent_locality_name' => '南山区',
        'township' => '440305001',
        'township_name' => '南头街道',
        'address_line1' => '测试街道 66 号',
    ]);
}

/**
 * 携地址下单
 */
function addressTestCreate(Model $buyer, Product $product, int $addressId): OrderCreate
{
    $create = new OrderCreate('product', $buyer);
    $create->setParams([
        'scope_type' => 'sn-shop',
        'scope_id' => 0,
        'relate_items' => [
            ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 1],
        ],
        'from' => 'product-detail',
        'platform' => 'web',
        'address_id' => $addressId,
    ]);

    return $create;
}

/*
|--------------------------------------------------------------------------
| 下单地址快照
|--------------------------------------------------------------------------
*/

it('下单：address_id 生成订单地址快照并累计使用次数', function () {
    $buyer = User::factory()->create();
    $product = addressTestProduct();
    $address = addressTestAddress($buyer);

    $orderCreate = addressTestCreate($buyer, $product, $address->id);
    $order = $orderCreate->create($orderCreate->calc('create'));

    expect($order->address)->not->toBeNull()
        ->and($order->address->consignee)->toBe('王五')
        ->and($order->address->region_label)->toBe('广东省　深圳市　南山区　南头街道')
        ->and($order->address->source_address_id)->toBe($address->id)
        ->and($address->fresh()->used_num)->toBe(1);

    // 地址簿后续变更不影响快照
    $address->update(['consignee' => '改名字了']);

    expect($order->address->fresh()->consignee)->toBe('王五');
});

it('下单：越权地址被拒绝', function () {
    $buyer = User::factory()->create();
    $other = User::factory()->create();
    $product = addressTestProduct();
    $address = addressTestAddress($other);

    $orderCreate = addressTestCreate($buyer, $product, $address->id);

    $orderCreate->create($orderCreate->calc('create'));
})->throws(OrderCreateException::class, '收货地址不属于当前用户');

it('下单：无 address_id 时正常落单（无地址快照）', function () {
    $buyer = User::factory()->create();
    $product = addressTestProduct();

    $create = new OrderCreate('product', $buyer);
    $create->setParams([
        'scope_type' => 'sn-shop',
        'scope_id' => 0,
        'relate_items' => [
            ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 1],
        ],
    ]);

    $order = $create->create($create->calc('create'));

    expect($order->address)->toBeNull();
});
