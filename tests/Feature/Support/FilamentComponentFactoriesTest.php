<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Filters\SelectFilter;
use Wsmallnews\Cms\Enums\LinkStatus;
use Wsmallnews\Support\Filament\Filters\FilterComponents;
use Wsmallnews\Support\Filament\Forms\FormComponents;

it('statusToggleButtons 返回内联分组组件且默认第一个 case', function () {
    $component = FormComponents::statusToggleButtons(LinkStatus::class);

    expect($component)->toBeInstanceOf(ToggleButtons::class)
        ->and($component->getName())->toBe('status')
        ->and($component->getLabel())->toBe(__('sn-support::support.form_components.status.label'))
        ->and($component->isInline())->toBeTrue()
        ->and($component->isGrouped())->toBeTrue()
        ->and(array_keys($component->getOptions()))->toBe(['normal', 'hidden'])
        ->and($component->getDefaultState())->toBe(LinkStatus::Normal);
});

it('statusToggleButtons 支持自定义字段名与标签', function () {
    $component = FormComponents::statusToggleButtons(LinkStatus::class, 'state', '链接状态');

    expect($component->getName())->toBe('state')
        ->and($component->getLabel())->toBe('链接状态');
});

it('orderColumnInput 返回 integer + min:0 输入框', function () {
    $component = FormComponents::orderColumnInput();

    expect($component)->toBeInstanceOf(TextInput::class)
        ->and($component->getName())->toBe('order_column')
        ->and($component->getLabel())->toBe(__('sn-support::support.form_components.order_column.label'))
        ->and($component->getMinValue())->toBe(0)
        ->and(FormComponents::orderColumnInput('sort')->getName())->toBe('sort');
});

it('orderColumnInput helperText 翻译已注册', function () {
    app()->setLocale('zh_cn');
    expect(__('sn-support::support.form_components.order_column.helper'))->toBe('留空自动分配到末尾');

    app()->setLocale('en');
    expect(__('sn-support::support.form_components.order_column.helper'))->toBe('Leave blank to assign to the end automatically');

    app()->setLocale('zh_cn');
});

it('statusFilter 返回枚举下拉筛选且标签取翻译', function () {
    $filter = FilterComponents::statusFilter(LinkStatus::class);

    expect($filter)->toBeInstanceOf(SelectFilter::class)
        ->and($filter->getName())->toBe('status')
        ->and($filter->getLabel())->toBe(__('sn-support::support.status_filter.label'))
        ->and(array_keys($filter->getOptions()))->toBe(['normal', 'hidden']);
});

it('statusFilter 支持自定义字段名与标签', function () {
    $filter = FilterComponents::statusFilter(LinkStatus::class, 'post_status', '文章状态');

    expect($filter->getName())->toBe('post_status')
        ->and($filter->getLabel())->toBe('文章状态');
});
