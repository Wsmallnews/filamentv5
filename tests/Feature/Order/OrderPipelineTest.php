<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Member\Models\Member;
use Wsmallnews\Order\Contracts\Shortcuts\ShortcutInterface;
use Wsmallnews\Order\Exceptions\OrderCreateException;
use Wsmallnews\Order\Exceptions\OrderException;
use Wsmallnews\Order\Models\Order;
use Wsmallnews\Order\Models\OrderAction;
use Wsmallnews\Order\OrderCreate;
use Wsmallnews\Order\Support\FieldInfo;
use Wsmallnews\Order\Support\OrderPipes;
use Wsmallnews\Pay\Contracts\WalletOperator;
use Wsmallnews\Pay\Enums\PayStatus;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Enums\VariantStatus;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\Variant;
use Wsmallnews\Profile\Models\Address;
use Wsmallnews\Shop\Order\Shortcuts\ProductShortcut;
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\Support\Exceptions\SupportException;

use function Pest\Livewire\livewire;

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
 * 演示商品（单规格 + 一个 SKU）。
 */
function pipelineProduct(int $priceMinor = 6900, int $stock = 500, ?int $originalPriceMinor = null): Product
{
    $product = Product::create([
        'publisher_type' => (new User)->getMorphClass(),
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-shop',
        'scope_id' => 0,
        'title' => '演示商品',
        'subtitle' => '演示副标题',
        'spec_type' => ProductSpecType::Single,
        'price' => sn_money()->fromDecimal(sn_money()->decimal($priceMinor)),
        'original_price' => $originalPriceMinor ? sn_money()->fromDecimal(sn_money()->decimal($originalPriceMinor)) : null,
        'stock_type' => $stock > 0 ? ProductStockType::Stock : ProductStockType::Infinite,
        'stock_unit' => '件',
        'status' => ProductStatus::Up,
        'published_at' => now(),
    ]);

    Variant::create([
        'product_id' => $product->id,
        'product_spec_text' => [],
        'product_sn' => 'TEST-' . $product->id,
        'spec_type' => ProductSpecType::Single,
        'price' => sn_money()->fromDecimal(sn_money()->decimal($priceMinor)),
        'original_price' => $originalPriceMinor ? sn_money()->fromDecimal(sn_money()->decimal($originalPriceMinor)) : null,
        'stock' => $stock,
        'stock_unit' => '件',
        'stock_convert_num' => 1,
        'status' => VariantStatus::Up,
    ]);

    return $product->fresh()->load('variants');
}

/**
 * 下单引擎（商城 scope + product 配方）。
 */
function pipelineCreate(Model $buyer, array $relateItems): OrderCreate
{
    $create = new OrderCreate('product', $buyer);
    $create->setParams([
        'scope_type' => 'sn-shop',
        'scope_id' => 0,
        'relate_items' => $relateItems,
        'from' => 'product-detail',
        'platform' => 'web',
    ]);

    return $create;
}

/**
 * 余额钱包 fake（支付 + 快照回款，独立于 PayTest 的同名类）。
 */
class PipelineFakeWallet implements WalletOperator
{
    /** @var array<int, int> payer_sn_id => 余额（分） */
    public static array $balances = [];

    public static function setBalance(int $payerId, int $minor): void
    {
        static::$balances[$payerId] = $minor;
    }

    public static function balance(int $payerId): int
    {
        return static::$balances[$payerId] ?? 0;
    }

    public function sufficient(HasSnIdentifiable $payer, string $walletType, int $minorAmount, string $orderCurrency): bool
    {
        return static::balance($payer->getSnId()) >= $minorAmount;
    }

    public function deduct(HasSnIdentifiable $payer, string $walletType, int $minorAmount, string $orderCurrency, array $meta = []): array
    {
        static::$balances[$payer->getSnId()] -= $minorAmount;

        return [
            'wallet_amount' => $minorAmount,
            'wallet_currency' => $orderCurrency,
            'rate' => ['anchor' => '1:1', 'market' => '1'],
            'transaction_id' => 'pipeline-deduct-' . ($meta['pay_sn'] ?? ''),
        ];
    }

    public function credit(HasSnIdentifiable $payer, string $walletType, int $minorAmount, string $orderCurrency, array $snapshot, array $meta = []): array
    {
        static::$balances[$payer->getSnId()] += $minorAmount;

        return [
            'wallet_amount' => $minorAmount,
            'wallet_currency' => $orderCurrency,
            'rate' => ['anchor' => '1:1', 'market' => '1'],
            'transaction_id' => 'pipeline-credit-' . ($meta['refund_sn'] ?? ''),
        ];
    }
}

