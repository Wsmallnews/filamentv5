<?php

use Cknow\Money\Money as CknowMoney;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Wsmallnews\Order\Models\Order;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Features\Money\MoneyManager;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| MoneyManager 输入口径
|--------------------------------------------------------------------------
*/

it('int 视为最小单位（分），string/float 视为十进制元', function () {
    expect(sn_money()->minor(10000))->toBe(10000)
        ->and(sn_money()->minor('100'))->toBe(10000)
        ->and(sn_money()->minor(100.5))->toBe(10050)
        ->and(sn_money()->minor('12.34'))->toBe(1234);
});

it('fromMinor 与 fromDecimal 构造 Money 并互转', function () {
    $money = sn_money()->fromMinor(123456);

    expect($money)->toBeInstanceOf(CknowMoney::class)
        ->and($money->getAmount())->toBe('123456')
        ->and($money->getCurrency()->getCode())->toBe('CNY')
        ->and(sn_money()->decimal($money))->toBe('1234.56')
        ->and(sn_money()->minor(sn_money()->fromDecimal('100')))->toBe(10000);
});

it('Money 对象透传并保留自带币种', function () {
    $usd = sn_money()->fromDecimal('10', 'USD');

    expect(sn_money()->money($usd)->getCurrency()->getCode())->toBe('USD')
        ->and(sn_money()->currencyOf($usd))->toBe('USD')
        ->and(sn_money()->minor($usd))->toBe(1000);
});

it('站点默认币种解析链：app.currency 覆盖 → sn-support.currency → CNY', function () {
    expect(sn_money()->defaultCurrency())->toBe('CNY')
        ->and(Number::defaultCurrency())->toBe('CNY')
        ->and(CknowMoney::getDefaultCurrency())->toBe('CNY');

    // 消费应用显式配置 app.currency 时优先
    config(['app.currency' => 'USD']);
    expect(sn_money()->defaultCurrency())->toBe('USD');

    // 未配置 app.currency 时回落 sn-support.currency（包默认，消费应用零配置）
    config(['app.currency' => null]);
    expect(sn_money()->defaultCurrency())->toBe('CNY');
});

/*
|--------------------------------------------------------------------------
| 运算与分摊
|--------------------------------------------------------------------------
*/

it('加减乘与求和保持整数分精度', function () {
    expect(sn_money()->minor(sn_money()->add('0.1', '0.2')))->toBe(30)
        ->and(sn_money()->minor(sn_money()->subtract('100', '0.01')))->toBe(9999)
        ->and(sn_money()->minor(sn_money()->multiply('12.34', 3)))->toBe(3702)
        ->and(sn_money()->minor(sn_money()->sum(['1.11', '2.22', 333])))->toBe(666);
});

it('分摊采用余数分配法，总额不丢分', function () {
    $allocated = sn_money()->allocate(10000, [1, 1, 1]);

    expect(array_map(fn ($money) => (int) $money->getAmount(), $allocated))->toBe([3334, 3333, 3333])
        ->and(sn_money()->minor(sn_money()->sum($allocated)))->toBe(10000);

    // 非等比：100 元按 2:3 分摊
    $ratios = sn_money()->allocate(10000, [2, 3]);
    expect(array_map(fn ($money) => (int) $money->getAmount(), $ratios))->toBe([4000, 6000]);
});

it('混币种运算抛出异常', function () {
    sn_money()->add(sn_money()->fromDecimal('1', 'CNY'), sn_money()->fromDecimal('1', 'USD'));
})->throws(SupportException::class);

it('负倍数被拒绝', function () {
    sn_money()->multiply('1.00', -2);
})->throws(SupportException::class);

