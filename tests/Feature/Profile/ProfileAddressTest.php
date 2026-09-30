<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Member\Models\Member;
use Wsmallnews\Profile\Livewire\Components\Address\Addresses;
use Wsmallnews\Profile\Livewire\Components\Address\ChooseAddress;
use Wsmallnews\Profile\Services\RegionService;
use Wsmallnews\Profile\Testing\TestsProfile;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, TestsProfile::class);

/*
|--------------------------------------------------------------------------
| 测试数据
|--------------------------------------------------------------------------
*/

/**
 * 深圳南头街道四级链的地址数据
 *
 * @return array<string, mixed>
 */
function addressData(array $overrides = []): array
{
    return array_merge([
        'consignee' => '张三',
        'phone' => '13800138000',
        'country_code' => 'CN',
        'administrative_area' => '44',
        'administrative_area_name' => '广东省',
        'locality' => '4403',
        'locality_name' => '深圳市',
        'dependent_locality' => '440305',
        'dependent_locality_name' => '南山区',
        'township' => '440305001',
        'township_name' => '南头街道',
        'address_line1' => '科苑南路 3331 号',
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| RegionService 双 Provider
|--------------------------------------------------------------------------
*/

it('区划服务：中国四级链路到叶子节点', function () {
    $this->seedRegions();

    $service = app(RegionService::class);

    $provinces = $service->divisions('CN');
    expect(count($provinces))->toBe(2)
        ->and(collect($provinces)->firstWhere('code', '44')['name'])->toBe('广东省');

    $cities = $service->divisions('CN', ['44']);
    expect(collect($cities)->firstWhere('code', '4403')['name'])->toBe('深圳市');

    $districts = $service->divisions('CN', ['44', '4403']);
    expect(collect($districts)->firstWhere('code', '440305')['has_children'])->toBeTrue();

    $townships = $service->divisions('CN', ['44', '4403', '440305']);
    expect(collect($townships)->firstWhere('code', '440305001')['has_children'])->toBeFalse();
});

it('区划服务：china_division_level=3 时止于三级', function () {
    $this->seedRegions();

    config(['sn-profile.china_division_level' => 3]);

    $districts = app(RegionService::class)->divisions('CN', ['44', '4403']);

    expect(collect($districts)->firstWhere('code', '440305')['has_children'])->toBeFalse();
});

it('区划服务：supported_countries 过滤国家列表', function () {
    config(['sn-profile.supported_countries' => ['CN', 'US']]);

    $countries = app(RegionService::class)->countries();

    expect(array_keys($countries))->toBe(['CN', 'US'])
        ->and($countries['CN'])->toBe('中国');
});

it('区划服务：直筒子市有补齐行可继续下钻', function () {
    $this->seedRegions();

    $padded = app(RegionService::class)->divisions('CN', ['44', '4419']);

    expect($padded)->not->toBeEmpty()
        ->and($padded[0]['code'])->toBe('441900')
        ->and($padded[0]['has_children'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| 地址模型与 owner 隔离
|--------------------------------------------------------------------------
*/

it('地址模型：区划链与完整地址展示', function () {
    $user = User::factory()->create();

    $address = $user->addresses()->create(addressData());

    expect($address->region_label)->toBe('广东省　深圳市　南山区　南头街道')
        ->and($address->full_address)->toBe('广东省　深圳市　南山区　南头街道 科苑南路 3331 号');
});

it('地址模型：owner 隔离，跨用户不可见', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->addresses()->create(addressData());

    expect($userA->addresses()->count())->toBe(1)
        ->and($userB->addresses()->count())->toBe(0);
});

it('地址模型：默认地址在 owner 范围内唯一', function () {
    $user = User::factory()->create();
    $member = Member::create(['user_id' => $user->id, 'status' => 'normal']);

    $first = $member->addresses()->create(addressData(['consignee' => '一号地址']));
    $second = $member->addresses()->create(addressData(['consignee' => '二号地址']));

    $first->setDefault();
    expect($first->fresh()->is_default)->toBeTrue()
        ->and($second->fresh()->is_default)->toBeFalse();

    $second->setDefault();
    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Livewire 组件
|--------------------------------------------------------------------------
*/

it('地址组件：新增地址（含四级区划拍平落列）', function () {
    $this->seedRegions();

    $user = User::factory()->create();

    livewire(Addresses::class, ['owner' => $user])
        ->callAction('create', [
            'consignee' => '李四',
            'phone' => '13900139000',
            'region' => [
                'country' => 'CN',
                'chain' => [
                    ['code' => '44', 'name' => '广东省'],
                    ['code' => '4403', 'name' => '深圳市'],
                    ['code' => '440305', 'name' => '南山区'],
                    ['code' => '440305001', 'name' => '南头街道'],
                ],
            ],
            'address_line1' => '测试路 8 号',
            'is_default' => true,
        ]);

    $address = $user->addresses()->first();

    expect($address)->not->toBeNull()
        ->and($address->consignee)->toBe('李四')
        ->and($address->administrative_area)->toBe('44')
        ->and($address->locality_name)->toBe('深圳市')
        ->and($address->township_name)->toBe('南头街道')
        ->and($address->is_default)->toBeTrue();       // 首个地址自动默认
});

it('地址组件：选择组件默认选中默认地址并派发事件', function () {
    $user = User::factory()->create();

    $normal = $user->addresses()->create(addressData(['consignee' => '普通地址']));
    $default = $user->addresses()->create(addressData(['consignee' => '默认地址']));
    $default->setDefault();

    $component = livewire(ChooseAddress::class, ['owner' => $user]);

    expect($component->get('selectedId'))->toBe($default->id);

    $component->call('choose', $normal->id)
        ->assertDispatched('sn-profile-address:selected', addressId: $normal->id);
});
