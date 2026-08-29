<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Support\Enums\ContentType;

uses(RefreshDatabase::class);

it('content 组件渲染纯图类型为无缝竖排长图', function () {
    $html = (string) $this->blade('<x-sn-support::content :content-type="$contentType" :content="$content" />', [
        'contentType' => ContentType::Images,
        'content' => json_encode(['sn/product/contents/a.jpg', 'sn/product/contents/b.jpg']),
    ]);

    expect($html)->toContain('sn-content-images')
        ->and($html)->toContain('block w-full')
        ->and($html)->toContain('src="'.files_url('sn/product/contents/a.jpg').'"')
        ->and($html)->toContain('src="'.files_url('sn/product/contents/b.jpg').'"')
        ->and(substr_count($html, '<img'))->toBe(2);
});

it('content 组件接受数组形式的纯图内容', function () {
    $html = (string) $this->blade('<x-sn-support::content :content-type="$contentType" :content="$content" />', [
        'contentType' => ContentType::Images,
        'content' => ['sn/product/contents/a.jpg'],
    ]);

    expect($html)->toContain('sn-content-images')
        ->and($html)->toContain('src="'.files_url('sn/product/contents/a.jpg').'"');
});

it('content 组件对空的纯图内容不渲染图片', function () {
    foreach ([null, [], '[]', 'not-a-json'] as $content) {
        $html = (string) $this->blade('<x-sn-support::content :content-type="$contentType" :content="$content" />', [
            'contentType' => ContentType::Images,
            'content' => $content,
        ]);

        expect($html)->not->toContain('<img')
            ->and($html)->toContain('sn-content-container');
    }
});

it('content 组件保持其他类型的渲染分支', function () {
    $richtext = (string) $this->blade('<x-sn-support::content :content-type="$contentType" :content="$content" />', [
        'contentType' => ContentType::Richtext,
        'content' => '<p>富文本</p>',
    ]);

    $textarea = (string) $this->blade('<x-sn-support::content :content-type="$contentType" :content="$content" />', [
        'contentType' => ContentType::Textarea,
        'content' => '纯文本',
    ]);

    expect($richtext)->toContain('fi-prose')
        ->and($richtext)->toContain('<p>富文本</p>')
        ->and($textarea)->toContain('sn-content-text')
        ->and($textarea)->toContain('纯文本');
});

it('表格内容弹窗视图渲染纯图类型', function () {
    $html = view('sn-support::filament.tables.columns.content-modal', [
        'contentType' => ContentType::Images,
        'content' => json_encode(['sn/product/contents/a.jpg']),
    ])->render();

    expect($html)->toContain('sn-content-images')
        ->and($html)->toContain('src="'.files_url('sn/product/contents/a.jpg').'"');
});