function bindPipelineWallet(): void
{
    app()->bind(WalletOperator::class, PipelineFakeWallet::class);
    PipelineFakeWallet::$balances = [];
}

/*
|--------------------------------------------------------------------------
| 场景注册表与管道注册表
|--------------------------------------------------------------------------
*/

it('product 类型解析到商城配方（注册表反查）', function () {
    $shortcut = app(Wsmallnews\Order\Order::class)->shortcut('product');

    expect($shortcut)->toBeInstanceOf(ProductShortcut::class);
    expect(is_subclass_of($shortcut, ShortcutInterface::class))->toBeTrue();

    $order = Order::create([
        'scope_type' => 'sn-shop',
        'scope_id' => 0,
        'type' => 'product',
        'order_sn' => 'SN-REG-1',
        'buyer_type' => 'user',
        'buyer_id' => 1,
        'currency' => 'CNY',
        'status' => 'unpaid',
        'pay_status' => 'unpaid',
        'delivery_status' => 'waiting_send',
        'refund_status' => 'unrefund',
        'pay_fee' => sn_money()->fromMinor(100),
        'original_pay_fee' => sn_money()->fromMinor(100),
        'remain_pay_fee' => sn_money()->fromMinor(100),
    ]);

    expect(app(Wsmallnews\Order\Order::class)->resolveShortcut($order))->toBeInstanceOf(ProductShortcut::class);
});

it('未注册的订单类型反查配方抛异常', function () {
    app(Wsmallnews\Order\Order::class)->shortcut('not-exists');
})->throws(OrderException::class, 'not registered');

it('OrderPipes 注册表：按锚点插拔管道', function () {
    OrderPipes::flush();

    $base = ['start' => 'StartPipe', 'product' => 'ProductPipe'];

    // after / before
    OrderPipes::scene('product')->stage('calc')->after('product', 'CouponPipe', 'coupon');
    OrderPipes::scene('product')->stage('calc')->before('product', 'VipPricePipe', 'vip');
    expect(OrderPipes::apply('product', 'calc', $base))->toBe([
        'start' => 'StartPipe',
        'vip' => 'VipPricePipe',
        'product' => 'ProductPipe',
        'coupon' => 'CouponPipe',
    ]);

    // replace / remove
    OrderPipes::flush();
    OrderPipes::scene('product')->stage('calc')->replace('product', 'StoreProductPipe');
    OrderPipes::scene('product')->stage('calc')->remove('start');
    expect(OrderPipes::apply('product', 'calc', $base))->toBe(['StoreProductPipe' => 'StoreProductPipe']);

    // 锚点不存在时静默跳过；未注册场景原样返回
    OrderPipes::flush();
    OrderPipes::scene('product')->stage('calc')->after('ghost', 'AnyPipe');
    expect(OrderPipes::apply('product', 'calc', $base))->toBe($base)
        ->and(OrderPipes::apply('other', 'calc', $base))->toBe($base);

    // 场景隔离：注册只影响目标场景
    OrderPipes::flush();
    OrderPipes::scene('product')->stage('check')->append('LimitPipe');
    expect(OrderPipes::apply('product', 'check', $base))->toBe([...$base, 'LimitPipe' => 'LimitPipe'])
        ->and(OrderPipes::apply('product', 'calc', $base))->toBe($base);

    OrderPipes::flush();
});

/*
|--------------------------------------------------------------------------
| 试算与落单（整数分口径）
|--------------------------------------------------------------------------
*/

it('FieldInfo 工厂：field_type 默认 amount，可传 text 等类型', function () {
    $amount = FieldInfo::make(fieldName: 'product_amount', textKey: 'sn-product::product.order_fields.product_amount', value: 13800);
    expect($amount['field_type'])->toBe('amount')
        ->and($amount['value'])->toBe(13800);

    $text = FieldInfo::make(fieldName: 'delivery_no', textKey: 'sn-order::order.order_fields.delivery_no', value: 'SF123456', fieldType: 'text');
    expect($text['field_type'])->toBe('text')
        ->and($text['value'])->toBe('SF123456');
});