it('判零与判负', function () {
    expect(sn_money()->isNegative('-0.01'))->toBeTrue()
        ->and(sn_money()->isZero(0))->toBeTrue()
        ->and(sn_money()->isPositive('0.01'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| 格式化（展示层）
|--------------------------------------------------------------------------
*/

it('format 输出带符号的展示金额', function () {
    expect(sn_money()->format(123456))->toContain('1,234.56')
        ->and(sn_money()->format(123456, 'USD'))->toContain('$')
        ->and(sn_money()->symbol())->toBe(sn_money()->symbol('CNY'))
        ->and(sn_money()->symbol('USD'))->not->toBeEmpty()
        ->and(preg_match('/\d/', sn_money()->symbol('USD')))->toBe(0);
});

/*
|--------------------------------------------------------------------------
| JSON 金额（整数分口径）
|--------------------------------------------------------------------------
*/

it('jsonFields 统一为整数分，jsonSum 直接求和', function () {
    $fields = sn_money()->jsonFields(['goods_amount' => 10000, 'freight_amount' => '12.34']);

    expect($fields)->toBe(['goods_amount' => 10000, 'freight_amount' => 1234])
        ->and(sn_money()->jsonSum($fields))->toBe(11234)
        ->and(sn_money()->jsonSum(['a' => '0.1', 'b' => '0.2']))->toBe(30);
});

it('jsonFormat 输出展示字符串', function () {
    $formatted = sn_money()->jsonFormat(['goods_amount' => 123456]);

    expect($formatted['goods_amount'])->toContain('1,234.56');
});

/*
|--------------------------------------------------------------------------
| MoneyCast 币种绑定
|--------------------------------------------------------------------------
*/

it('订单金额 cast 绑定行内 currency 列并回写', function () {
    $order = Order::create([
        'scope_type' => 'sn-order',
        'scope_id' => 0,
        'type' => 'product',
        'order_sn' => 'SN' . now()->format('YmdHis'),
        'buyer_type' => 'user',
        'buyer_id' => 1,
        'status' => 'unpaid',
        'pay_status' => 'unpaid',
        'delivery_status' => 'waiting_send',
        'refund_status' => 'unrefund',
        'pay_fee' => 100,          // 标量按元传入
    ]);

    expect($order->getRawOriginal('pay_fee'))->toBe(10000)
        ->and($order->currency)->toBe('CNY')
        ->and($order->pay_fee->getCurrency()->getCode())->toBe('CNY')
        ->and($order->pay_fee->getAmount())->toBe('10000');

    // 刷新后仍按行内币种构造
    $order->refresh();
    expect($order->pay_fee->getAmount())->toBe('10000');
});

it('订单行内币种为 USD 时金额按 USD 构造', function () {
    $order = Order::create([
        'scope_type' => 'sn-order',
        'scope_id' => 0,
        'type' => 'product',
        'order_sn' => 'SN' . now()->format('YmdHis') . 'U',
        'buyer_type' => 'user',
        'buyer_id' => 1,
        'status' => 'unpaid',
        'pay_status' => 'unpaid',
        'delivery_status' => 'waiting_send',
        'refund_status' => 'unrefund',
        'currency' => 'USD',
        'pay_fee' => 10,
    ]);

    expect($order->currency)->toBe('USD')
        ->and($order->getRawOriginal('pay_fee'))->toBe(1000)
        ->and($order->pay_fee->getCurrency()->getCode())->toBe('USD');
});

it('商品价格 cast 币种列可空回落站点默认', function () {
    $product = Product::create([
        'title' => '测试商品',
        'publisher_type' => 'user',
        'publisher_id' => 1,
        'spec_type' => 'single',
        'stock_type' => 'stock',
        'status' => 'up',
        'price' => '66.66',
    ]);

    expect($product->getRawOriginal('price'))->toBe(6666)
        ->and($product->price->getCurrency()->getCode())->toBe('CNY');

    // 显式定价币种生效
    $usdProduct = Product::create([
        'title' => '美元商品',
        'publisher_type' => 'user',
        'publisher_id' => 1,
        'spec_type' => 'single',
        'stock_type' => 'stock',
        'status' => 'up',
        'currency' => 'USD',
        'price' => '9.99',
    ]);
    expect($usdProduct->price->getCurrency()->getCode())->toBe('USD')
        ->and($usdProduct->price->getAmount())->toBe('999');
});

it('MoneyManager 可经容器解析（sn_money 与 app 等价）', function () {
    expect(sn_money())->toBeInstanceOf(MoneyManager::class)
        ->and(app(MoneyManager::class))->toBeInstanceOf(MoneyManager::class);
});
