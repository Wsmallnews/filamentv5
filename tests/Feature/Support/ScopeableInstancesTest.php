<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Support\Data\ScopeableContext;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Support\Utils as SupportUtils;

uses(RefreshDatabase::class);

function declareScopeables(array $instances): void
{
    config(['sn-test.scopeables' => $instances]);
}

it('解析 main 默认实例与命名实例（scope_id 强制整型）', function () {
    declareScopeables([
        'main' => ['scope_type' => 'sn-test', 'scope_id' => 0],
        'footer' => ['scope_type' => 'sn-test-footer', 'scope_id' => '5'],
    ]);

    expect(SupportUtils::getScopeFromInstances('sn-test.scopeables'))
        ->toEqual(new ScopeableContext('sn-test', 0))
        ->and(SupportUtils::getScopeFromInstances('sn-test.scopeables', 'footer'))
        ->toEqual(new ScopeableContext('sn-test-footer', 5));
});

it('实例配置缺失时抛异常', function () {
    SupportUtils::getScopeFromInstances('sn-test.scopeables');
})->throws(InvalidScopeException::class);

it('缺失 main 实例时抛异常', function () {
    declareScopeables([
        'other' => ['scope_type' => 'sn-test', 'scope_id' => 0],
    ]);

    SupportUtils::getScopeFromInstances('sn-test.scopeables');
})->throws(InvalidScopeException::class);

it('引用未声明的实例键时抛异常', function () {
    declareScopeables([
        'main' => ['scope_type' => 'sn-test', 'scope_id' => 0],
    ]);

    SupportUtils::getScopeFromInstances('sn-test.scopeables', 'missing');
})->throws(InvalidScopeException::class);

it('多个实例指向同一分区时抛异常', function () {
    declareScopeables([
        'main' => ['scope_type' => 'sn-test', 'scope_id' => 0],
        'dup' => ['scope_type' => 'sn-test', 'scope_id' => 0],
    ]);

    SupportUtils::getScopeFromInstances('sn-test.scopeables');
})->throws(InvalidScopeException::class);

it('实例缺少 scope_type 或 scope_id 时抛异常', function () {
    declareScopeables([
        'main' => ['scope_type' => 'sn-test', 'scope_id' => 0],
        'broken' => ['scope_type' => '', 'scope_id' => 0],
    ]);

    SupportUtils::getScopeFromInstances('sn-test.scopeables', 'main');
})->throws(InvalidScopeException::class);