it('试算：整数分金额、JSON 字段集与商品快照', function () {
    $buyer = User::factory()->create();
    $product = pipelineProduct(6900, 500, 9900);     // ¥69，划线 ¥99

    $rocket = pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 2],
    ])->calc('calc');

    $payloads = $rocket->getPayloads();

    // 金额全部整数分：69 × 2 = 13800；划线 99 × 2 = 19800
    expect($payloads['order_amount'])->toBe(13800)
        ->and($payloads['original_order_amount'])->toBe(19800)
        ->and($payloads['pay_fee'])->toBe(13800)
        ->and($payloads['relate_amount'])->toBe(13800)
        ->and($payloads['amount_fields'])->toBe(['relate_amount' => 13800])
        ->and($payloads['original_amount_fields'])->toBe(['relate_original_amount' => 19800])
        ->and($payloads['discount_fields'])->toBe([]);

    // 商品快照
    $item = $payloads['relate_items'][0];
    expect($item['relate_type'])->toBe('product')
        ->and($item['relate_id'])->toBe($product->id)
        ->and($item['relate_title'])->toBe('演示商品')
        ->and($item['relate_price'])->toBe(6900)
        ->and($item['relate_original_price'])->toBe(9900)
        ->and($item['relate_amount'])->toBe(13800)
        ->and($item['relate_num'])->toBe(2)
        ->and($item['amount_fields'])->toBe(['product_amount' => 13800])
        ->and($item['relate_options']['product_variant_id'])->toBe($product->variants->first()->id)
        // 单价快照（整数分）
        ->and($item['relate_options']['product_price'])->toBe(6900)
        ->and($item['relate_options']['original_product_price'])->toBe(9900)
        ->and($item['relate_options']['product_spec_type'])->toBe('single')
        // 属性集合 = 入参属性 + 规格值（空规格值剔除，单规格商品为空数组）
        ->and($item['relate_attributes'])->toBe([]);

    // 展示字段集：text/desc 存翻译键（渲染侧求值），value 整数分（渲染侧格式化）
    expect($payloads['amount_fields_info']['relate_amount']['value'])->toBe(13800)
        ->and($payloads['amount_fields_info']['relate_amount']['text'])->toBe('sn-product::product.order_fields.relate_amount')
        ->and($payloads['amount_fields_info']['relate_amount']['desc'])->toBe('sn-product::product.order_fields.desc_items')
        ->and($payloads['amount_fields_info']['relate_amount']['desc_params'])->toBe(['num' => 2])
        // 翻译键可解析（多语言切换的数据基础）
        ->and(__($payloads['amount_fields_info']['relate_amount']['text']))->toBe('商品总价')
        ->and(__('sn-product::product.order_fields.desc_items', ['num' => 2]))->toBe('共 2 件商品');
});

it('落单：订单与明细落库（金额 fromMinor 口径 + 状态初始）', function () {
    $buyer = User::factory()->create();
    $product = pipelineProduct(6900, 500);

    $create = pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 2],
    ]);
    $rocket = $create->calc('create');
    $order = $create->create($rocket);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->type)->toBe('product')
        ->and($order->currency)->toBe('CNY')
        ->and($order->status->value)->toBe('unpaid')
        ->and($order->pay_fee->getAmount())->toBe('13800')
        ->and($order->original_pay_fee->getAmount())->toBe('13800')
        ->and($order->remain_pay_fee->getAmount())->toBe('13800')
        ->and($order->amount_fields)->toBe(['relate_amount' => 13800])
        ->and($order->order_amount->getAmount())->toBe('13800')
        ->and($order->buyer_type)->toBe((new User)->getMorphClass())
        ->and($order->buyer_id)->toBe($buyer->id);

    $item = $order->orderItems->first();
    expect($item->relate_title)->toBe('演示商品')
        ->and($item->relate_price->getAmount())->toBe('6900')
        ->and($item->amount->getAmount())->toBe('13800')
        ->and($item->total_fee->getAmount())->toBe('13800')
        ->and($item->pay_status->value)->toBe('unpaid')
        ->and($item->relate_options['product_sn'])->toBe('TEST-' . $product->id);
});

it('多商品混合购买：逐项计价与订单合计', function () {
    $buyer = User::factory()->create();
    $a = pipelineProduct(6900, 500);       // ¥69 × 1
    $b = pipelineProduct(4500, 500);       // ¥45 × 3

    $rocket = pipelineCreate($buyer, [
        ['product_id' => $a->id, 'product_variant_id' => $a->variants->first()->id, 'product_num' => 1],
        ['product_id' => $b->id, 'product_variant_id' => $b->variants->first()->id, 'product_num' => 3],
    ])->calc('calc');

    $payloads = $rocket->getPayloads();

    expect($payloads['relate_items'])->toHaveCount(2)
        ->and($payloads['relate_items'][0]['relate_amount'])->toBe(6900)
        ->and($payloads['relate_items'][1]['relate_amount'])->toBe(13500)
        ->and($payloads['order_amount'])->toBe(20400)     // 6900 + 13500
        ->and($payloads['pay_fee'])->toBe(20400);
});

