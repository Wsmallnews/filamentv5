<?php

it('swiper 组件渲染字符串幻灯片和默认缩略图', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" />', [
        'slides' => ['a.jpg', 'b.jpg'],
    ]);

    expect($html)
        ->toContain('x-ref="main"')
        ->toContain('x-ref="thumbs"')
        ->toContain('src="a.jpg"')
        ->toContain('src="b.jpg"')
        ->toContain('flex overflow-hidden')
        ->toContain('flex-col')
        ->toContain('height: 80px')
        ->toContain('swiper-button-next')
        ->toContain("thumbDirection: 'horizontal'");
});

it('swiper 组件渲染数组幻灯片，支持 url 跳转与默认说明条', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" :has-thumb="false" />', [
        'slides' => [
            ['image' => 'a.jpg', 'url' => 'https://example.com/a', 'label' => '第一张'],
        ],
    ]);

    expect($html)
        ->toContain("jumpToUrl('https://example.com/a'")
        ->toContain('第一张')
        ->toContain('swiper-slide-label')
        ->toContain('bg-black/50')
        ->toContain('object-contain')
        ->not->toContain('x-ref:thumbs');
});

it('swiper 组件单张幻灯片时自动隐藏缩略图', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" />', [
        'slides' => ['a.jpg'],
    ]);

    expect($html)
        ->toContain('x-ref="main"')
        ->not->toContain('x-ref="thumbs"');
});

it('swiper 组件支持自定义说明条样式', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" :has-thumb="false" label-class="top-0 bg-red-500" />', [
        'slides' => [['image' => 'a.jpg', 'label' => '标题']],
    ]);

    expect($html)
        ->toContain('top-0 bg-red-500')
        ->not->toContain('bg-black/50');

    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" :has-thumb="false" label-class="top-0" />', [
        'slides' => [['image' => 'a.jpg', 'label' => '标题', 'label_class' => 'bottom-2 bg-blue-500']],
    ]);

    expect($html)
        ->toContain('bottom-2 bg-blue-500')
        ->not->toContain('top-0');
});

it('swiper 组件支持 html 幻灯片自定义内容', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" :has-thumb="false" />', [
        'slides' => [['html' => '<p class="custom-slide">视频幻灯片</p>']],
    ]);

    expect($html)
        ->toContain('<p class="custom-slide">视频幻灯片</p>')
        ->not->toContain('<img');
});

it('swiper 组件支持缩略图位置与尺寸', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" thumb-position="left" :thumb-size="72" />', [
        'slides' => ['a.jpg', 'b.jpg', 'c.jpg'],
    ]);

    expect($html)
        ->toContain('flex-row-reverse')
        ->toContain('is-vertical')
        ->toContain('width: 72px')
        ->toContain("thumbDirection: 'vertical'");

    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" thumb-position="top" />', [
        'slides' => ['a.jpg', 'b.jpg', 'c.jpg'],
    ]);

    expect($html)
        ->toContain('flex-col-reverse')
        ->toContain('is-horizontal')
        ->toContain('height: 80px');
});

it('swiper 组件支持宽高比控制', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" :has-thumb="false" ratio="16/9" />', [
        'slides' => ['a.jpg'],
    ]);

    expect($html)->toContain('aspect-ratio: 16/9');
});

it('swiper 组件支持图片填充方式', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" :has-thumb="false" image-fit="cover" />', [
        'slides' => ['a.jpg'],
    ]);

    expect($html)
        ->toContain('object-cover')
        ->not->toContain('object-contain');
});

it('swiper 组件透传轮播配置', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-sn-support::swiper
            :slides="$slides"
            :has-thumb="false"
            effect="fade"
            :loop="false"
            :navigation="false"
            pagination="fraction"
            :autoplay="true"
            :autoplay-delay="5000"
            :options="$options"
        />
        BLADE, [
        'slides' => ['a.jpg', 'b.jpg'],
        'options' => ['speed' => 600],
    ]);

    expect($html)
        ->toContain("effect: 'fade'")
        ->toContain('loop: false')
        ->toContain("pagination: 'fraction'")
        ->toContain('autoplay: true')
        ->toContain('autoplayDelay: 5000')
        ->toContain('JSON.parse')
        ->toContain('speed')
        ->toContain('600')
        ->toContain('swiper-pagination')
        ->not->toContain('swiper-button-next');
});
