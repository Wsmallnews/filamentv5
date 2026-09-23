<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Member\Models\Member;
use Wsmallnews\Pay\Enums\PayStatus;
use Wsmallnews\Pay\Enums\RefundStatus;
use Wsmallnews\Wallet\Enums\RechargeStatus;
use Wsmallnews\Wallet\Enums\TransactionType;
use Wsmallnews\Wallet\Exceptions\WalletException;
use Wsmallnews\Wallet\Facades\Wallet;
use Wsmallnews\Wallet\Models\Wallet as WalletModel;
use Wsmallnews\Wallet\Models\WalletType;
use Wsmallnews\Wallet\Services\ConversionService;
use Wsmallnews\Wallet\Services\RateService;
use Wsmallnews\Wallet\Support\Utils;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 工具
|--------------------------------------------------------------------------
*/

function registerWalletTestTypes(): void
{
    // balance：1:1 锚定 CNY，2 位小数（分）
    // point：100 积分 = 1 CNY，0 位小数
    Wallet::registers([
        'balance' => [
            'name' => '余额', 'currency_code' => 'CNY', 'decimals' => 2,
            'anchor_rate' => 1, 'anchor_currency' => 'CNY',
        ],
        'point' => [
            'name' => '积分', 'currency_code' => 'POINT', 'decimals' => 0,
            'anchor_rate' => 100, 'anchor_currency' => 'CNY',
        ],
    ]);
}

function createWalletTestMember(): Member
{
    return Member::create(['user_id' => rand(1, 999999), 'status' => 'normal']);
}

/*
|--------------------------------------------------------------------------
| 类型注册与费率解析
|--------------------------------------------------------------------------
*/

it('代码声明的钱包类型首次解析时懒落库', function () {
    expect(WalletType::count())->toBe(0);

    Wallet::registers(['point' => ['name' => '积分', 'currency_code' => 'POINT', 'decimals' => 0, 'anchor_rate' => 100, 'anchor_currency' => 'CNY']]);

    // 未解析前不访问数据库
    expect(WalletType::count())->toBe(0);

    $type = Wallet::type('point');

    expect($type)->toBeInstanceOf(WalletType::class)
        ->and($type->name)->toBe('积分')
        ->and($type->decimals)->toBe(0)
        ->and(WalletType::count())->toBe(1)
        ->and($type->resolveRate())->not->toBeNull()
        ->and((string) $type->resolveRate()->anchor_rate)->toBe('100');
});

it('未注册类型抛异常', function () {
    Wallet::type('energy');
})->throws(WalletException::class, 'is not registered');

it('租户覆盖锚定率优先于全局默认', function () {
    registerWalletTestTypes();
    $type = Wallet::type('point');

    Utils::getWalletRateModel()::create([
        'wallet_type_id' => $type->id,
        'team_id' => 5,
        'anchor_rate' => '80',
        'anchor_currency' => 'CNY',
        'enabled' => true,
    ]);

    expect((string) $type->resolveRate()->anchor_rate)->toBe('100')
        ->and((string) $type->resolveRate(5)->anchor_rate)->toBe('80')
        ->and((string) $type->resolveRate(6)->anchor_rate)->toBe('100');
});

/*
|--------------------------------------------------------------------------
| 钱包基础与不可变账本
|--------------------------------------------------------------------------
*/

it('钱包懒创建：读不建行、首笔变动建行、快照链完整', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    expect(Wallet::balance($member, 'point'))->toBe(0)
        ->and(WalletModel::count())->toBe(0);

    Wallet::credit($member, 'point', 1000, ['description' => '初始发放']);
    Wallet::debit($member, 'point', 300);

    $wallet = WalletModel::first();
    $transactions = $wallet->transactions;

    expect($wallet->balance)->toBe(700)
        ->and($transactions)->toHaveCount(2)
        ->and($transactions[0]->balance_after)->toBe(1000)
        ->and($transactions[1]->balance_after)->toBe(700)
        ->and($transactions[0]->type)->toBe(TransactionType::Recharge)
        ->and($transactions[1]->type)->toBe(TransactionType::Consume)
        ->and($wallet->team_id)->toBeNull();     // 无租户 = 全局（NULL，非 0）
});

it('流水不可变：禁止修改与删除', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    $tx = Wallet::credit($member, 'point', 100);

    $tx->update(['amount' => 999]);
})->throws(WalletException::class, 'immutable');

it('余额不足时扣减被拒且不留任何痕迹', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    Wallet::credit($member, 'point', 100);

    Wallet::debit($member, 'point', 101);
})->throws(WalletException::class, 'Insufficient available balance.');

