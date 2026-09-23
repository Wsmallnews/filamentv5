<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wsmallnews\Member\Models\Member;
use Wsmallnews\Order\Enums\Order\PayStatus as OrderPayStatus;
use Wsmallnews\Order\Models\Order;
use Wsmallnews\Pay\Contracts\PayableInterface;
use Wsmallnews\Pay\Contracts\PayerInterface;
use Wsmallnews\Pay\Contracts\ThirdAdapterInterface;
use Wsmallnews\Pay\Contracts\WalletOperator;
use Wsmallnews\Pay\Data\NotifyPayload;
use Wsmallnews\Pay\Data\PayPayload;
use Wsmallnews\Pay\Data\RefundPayload;
use Wsmallnews\Pay\Enums\PayStatus;
use Wsmallnews\Pay\Enums\RefundStatus;
use Wsmallnews\Pay\Events\PaySucceeded;
use Wsmallnews\Pay\Events\RefundSucceeded;
use Wsmallnews\Pay\Exceptions\PayException;
use Wsmallnews\Pay\Models\PayRecord;
use Wsmallnews\Pay\PayManager;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 测试替身：假钱包 + 假第三方渠道
|--------------------------------------------------------------------------
*/

class FakeWalletOperator implements WalletOperator
{
    /** @var array<int, int> member_id => 余额（分） */
    public static array $balances = [];

    public static function setBalance(int $payerId, int $minor): void
    {
        static::$balances[$payerId] = $minor;
    }

    public static function balance(int $payerId): int
    {
        return static::$balances[$payerId] ?? 0;
    }

    public function sufficient(PayerInterface $payer, string $walletType, int $minorAmount, string $orderCurrency): bool
    {
        return static::balance($payer->getSnId()) >= $minorAmount;
    }

    public function deduct(PayerInterface $payer, string $walletType, int $minorAmount, string $orderCurrency, array $meta = []): array
    {
        static::$balances[$payer->getSnId()] = static::balance($payer->getSnId()) - $minorAmount;

        return [
            'wallet_amount' => $minorAmount,
            'wallet_currency' => $orderCurrency,
            'rate' => ['anchor' => '1:1', 'market' => '1'],
            'transaction_id' => 'fake-deduct-' . ($meta['pay_sn'] ?? ''),
        ];
    }

    public function credit(PayerInterface $payer, string $walletType, int $minorAmount, string $orderCurrency, array $snapshot, array $meta = []): array
    {
        static::$balances[$payer->getSnId()] = static::balance($payer->getSnId()) + $minorAmount;

        return ['wallet_amount' => $minorAmount, 'transaction_id' => 'fake-credit-' . ($meta['refund_sn'] ?? '')];
    }
}

class FakeThirdAdapter implements ThirdAdapterInterface
{
    public function __construct(protected PayManager $payManager) {}

    public function getChannel(): string
    {
        return 'fake';
    }

    public function pay(PayPayload $payload): array
    {
        return ['status' => PayStatus::Unpaid, 'real_fee' => $payload->amount];
    }

    public function prepay(PayRecord $payRecord, PayPayload $payload, array $extra = []): mixed
    {
        return ['fake_prepay' => true, 'pay_sn' => $payRecord->pay_sn];
    }

    public function verifyNotify(Request $request): NotifyPayload
    {
        throw new RuntimeException('not used in tests');
    }

    public function verifyRefundNotify(Request $request): NotifyPayload
    {
        throw new RuntimeException('not used in tests');
    }

    public function buildNotifyResponse(bool $success): Response
    {
        return new Response($success ? 'fake-ok' : 'fake-fail');
    }

    public function refund(RefundPayload $payload): array
    {
        return ['status' => RefundStatus::Completed, 'sdk_result' => ['fake_refund' => true]];
    }
}

/*
|--------------------------------------------------------------------------
| 工具
|--------------------------------------------------------------------------
*/

function createPayTestOrder(int $payFeeMinor = 10000): Order
{
    return Order::create([
        'scope_type' => 'sn-order',
        'scope_id' => 0,
        'type' => 'product',
        'order_sn' => 'SN' . uniqid(),
        'buyer_type' => 'member',
        'buyer_id' => 1,
        'currency' => 'CNY',
        'status' => 'unpaid',
        'pay_status' => 'unpaid',
        'delivery_status' => 'waiting_send',
        'refund_status' => 'unrefund',
        'pay_fee' => sn_money()->fromMinor($payFeeMinor),
        'original_pay_fee' => sn_money()->fromMinor($payFeeMinor),
        'remain_pay_fee' => sn_money()->fromMinor($payFeeMinor),
    ]);
}

function bindFakeWallet(): void
{
    app()->bind(WalletOperator::class, FakeWalletOperator::class);
    config(['sn-pay.channels.money.enabled' => true]);
}

function createPayTestMember(): Member
{
    return Member::create(['user_id' => rand(1, 999999), 'status' => 'normal']);
}

