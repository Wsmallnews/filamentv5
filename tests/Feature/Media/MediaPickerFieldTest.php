<?php

use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Wsmallnews\Media\Filament\Forms\MediaPicker;

uses(RefreshDatabase::class);

/**
 * 宿主组件：以真实 Filament 表单渲染 MediaPicker 字段
 */
class MediaPickerHost extends Component implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MediaPicker::make('image')->acceptImages(),
                MediaPicker::make('gallery')->multiple()->acceptImages(),
                MediaPicker::make('banner')->ratio(3)->acceptImages(),
            ])
            ->statePath('data');
    }

    public function render()
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
        BLADE;
    }
}

it('renders picker fields with local alpine state and embedded picker component', function () {
    $html = Livewire::test(MediaPickerHost::class)->html();

    expect($html)->toContain('snMediaPickerField')
        ->toContain('aspect-ratio')
        // 嵌入的 Picker Livewire 子组件（wire:key 前缀）
        ->toContain('sn-media-picker-')
        ->toContain('data.image');
});

it('renders ratio as an inline aspect-ratio style', function () {
    $html = Livewire::test(MediaPickerHost::class)->html();

    expect($html)->toContain("'aspect-ratio:' + ratio");
});

it('evaluates picker props from configuration', function () {
    $banner = MediaPicker::make('banner')->ratio(3)->acceptImages();
    $gallery = MediaPicker::make('gallery')->multiple()->acceptImages();

    expect($banner->getRatio())->toBe(3.0)
        ->and($banner->getAcceptedMimes())->toBe(['image/*'])
        ->and($banner->isMultiple())->toBeFalse()
        ->and($gallery->isMultiple())->toBeTrue();
});

it('normalizes state on hydration', function () {
    $value = ['path' => 'sn/a.jpg', 'name' => 'a.jpg', 'mime' => 'image/jpeg', 'size' => 10, 'width' => 5, 'height' => 5];

    $component = Livewire::test(MediaPickerHost::class);

    $form = $component->instance()->form;
    $form->fill(['image' => $value, 'gallery' => [$value, 'garbage']]);

    $state = $form->getState();

    expect($state['image'])->toBe($value)
        ->and($state['gallery'])->toBe([$value]);
});