it('属性集合合并入参属性与规格值（空值剔除）', function () {
    $buyer = User::factory()->create();
    $product = pipelineProduct(6900, 500);

    // 入参携带属性（购物车加的小料等），单规格商品规格值为空串 → 剔除
    $rocket = pipelineCreate($buyer, [
        [
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'product_num' => 1,
            'relate_attributes' => ['加冰', '半糖'],
        ],
    ])->calc('calc');

    expect($rocket->getPayloads()['relate_items'][0]['relate_attributes'])->toBe(['加冰', '半糖']);
});

it('库存不足拒绝下单（基础库存数量口径）', function () {
    $buyer = User::factory()->create();
    $product = pipelineProduct(6900, 3);       // 库存 3 件

    pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 4],
    ])->calc('calc');
})->throws(OrderCreateException::class, '商品库存不足：演示商品');

it('多单位商品按换算比例校验库存（1 箱 = 12 瓶）', function () {
    $buyer = User::factory()->create();
    $product = pipelineProduct(1200, 20);      // 基础库存 20 瓶
    $product->update(['spec_type' => ProductSpecType::Unit]);
    $product->variants->first()->update(['stock_convert_num' => 12]);

    // 买 2 箱 = 24 瓶 > 20 瓶库存 → 拒绝
    pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 2],
    ])->calc('calc');
})->throws(OrderCreateException::class, '商品库存不足');

/*
|--------------------------------------------------------------------------
| 端到端：落单 → 支付 → 退款
|--------------------------------------------------------------------------
*/

/**
 * 付款人（Member 经 support 的 HasSnIdentifiable 身份契约接入，无域包接口依赖）。
 */
function pipelineMember(): Member
{
    return Member::create(['user_id' => User::factory()->create()->id, 'status' => 'normal']);
}

it('端到端：落单 → 余额支付 → 订单 paid', function () {
    bindPipelineWallet();

    $buyer = pipelineMember();
    PipelineFakeWallet::setBalance($buyer->getSnId(), 13800);

    $product = pipelineProduct(6900, 500);
    $create = pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 2],
    ]);
    $rocket = $create->calc('create');
    $order = $create->create($rocket);

    expect($order->pay_status->value)->toBe('unpaid');

    // 余额支付（同步完成路径：checkAndPaid → 订单状态流转）
    $payResult = app('sn-pay')->payer($buyer)->payable($order)->channel('money', 'balance')->pay();

    expect($payResult->payRecord->status)->toBe(PayStatus::Paid)
        ->and(PipelineFakeWallet::balance($buyer->getSnId()))->toBe(0);

    $order = $order->fresh();
    expect($order->pay_status->value)->toBe('paid')
        ->and($order->status->value)->toBe('paid')
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->orderItems->every(fn ($item) => $item->pay_status->value === 'paid'))->toBeTrue();
});

it('端到端：部分退款 → hasrefund，补齐全额 → refunded', function () {
    bindPipelineWallet();

    $buyer = pipelineMember();
    PipelineFakeWallet::setBalance($buyer->getSnId(), 13800);

    $product = pipelineProduct(6900, 500);
    $create = pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 2],
    ]);
    $rocket = $create->calc('create');
    $order = $create->create($rocket);

    $payResult = app('sn-pay')->payer($buyer)->payable($order)->channel('money', 'balance')->pay();

    // 部分退款 5000 分（RefundSucceeded → 订单 hasrefund）
    app('sn-pay')->payer($buyer)->channel('money', 'balance')->refund($payResult->payRecord, 5000);

    $order = $order->fresh();
    expect($order->refund_status->value)->toBe('hasrefund')
        ->and($order->orderItems->first()->refund_status->value)->toBe('unrefund');      // 部分退款不动明细

    // 补齐全额 8800 分 → refunded，明细同步
    app('sn-pay')->payer($buyer)->channel('money', 'balance')->refund($payResult->payRecord, 8800);

    $order = $order->fresh();
    expect($order->refund_status->value)->toBe('refunded')
        ->and($order->orderItems->first()->refund_status->value)->toBe('refunded')
        ->and(PipelineFakeWallet::balance($buyer->getSnId()))->toBe(13800);      // 快照 1:1 回款

    // 操作日志：支付 1 条 + 退款 2 条（退款含全额/部分文案）
    $actions = OrderAction::query()->where('order_id', $order->id)->get();
    expect($actions->count())->toBe(3)
        ->and($actions->pluck('message')->contains('订单部分退款'))->toBeTrue()
        ->and($actions->pluck('message')->contains('订单全额退款'))->toBeTrue();
});