/*
|--------------------------------------------------------------------------
| 契约层
|--------------------------------------------------------------------------
*/

it('订单实现 pay 包支付契约并提供币种快照', function () {
    $order = createPayTestOrder();

    expect(Order::class)->toImplement(PayableInterface::class)
        ->and($order->getMorphClass())->toBe('sn_order')
        ->and($order->getPayCurrency())->toBe('CNY')
        ->and($order->getRemainPayFee())->toBe(10000);
});

it('Member 实现 PayerInterface 并具备付款便捷入口', function () {
    $member = createPayTestMember();

    expect(Member::class)->toImplement(PayerInterface::class)
        ->and($member->payerMask())->toBe((string) $member->getKey())
        ->and($member->pay())->toBeInstanceOf(PayManager::class);
});

it('自定义渠道经 extend 注册即可用于支付（聚合平台接入约定）', function () {
    app('sn-pay')->addChannel('fake', FakeThirdAdapter::class);

    $order = createPayTestOrder();

    $result = app('sn-pay')
        ->payable($order)
        ->channel('fake', 'scan')
        ->pay();

    expect($result->payRecord->status)->toBe(PayStatus::Unpaid)
        ->and($result->sdkResult)->toBe(['fake_prepay' => true, 'pay_sn' => $result->payRecord->pay_sn]);
});

/*
|--------------------------------------------------------------------------
| 余额支付（WalletOperator 契约）
|--------------------------------------------------------------------------
*/

it('未绑定钱包契约时余额通道不可用', function () {
    config(['sn-pay.channels.money.enabled' => true]);

    // wallet 扩展安装后始终绑定契约，此处显式卸载以模拟未接入钱包的状态
    app()->offsetUnset(WalletOperator::class);

    app('sn-pay')->payable(createPayTestOrder())->channel('money', 'balance')->pay();
})->throws(PayException::class, 'Wallet operator is not bound');

it('余额支付完成扣款并流转订单状态（含汇率快照与事件）', function () {
    Event::fake([PaySucceeded::class]);
    bindFakeWallet();

    $member = createPayTestMember();
    FakeWalletOperator::setBalance($member->getSnId(), 10000);

    $order = createPayTestOrder(10000);

    $result = app('sn-pay')
        ->payer($member)
        ->payable($order)
        ->channel('money', 'balance')
        ->pay();

    expect($result->isPaid())->toBeTrue()
        ->and($result->payRecord->channel)->toBe('money')
        ->and($result->payRecord->currency)->toBe('CNY')
        ->and(sn_money()->minor($result->payRecord->pay_fee))->toBe(10000)
        ->and($result->payRecord->options['wallet'])->toHaveKey('rate')
        ->and(FakeWalletOperator::balance($member->getSnId()))->toBe(0)
        ->and($order->fresh()->pay_status)->toBe(OrderPayStatus::Paid)
        ->and($order->fresh()->getRemainPayFee())->toBe(0);

    Event::assertDispatched(PaySucceeded::class);
});

it('余额不足时支付失败且不落支付单', function () {
    bindFakeWallet();

    $member = createPayTestMember();
    FakeWalletOperator::setBalance($member->getSnId(), 100);

    $order = createPayTestOrder(10000);

    app('sn-pay')->payer($member)->payable($order)->channel('money', 'balance')->pay();
})->throws(PayException::class, 'Insufficient wallet balance');

/*
|--------------------------------------------------------------------------
| 分批支付（定金模式）
|--------------------------------------------------------------------------
*/

it('分批支付：两笔凑满应支付金额后订单翻转为已支付', function () {
    Event::fake();
    bindFakeWallet();

    $member = createPayTestMember();
    FakeWalletOperator::setBalance($member->getSnId(), 10000);

    $order = createPayTestOrder(10000);        // 应付 100 元

    // 第一笔 50 元（定金）
    $first = app('sn-pay')->payer($member)->payable($order)->channel('money', 'balance')->pay(5000);

    expect($first->isPaid())->toBeTrue()
        ->and($order->fresh()->pay_status)->toBe(OrderPayStatus::Unpaid)
        ->and($order->fresh()->getRemainPayFee())->toBe(5000);

    // 第二笔 50 元凑满
    $second = app('sn-pay')->payer($member)->payable($order)->channel('money', 'balance')->pay();

    expect($second->isPaid())->toBeTrue()
        ->and($order->fresh()->pay_status)->toBe(OrderPayStatus::Paid)
        ->and($order->fresh()->getRemainPayFee())->toBe(0)
        ->and($order->payRecords()->paid()->count())->toBe(2);
});

it('支付金额超过剩余应付被拒绝', function () {
    bindFakeWallet();

    $member = createPayTestMember();
    FakeWalletOperator::setBalance($member->getSnId(), 999999);

    $order = createPayTestOrder(10000);

    app('sn-pay')->payer($member)->payable($order)->channel('money', 'balance')->pay(20000);
})->throws(PayException::class, 'Pay amount');