it('余额不足时扣减被拒且余额不变', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    Wallet::credit($member, 'point', 100);

    try {
        Wallet::debit($member, 'point', 101);
    } catch (WalletException) {
        // 异常后余额与流水均不受影响
    }

    expect(Wallet::balance($member, 'point'))->toBe(100)
        ->and(Utils::getWalletTransactionModel()::count())->toBe(1);
});

it('非法金额被拒', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    Wallet::credit($member, 'point', 0);
})->throws(WalletException::class, 'must be positive');

/*
|--------------------------------------------------------------------------
| 幂等（uuid 重放）
|--------------------------------------------------------------------------
*/

it('同 uuid 重放只记一笔账', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    $first = Wallet::credit($member, 'point', 500, ['uuid' => 'pay:SN001']);
    $replay = Wallet::debit($member, 'point', 300, ['uuid' => 'pay:SN001']);

    expect($replay->id)->toBe($first->id)
        ->and(Wallet::balance($member, 'point'))->toBe(500)
        ->and(Utils::getWalletTransactionModel()::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| 冻结体系（佣金场景）
|--------------------------------------------------------------------------
*/

it('佣金场景：冻结入账不可提现，解冻后可提现', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    // 发佣金：直接入冻结
    Wallet::credit($member, 'point', 1000, ['frozen' => true]);

    expect(Wallet::balance($member, 'point'))->toBe(0)
        ->and(Wallet::frozen($member, 'point'))->toBe(1000);

    // 用户提现（扣可用）失败：可用为 0，不允许负数
    Wallet::debit($member, 'point', 100);
})->throws(WalletException::class, 'Insufficient available balance.');

it('佣金场景续：售后窗口内退款走冻结扣减，确认后解冻可提现', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    Wallet::credit($member, 'point', 1000, ['frozen' => true]);

    // 售后窗口内订单退款：佣金从冻结中追回
    Wallet::debitFrozen($member, 'point', 300);

    expect(Wallet::frozen($member, 'point'))->toBe(700)
        ->and(Wallet::balance($member, 'point'))->toBe(0);

    // 窗口期过后解冻，佣金可提现
    Wallet::unfreeze($member, 'point', 700);
    Wallet::debit($member, 'point', 700);

    expect(Wallet::balance($member, 'point'))->toBe(0)
        ->and(Wallet::frozen($member, 'point'))->toBe(0);
});

it('可用余额可主动冻结：freeze / unfreeze 互逆', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    Wallet::credit($member, 'point', 1000);

    $freezeTx = Wallet::freeze($member, 'point', 400);

    expect(Wallet::balance($member, 'point'))->toBe(600)
        ->and(Wallet::frozen($member, 'point'))->toBe(400)
        ->and($freezeTx->type)->toBe(TransactionType::Freeze);

    Wallet::unfreeze($member, 'point', 400);

    expect(Wallet::balance($member, 'point'))->toBe(1000)
        ->and(Wallet::frozen($member, 'point'))->toBe(0);
});

it('冻结不足时冻结扣减被拒', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    Wallet::credit($member, 'point', 100, ['frozen' => true]);

    Wallet::debitFrozen($member, 'point', 101);
})->throws(WalletException::class, 'Insufficient frozen balance.');

/*
|--------------------------------------------------------------------------
| 转账
|--------------------------------------------------------------------------
*/

it('钱包间转账：双流水对冲、共享 transfer_sn、余额同步', function () {
    registerWalletTestTypes();

    $from = createWalletTestMember();
    $to = createWalletTestMember();

    Wallet::credit($from, 'point', 1000);

    $result = Wallet::transfer($from, $to, 'point', 300, ['description' => '转赠']);

    expect(Wallet::balance($from, 'point'))->toBe(700)
        ->and(Wallet::balance($to, 'point'))->toBe(300)
        ->and($result['out']->transfer_sn)->toBe($result['in']->transfer_sn)
        ->and($result['out']->amount)->toBe(-300)
        ->and($result['in']->amount)->toBe(300)
        ->and(Utils::getWalletTransactionModel()::count())->toBe(3);       // 入账 1 + 转出 1 + 转入 1
});

it('转账余额不足被拒', function () {
    registerWalletTestTypes();

    $from = createWalletTestMember();
    $to = createWalletTestMember();

    Wallet::credit($from, 'point', 100);

    Wallet::transfer($from, $to, 'point', 101);
})->throws(WalletException::class, 'Insufficient available balance.');

/*
|--------------------------------------------------------------------------
| 换算引擎（两跳模型）
|--------------------------------------------------------------------------
*/

