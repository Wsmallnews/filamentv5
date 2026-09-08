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

it('swiper 组件渲染数组幻灯片，支持跳转链接', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" :has-thumb="false" />', [
        'slides' => [
            ['image' => 'a.jpg', 'href' => 'https://example.com/a'],
        ],
    ]);

    expect($html)
        ->toContain("jumpToUrl('https://example.com/a'")
        ->toContain('cursor-pointer');
});

it('swiper 组件单张幻灯片时自动隐藏缩略图', function () {
    $html = (string) $this->blade('<x-sn-support::swiper :slides="$slides" />', [
        'slides' => ['a.jpg'],
    ]);

    expect($html)
        ->toContain('x-ref="main"')
        ->not->toContain('x-ref="thumbs"');
});

it('swiper 组件组合模式下覆盖内容由调用处渲染', function () {
    // 旧版内建的 label 说明条已在组件重构中移除，覆盖内容统一走组合模式 slot（定位与样式由调用处控制）
    $html = (string) $this->blade(<<<'BLADE'
        <x-sn-support::swiper :has-thumb="false">
            <x-sn-support::swiper.slide image="a.jpg">
                <div class="absolute bottom-2 bg-blue-500">标题</div>
            </x-sn-support::swiper.slide>
        </x-sn-support::swiper>
        BLADE);

    expect($html)
        ->toContain('bottom-2 bg-blue-500')
        ->toContain('标题')
        ->toContain('data-slide-image="a.jpg"');
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
