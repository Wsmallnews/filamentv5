<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Wsmallnews\Media\Livewire\Components\Picker;
use Wsmallnews\Media\Models\Media;
use Wsmallnews\Media\Models\MediaDirectory;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makePickerMedia(array $attributes = []): Media
{
    return Media::create(array_merge([
        'disk' => 'public',
        'path' => 'sn/' . now()->format('Ymd') . '/' . Str::random(32) . '.' . ($attributes['ext'] ?? 'jpg'),
        'name' => '素材-' . Str::random(4) . '.' . ($attributes['ext'] ?? 'jpg'),
        'ext' => $attributes['ext'] ?? 'jpg',
        'mime' => $attributes['mime'] ?? 'image/jpeg',
        'size' => 2048,
        'width' => $attributes['width'] ?? 500,
        'height' => $attributes['height'] ?? 500,
    ], $attributes));
}

it('renders the picker with library tab', function () {
    makePickerMedia(['name' => '可见素材.jpg']);

    livewire(Picker::class, [
        'multiple' => false,
        'acceptedMimes' => [],
        'ratio' => 1,
        'statePath' => 'data.image',
        'initialValues' => [],
    ])->assertSee('可见素材.jpg');
});

it('dims media that does not match the accepted mimes', function () {
    makePickerMedia(['name' => '图片.jpg']);
    makePickerMedia(['name' => '证书.pdf', 'ext' => 'pdf', 'mime' => 'application/pdf', 'width' => null, 'height' => null]);

    // 约束转 SQL：列表直接过滤（分页计数随之正确），不匹配项不出现在结果里
    livewire(Picker::class, [
        'multiple' => false,
        'acceptedMimes' => ['application/pdf'],
        'ratio' => 1,
        'statePath' => 'data.certificate',
        'initialValues' => [],
    ])->assertSee('证书.pdf')->assertDontSee('图片.jpg');
});

it('filters the library by directory (including descendants) inside the picker', function () {
    $shop = MediaDirectory::create(['name' => '商城', 'team_id' => null]);
    $spec = MediaDirectory::create(['name' => '规格图', 'parent_id' => $shop->id, 'team_id' => null]);
    makePickerMedia(['path' => 'sn/' . now()->format('Ymd') . '/aaaa.jpg', 'name' => '商品图.jpg', 'directory_id' => $spec->id]);
    makePickerMedia(['path' => 'sn/' . now()->format('Ymd') . '/bbbb.jpg', 'name' => '横幅图.jpg']);

    $component = livewire(Picker::class, [
        'multiple' => false,
        'acceptedMimes' => [],
        'ratio' => 1,
        'statePath' => 'data.image',
        'initialValues' => [],
    ]);

    $component->call('setLibraryDir', $shop->id)
        ->assertSet('libraryDir', $shop->id)
        ->assertSee('商品图.jpg')
        ->assertDontSee('横幅图.jpg')
        ->call('setLibraryDir', null)
        ->assertSee('商品图.jpg')
        ->assertSee('横幅图.jpg');
});

it('filters by type combined with directory (not clearing)', function () {
    $shop = MediaDirectory::create(['name' => '商城', 'team_id' => null]);
    makePickerMedia(['name' => '商城图片.jpg', 'directory_id' => $shop->id]);
    makePickerMedia(['name' => '商城文档.pdf', 'ext' => 'pdf', 'mime' => 'application/pdf', 'width' => null, 'height' => null, 'directory_id' => $shop->id]);
    makePickerMedia(['name' => '外面图片.jpg']);

    $component = livewire(Picker::class, [
        'multiple' => false,
        'acceptedMimes' => [],
        'ratio' => 1,
        'statePath' => 'data.image',
        'initialValues' => [],
    ]);

    $component->call('setLibraryType', 'image')
        ->call('setLibraryDir', $shop->id)
        ->assertSet('libraryType', 'image') // 类型未被目录清空
        ->assertSee('商城图片.jpg')
        ->assertDontSee('商城文档.pdf')
        ->assertDontSee('外面图片.jpg');
});

it('toggles selection cumulatively in multiple mode', function () {
    $one = makePickerMedia();
    $two = makePickerMedia();

    $component = livewire(Picker::class, [
        'multiple' => true,
        'acceptedMimes' => [],
        'ratio' => 1,
        'statePath' => 'data.gallery',
        'initialValues' => [],
    ]);

    $component->call('toggle', $one->id)->call('toggle', $two->id)
        ->assertSet('selection', [$one->id, $two->id])
        ->call('toggle', $one->id)
        ->assertSet('selection', [$two->id]);
});

it('replaces selection in single mode', function () {
    $one = makePickerMedia();
    $two = makePickerMedia();

    $component = livewire(Picker::class, [
        'multiple' => false,
        'acceptedMimes' => [],
        'ratio' => 1,
        'statePath' => 'data.image',
        'initialValues' => [],
    ]);

    $component->call('toggle', $one->id)
        ->assertSet('selection', [$one->id])
        ->call('toggle', $two->id)
        ->assertSet('selection', [$two->id])
        ->call('toggle', $two->id)
        ->assertSet('selection', []);
});

it('dispatches sn-media-selected with value objects and urls on confirm', function () {
    $one = makePickerMedia(['name' => '第一张.jpg']);
    $two = makePickerMedia(['name' => '第二张.jpg']);

    $component = livewire(Picker::class, [
        'multiple' => true,
        'acceptedMimes' => [],
        'ratio' => 1,
        'statePath' => 'data.gallery',
        'initialValues' => [],
    ]);

    $component
        ->call('toggle', $two->id)
        ->call('toggle', $one->id)
        ->call('confirm')
        ->assertDispatched('sn-media-selected', function (string $name, array $params) use ($one, $two) {
            expect($params['statePath'])->toBe('data.gallery')
                ->and($params['values'])->toHaveCount(2)
                ->and($params['values'][0]['path'])->toBe($two->path)
                ->and($params['values'][1]['path'])->toBe($one->path)
                ->and($params['values'][0])->toHaveKeys(['path', 'name', 'mime', 'size', 'width', 'height'])
                ->and($params['urls'])->toHaveKeys([$one->path, $two->path]);

            return true;
        });
});

it('preselects existing values by path in mount', function () {
    $one = makePickerMedia();
    $two = makePickerMedia();

    livewire(Picker::class, [
        'multiple' => true,
        'acceptedMimes' => [],
        'ratio' => 1,
        'statePath' => 'data.gallery',
        'initialValues' => [$one->toValue(), $two->toValue()],
    ])->assertSet('selection', [$one->id, $two->id]);
});