it('单跳换算：本位币订单按锚定率换算（100 积分 = 1 元）', function () {
    registerWalletTestTypes();
    $type = Wallet::type('point');
    $conversion = app(ConversionService::class);

    // 72 元 = 7200 分 → 7200 积分
    $result = $conversion->convert(7200, 'CNY', $type);

    expect($result['wallet_amount'])->toBe(7200)
        ->and($result['snapshot']['market_rate'])->toBeNull()
        ->and($result['snapshot']['anchor_rate'])->toBe('100');
});

it('两跳换算：美元订单经市场汇率折算（1 USD = 7.2 CNY）', function () {
    registerWalletTestTypes();
    $type = Wallet::type('point');
    $conversion = app(ConversionService::class);

    Utils::getMarketRateModel()::create(['source_currency' => 'USD', 'target_currency' => 'CNY', 'rate' => '7.2']);

    // $10 = 1000 分(USD) → 7200 分(CNY) → 7200 积分
    $result = $conversion->convert(1000, 'USD', $type);

    expect($result['wallet_amount'])->toBe(7200)
        ->and($result['snapshot']['market_rate'])->toBe('7.2');
});

it('市场汇率支持反查取倒数', function () {
    registerWalletTestTypes();

    // 只录 CNY:USD，查 USD:CNY 自动取倒数
    Utils::getMarketRateModel()::create(['source_currency' => 'CNY', 'target_currency' => 'USD', 'rate' => '0.1389']);

    $rate = app(RateService::class)->market('USD', 'CNY');

    expect(bccomp($rate, '7.190', 3))->toBe(1);          // 1/0.1389 ≈ 7.1994
});

it('市场汇率缺失时抛异常（不静默换算）', function () {
    registerWalletTestTypes();
    $type = Wallet::type('point');

    app(ConversionService::class)->convert(1000, 'USD', $type);
})->throws(WalletException::class, 'Market rate');

it('扣款口径 up 进位：除不尽时远离零进位保护平台', function () {
    registerWalletTestTypes();

    // 33.33 积分/元：1 元 → 33.33 → up → 34
    Wallet::registers(['energy' => ['name' => '能量', 'currency_code' => 'ENERGY', 'decimals' => 0, 'anchor_rate' => '33.33', 'anchor_currency' => 'CNY']]);
    $type = Wallet::type('energy');

    $up = app(ConversionService::class)->convert(100, 'CNY', $type, null, 'up');
    $half = app(ConversionService::class)->convert(100, 'CNY', $type, null, 'half_up');

    expect($up['wallet_amount'])->toBe(34)
        ->and($half['wallet_amount'])->toBe(33);
});

it('钱包金额逆向换算：充值单定价（1000 积分 = 10 元）', function () {
    registerWalletTestTypes();
    $type = Wallet::type('point');

    $payFee = app(ConversionService::class)->convertToCurrency(1000, $type, 'CNY');

    expect($payFee)->toBe(1000);     // 10 元 = 1000 分
});

it('退款按支付时快照等比例回退，不重新换算', function () {
    registerWalletTestTypes();
    $type = Wallet::type('point');
    $conversion = app(ConversionService::class);

    Utils::getMarketRateModel()::create(['source_currency' => 'USD', 'target_currency' => 'CNY', 'rate' => '7.2']);

    $deducted = $conversion->convert(10000, 'USD', $type, null, 'up');

    // 支付后汇率波动（改库），退款仍按快照回退
    Utils::getMarketRateModel()::query()->first()->update(['rate' => '9.9']);

    expect($conversion->reverseBySnapshot(5000, $deducted['snapshot']))->toBe((int) ($deducted['wallet_amount'] / 2))
        ->and($conversion->reverseBySnapshot(10000, $deducted['snapshot']))->toBe($deducted['wallet_amount']);
});

it('租户覆盖锚定率参与换算', function () {
    registerWalletTestTypes();
    $type = Wallet::type('point');
    $conversion = app(ConversionService::class);

    // 租户 5 改为 80 积分/元
    Utils::getWalletRateModel()::create(['wallet_type_id' => $type->id, 'team_id' => 5, 'anchor_rate' => '80', 'anchor_currency' => 'CNY', 'enabled' => true]);

    expect($conversion->convert(10000, 'CNY', $type, null)['wallet_amount'])->toBe(10000)
        ->and($conversion->convert(10000, 'CNY', $type, 5)['wallet_amount'])->toBe(8000);
});

/*
|--------------------------------------------------------------------------
| 充值单（在线充值闭环）
|--------------------------------------------------------------------------
*/