/*
|--------------------------------------------------------------------------
| 回调处理（幂等 + 防篡改）
|--------------------------------------------------------------------------
*/

it('第三方回调标记支付成功并流转订单，重复回调幂等', function () {
    Event::fake([PaySucceeded::class]);
    app('sn-pay')->addChannel('fake', FakeThirdAdapter::class);

    $order = createPayTestOrder(10000);
    $result = app('sn-pay')->payable($order)->channel('fake', 'scan')->pay();

    $operator = app('sn-pay')->channel('fake', 'scan');

    $payload = new NotifyPayload(
        paySn: $result->payRecord->pay_sn,
        transactionId: 'TX-FAKE-1',
        amount: 10000,
        currency: 'CNY',
        success: true,
    );

    $response = $operator->handleNotify($payload);
    expect($response->getContent())->toBe('fake-ok')
        ->and($result->payRecord->fresh()->status)->toBe(PayStatus::Paid)
        ->and($result->payRecord->fresh()->transaction_id)->toBe('TX-FAKE-1')
        ->and($order->fresh()->pay_status)->toBe(OrderPayStatus::Paid);

    Event::assertDispatchedTimes(PaySucceeded::class, 1);

    // 同一回调重放：应答成功但不重复处理
    $again = $operator->handleNotify($payload);
    expect($again->getContent())->toBe('fake-ok');
    Event::assertDispatchedTimes(PaySucceeded::class, 1);
});

it('回调金额不匹配被拒绝（防篡改）', function () {
    app('sn-pay')->addChannel('fake', FakeThirdAdapter::class);

    $order = createPayTestOrder(10000);
    $result = app('sn-pay')->payable($order)->channel('fake', 'scan')->pay();

    $response = app('sn-pay')->channel('fake', 'scan')->handleNotify(new NotifyPayload(
        paySn: $result->payRecord->pay_sn,
        transactionId: 'TX-FAKE-2',
        amount: 1,
        currency: 'CNY',
        success: true,
    ));

    expect($response->getContent())->toBe('fake-fail')
        ->and($result->payRecord->fresh()->status)->toBe(PayStatus::Unpaid);
});

it('未知单号回调应答失败', function () {
    app('sn-pay')->addChannel('fake', FakeThirdAdapter::class);

    $response = app('sn-pay')->channel('fake', 'scan')->handleNotify(new NotifyPayload(
        paySn: 'P_NOT_EXIST',
        transactionId: null,
        amount: 100,
        currency: 'CNY',
        success: true,
    ));

    expect($response->getContent())->toBe('fake-fail');
});

/*
|--------------------------------------------------------------------------
| 退款（原路退回 + 快照回款）
|--------------------------------------------------------------------------
*/

it('余额支付全额退款：回款钱包并标记支付单已退款', function () {
    Event::fake([RefundSucceeded::class]);
    bindFakeWallet();

    $member = createPayTestMember();
    FakeWalletOperator::setBalance($member->getSnId(), 10000);

    $order = createPayTestOrder(10000);
    $payResult = app('sn-pay')->payer($member)->payable($order)->channel('money', 'balance')->pay();

    expect(FakeWalletOperator::balance($member->getSnId()))->toBe(0);

    $refundResult = app('sn-pay')
        ->payer($member)
        ->channel('money', 'balance')
        ->refund($payResult->payRecord);

    expect($refundResult->isCompleted())->toBeTrue()
        ->and($refundResult->refund->status)->toBe(RefundStatus::Completed)
        ->and(sn_money()->minor($payResult->payRecord->fresh()->refunded_fee))->toBe(10000)
        ->and($payResult->payRecord->fresh()->status)->toBe(PayStatus::Refunded)
        ->and(FakeWalletOperator::balance($member->getSnId()))->toBe(10000);      // 快照 1:1 回款

    Event::assertDispatched(RefundSucceeded::class);
});

it('部分退款累计，超额退款被拒绝', function () {
    bindFakeWallet();

    $member = createPayTestMember();
    FakeWalletOperator::setBalance($member->getSnId(), 10000);

    $order = createPayTestOrder(10000);
    $payResult = app('sn-pay')->payer($member)->payable($order)->channel('money', 'balance')->pay();

    // 部分退 30 元
    $refund = app('sn-pay')->payer($member)->channel('money', 'balance')->refund($payResult->payRecord, 3000);
    expect($refund->isCompleted())->toBeTrue()
        ->and(sn_money()->minor($payResult->payRecord->fresh()->refunded_fee))->toBe(3000)
        ->and($payResult->payRecord->fresh()->status)->toBe(PayStatus::Paid);     // 未退完仍是已支付

    // 超过剩余可退（10000 - 3000 = 7000）被拒绝
    app('sn-pay')->payer($member)->channel('money', 'balance')->refund($payResult->payRecord, 8000);
})->throws(PayException::class, 'Refund fee');
