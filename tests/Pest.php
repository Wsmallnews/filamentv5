<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Global Helpers
|--------------------------------------------------------------------------
*/

/**
 * livewire()/静态调用不经生产环境的 IdentifyResourceConfiguration /
 * IdentifyPageConfiguration 中间件（页面与资源的 configuration key 是两套），
 * 直连测试资源/页面时需显式进入 admin 面板 + 默认配置上下文
 */
function withAdminPanelContext(callable $callback): mixed
{
    $previousPanel = Filament::getCurrentPanel();
    $previousPageKey = Filament::getCurrentPageConfigurationKey();
    $previousResourceKey = Filament::getCurrentResourceConfigurationKey();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setCurrentPageConfigurationKey('default');
    Filament::setCurrentResourceConfigurationKey('default');

    try {
        return $callback();
    } finally {
        Filament::setCurrentPanel($previousPanel);
        Filament::setCurrentPageConfigurationKey($previousPageKey);
        Filament::setCurrentResourceConfigurationKey($previousResourceKey);
    }
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
