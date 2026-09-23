<?php

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Order\Enums\Item\EvaluateStatus;
use Wsmallnews\Order\Enums\Order\Status;
use Wsmallnews\Order\Models\Order;
use Wsmallnews\Order\OrderPlugin;
use Wsmallnews\Order\Support\Utils;
use Wsmallnews\Pay\Contracts\PayableInterface;
use Wsmallnews\Pay\Models\PayRecord;

uses(RefreshDatabase::class);

it('解析 order 模块 scopeable main 默认实例', function () {
    expect(Utils::getScopeable())->toBe(['scope_type' => 'sn-order', 'scope_id' => 0]);
});

it('order 模型实现 pay 包支付契约并注册 morph 别名', function () {
    expect(Order::class)->toImplement(PayableInterface::class)
        ->and((new Order)->getMorphClass())->toBe('sn_order');
});

it('支付记录关联经 sn-order.models.pay_record 配置解析（pay 包提供模型）', function () {
    expect(Utils::getPayRecordModel())->toBe(PayRecord::class)
        ->and((new Order)->payRecords())->toBeInstanceOf(MorphMany::class);
});

it('订单状态枚举经翻译键输出标签', function () {
    expect(Status::Unpaid->getLabel())->toBe(__('sn-order::order.order_status.unpaid'))
        ->and(EvaluateStatus::Evaluated->getLabel())->toBe(__('sn-order::order.item_evaluate_status.evaluated'));
});

it('order 插件注册到面板（panel_register 配置驱动，当前无后台页面）', function () {
    expect(Filament::getPanel('admin')->hasPlugin((new OrderPlugin)->getId()))->toBeTrue();
});

it('订单表结构可写入（scopeable + 金额 + 状态枚举转换）', function () {
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
        'pay_fee' => 100,
    ]);

    expect($order->status)->toBe(Status::Unpaid)
        ->and($order->pay_fee->getAmount())->toBe('10000')
        ->and($order->isPaid())->toBeFalse();
});