it('创建充值单按锚定率逆向定价', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    $recharge = Wallet::createRecharge($member, 'point', 1000);

    expect($recharge->pay_currency)->toBe('CNY')
        ->and(sn_money()->minor($recharge->pay_fee))->toBe(1000)      // 1000 积分 = 10 元
        ->and($recharge->status)->toBe(RechargeStatus::Unpaid)
        ->and($recharge->getMorphClass())->toBe('sn_wallet_recharge');
});

it('充值单支付成功后入账，重复回调幂等', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    $recharge = Wallet::createRecharge($member, 'point', 1000);

    config(['sn-pay.channels.money.enabled' => true]);

    // 用 balance 钱包支付充值单：balance 扣款 → point 入账
    Wallet::credit($member, 'balance', 2000);

    $result = app('sn-pay')
        ->payer($member)
        ->payable($recharge)
        ->channel('money', 'balance')
        ->pay(null, ['wallet_type' => 'balance']);

    expect($result->isPaid())->toBeTrue()
        ->and($result->payRecord->status)->toBe(PayStatus::Paid)
        ->and($recharge->fresh()->status)->toBe(RechargeStatus::Paid)
        ->and(Wallet::balance($member, 'point'))->toBe(1000)
        ->and(Wallet::balance($member, 'balance'))->toBe(1000);         // 2000 - 1000(分=10元)

    // 模拟回调重放：再次 checkAndPaid 不重复入账
    $recharge->fresh()->checkAndPaid();

    expect(Wallet::balance($member, 'point'))->toBe(1000)
        ->and(Utils::getWalletTransactionModel()::query()->where('type', TransactionType::Recharge)->where('subject_type', 'sn_wallet_recharge')->count())->toBe(1);
});

it('积分钱包直接支付充值单并按快照退款', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    // 充值 balance 50 元，用 point 支付（100 积分 = 1 元 → 5000 积分）
    $recharge = Wallet::createRecharge($member, 'balance', 5000);

    config(['sn-pay.channels.money.enabled' => true]);
    Wallet::credit($member, 'point', 10000);

    $result = app('sn-pay')
        ->payer($member)
        ->payable($recharge)
        ->channel('money', 'balance')
        ->pay(null, ['wallet_type' => 'point']);

    expect($result->payRecord->options['wallet'])->toHaveKeys(['wallet_amount', 'snapshot', 'rate'])
        ->and($result->payRecord->options['wallet']['wallet_amount'])->toBe(5000)
        ->and(Wallet::balance($member, 'point'))->toBe(5000)
        ->and(Wallet::balance($member, 'balance'))->toBe(5000)
        ->and($recharge->fresh()->status)->toBe(RechargeStatus::Paid);

    // 按快照退一半（¥25 → 2500 积分）
    $refund = app('sn-pay')
        ->payer($member)
        ->channel('money', 'balance')
        ->refund($result->payRecord, 2500);

    expect($refund->isCompleted())->toBeTrue()
        ->and($refund->refund->status)->toBe(RefundStatus::Completed)
        ->and(Wallet::balance($member, 'point'))->toBe(7500);
});

it('pay 通道钱包扣款流水带业务主体与幂等键', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    $recharge = Wallet::createRecharge($member, 'balance', 1000);
    Wallet::credit($member, 'point', 10000);

    config(['sn-pay.channels.money.enabled' => true]);

    $result = app('sn-pay')->payer($member)->payable($recharge)->channel('money', 'balance')->pay(null, ['wallet_type' => 'point']);

    $consume = Utils::getWalletTransactionModel()::query()->where('type', TransactionType::Consume)->first();

    expect($consume->uuid)->toBe('pay:' . $result->payRecord->pay_sn)
        ->and($consume->subject_type)->toBe('sn_wallet_recharge')
        ->and($consume->subject_id)->toBe($recharge->id)
        ->and($consume->options['pay_sn'])->toBe($result->payRecord->pay_sn);
});

/*
|--------------------------------------------------------------------------
| 对账
|--------------------------------------------------------------------------
*/

it('对账命令：正常通过，余额被篡改时报告并支持 --fix 修复', function () {
    registerWalletTestTypes();
    $member = createWalletTestMember();

    Wallet::credit($member, 'point', 1000);
    Wallet::debit($member, 'point', 300);

    $this->artisan('sn-wallet:reconcile')->assertSuccessful();

    // 篡改余额（绕过账本）
    $wallet = WalletModel::first();
    $wallet->newQuery()->whereKey($wallet->id)->update(['balance' => 999]);

    $this->artisan('sn-wallet:reconcile')->assertSuccessful();

    $report = app('sn-wallet')->reconcile();
    expect($report['mismatched'])->toHaveCount(1);

    $this->artisan('sn-wallet:reconcile', ['--fix' => true])->assertSuccessful();

    expect(Wallet::balance($member, 'point'))->toBe(700);
});