it('确认组件渲染：整数分金额格式化展示与商品快照', function () {
    $buyer = pipelineMember();
    $product = pipelineProduct(6900, 500);

    livewire('sn-order::components.confirm', [
        'buyer' => $buyer,
        'order_type' => 'product',
        'relateItems' => [
            ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 2],
        ],
        'from' => 'product-detail',
        'scopeType' => 'sn-shop',
        'scopeId' => 0,
    ])
        ->assertSee('演示商品')
        ->assertSee('69.00')          // 单价
        ->assertSee('138.00')         // 小计与应付
        ->assertSee('共 2 件商品');
});

it('FieldInfo 解析侧：sorted 排序 + render 整行 HtmlString', function () {
    // 排序：跨管道乱序合并后按 order_column 归位
    $fields = [
        'coupon' => FieldInfo::make('coupon_amount', 'sn-order::order.order_fields.coupon', 500, orderColumn: 3),
        'delivery' => FieldInfo::make('delivery_amount', 'sn-order::order.order_fields.delivery', 0, orderColumn: 2),
        'relate_amount' => FieldInfo::make('relate_amount', 'sn-product::product.order_fields.relate_amount', 13800, orderColumn: 1, highLight: true, descKey: 'sn-product::product.order_fields.desc_items', descParams: ['num' => 2]),
    ];
    $sorted = FieldInfo::sorted($fields);
    expect(array_column($sorted, 'field_name'))->toBe(['relate_amount', 'delivery_amount', 'coupon_amount']);

    // 渲染：翻译键求值 + 颜色映射 + 金额格式化
    $html = FieldInfo::render($sorted[0])->toHtml();
    expect($html)->toContain('商品总价')
        ->and($html)->toContain('¥138.00')
        ->and($html)->toContain('sn-primary-text font-bold')
        ->and($html)->toContain('共 2 件商品');

    // 非高亮 → 常规正文色；text 类型值原样输出
    $normal = FieldInfo::render(FieldInfo::make('delivery_no', 'sn-order::order.order_fields.delivery_no', 'SF123456', fieldType: 'text'))->toHtml();
    expect($normal)->toContain('sn-neutral-text')
        ->and($normal)->toContain('SF123456');

    // 存量裸文本兼容：非翻译键原样回显
    expect(FieldInfo::render(['text' => '历史文案', 'value' => 100])->toHtml())->toContain('历史文案');
});

/*
|--------------------------------------------------------------------------
| 收货地址快照（profile 地址簿 → 订单地址）
|--------------------------------------------------------------------------
*/

it('落单带地址：归属校验后生成订单地址快照并累计使用次数', function () {
    $buyer = pipelineMember();
    $address = Address::create([
        'owner_type' => (new Member)->getMorphClass(),
        'owner_id' => $buyer->id,
        'consignee' => '张三',
        'phone' => '13800138000',
        'administrative_area_name' => '广东省',
        'locality_name' => '深圳市',
        'dependent_locality_name' => '南山区',
        'address_line1' => '科技园某某大厦 101',
        'is_default' => true,
    ]);

    $product = pipelineProduct(6900, 500);

    $create = pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 1],
    ]);
    // 地址参数（Confirm 组件事件流最终落到 params.address_id）
    $create->setParams(array_merge($create->params, ['address_id' => $address->id]));

    $rocket = $create->calc('create');
    $order = $create->create($rocket);

    $orderAddress = $order->address;
    expect($orderAddress)->not->toBeNull()
        ->and($orderAddress->consignee)->toBe('张三')
        ->and($orderAddress->phone)->toBe('13800138000')
        ->and($orderAddress->administrative_area_name)->toBe('广东省')
        ->and($orderAddress->address_line1)->toBe('科技园某某大厦 101')
        ->and($orderAddress->source_address_id)->toBe($address->id)
        ->and($address->fresh()->used_num)->toBe(1);      // 使用次数累计
});

it('非本人地址被拒绝下单（归属校验）', function () {
    $buyer = pipelineMember();
    $other = pipelineMember();
    $address = Address::create([
        'owner_type' => (new Member)->getMorphClass(),
        'owner_id' => $other->id,           // 别人的地址
        'consignee' => '李四',
        'phone' => '13900139000',
        'address_line1' => '别处',
    ]);

    $product = pipelineProduct(6900, 500);

    $create = pipelineCreate($buyer, [
        ['product_id' => $product->id, 'product_variant_id' => $product->variants->first()->id, 'product_num' => 1],
    ]);
    $create->setParams(array_merge($create->params, ['address_id' => $address->id]));

    $rocket = $create->calc('create');
    $create->create($rocket);
})->throws(SupportException::class, '收货地址不属于当前用户');
