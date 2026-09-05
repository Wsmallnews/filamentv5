<?php

use Wsmallnews\Support\Support\Theme;

it('renders nothing when theme config is empty', function () {
    config()->set('sn-support.theme', []);

    expect(Theme::styles())->toBe('');
});

it('renders base tokens in a :root block', function () {
    config()->set('sn-support.theme', [
        'radius_card' => '0.75rem',
        'space_card' => '20px',
    ]);

    $styles = Theme::styles();

    expect($styles)->toContain(':root{--sn-radius-card:0.75rem;--sn-space-card:20px;}')
        ->and($styles)->not->toContain('@media');
});

it('renders _lg tokens in a separate media block to preserve responsiveness', function () {
    config()->set('sn-support.theme', [
        'radius_card' => '0.75rem',
        'radius_card_lg' => '1rem',
        'space_page_lg' => '2rem',
    ]);

    $styles = Theme::styles();

    expect($styles)->toContain(':root{--sn-radius-card:0.75rem;}')
        ->and($styles)->toContain('@media (min-width: 64rem){:root{--sn-radius-card:1rem;--sn-space-page:2rem;}}');
});

it('ignores unknown keys and invalid values', function () {
    config()->set('sn-support.theme', [
        'radius_card' => 'javascript:alert(1)',
        'unknown_token' => '1rem',
        'shadow_card' => '0 1px 2px black',
        'space_row' => '1.25rem',
    ]);

    $styles = Theme::styles();

    expect($styles)->toBe('<style>:root{--sn-space-row:1.25rem;}</style>');
});

it('accepts zero and percentage values', function () {
    config()->set('sn-support.theme', [
        'radius_card' => '0',
        'radius_control' => '50%',
    ]);

    expect(Theme::styles())->toContain('--sn-radius-card:0;')
        ->and(Theme::styles())->toContain('--sn-radius-control:50%;');
});
