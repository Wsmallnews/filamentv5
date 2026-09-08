<?php

use Illuminate\Support\Str;

it('generate_slug 普通标题转写为小写 slug', function () {
    expect(generate_slug('Hello World'))->toBe('hello-world');
});

it('generate_slug 超长标题按词边界截断到 80 字符内', function () {
    $slug = generate_slug(str_repeat('lorem-ipsum-dolor-', 12));

    expect(Str::length($slug))->toBeLessThanOrEqual(80)
        ->and($slug)->not->toEndWith('-')
        // 截断发生在连字符词边界，最后一个单词是完整的（80 字符窗口止于第 5 组的 lorem-）
        ->and(Str::afterLast($slug, '-'))->toBe('lorem');
});

it('generate_slug 空转写结果按默认前缀兜底', function () {
    $slug = generate_slug('!!!###');

    expect($slug)->toStartWith('slug-')
        ->and(Str::length($slug))->toBe(13);
});

it('generate_slug 支持自定义兜底前缀', function () {
    expect(generate_slug('!!!###', fallbackPrefix: 'post'))->toStartWith('post-')
        ->and(Str::length(generate_slug('!!!###', fallbackPrefix: 'post')))->toBe(13);
});

it('generate_slug 支持自定义长度上限', function () {
    expect(generate_slug('hello-world-foo', limit: 12))->toBe('hello-world');
});

it('generate_slug 空值走兜底', function () {
    expect(generate_slug(null))->toStartWith('slug-');
});
