<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Wsmallnews\Media\Filament\Forms\FilePicker;
use Wsmallnews\Media\Filament\Forms\MediaPicker;

/**
 * MediaPicker 字段测试页：覆盖单图 / 多图排序 / 宽高比 / 文件 / 全格式 / FilePicker 直接上传（私有盘）场景。
 * 仅演示值契约，不落任何业务表。
 */
class MediaPickerTestPage extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static ?string $slug = 'media-picker-test';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string | \UnitEnum | null $navigationGroup = '素材库';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'MediaPicker 测试';

    protected string $view = 'filament.pages.media-picker-test';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('图片场景')
                    ->description('外观（宽高比）由调用处定义；多值数组序即排序')
                    ->columns(2)
                    ->schema([
                        MediaPicker::make('image')
                            ->label('单图（商品主图）')
                            ->acceptImages(),
                        MediaPicker::make('banner')
                            ->label('长方形（导航 Banner 3:1）')
                            ->acceptImages()
                            ->ratio(3),
                        MediaPicker::make('gallery')
                            ->label('多图（轮播，可排序）')
                            ->multiple()
                            ->acceptImages()
                            ->columnSpanFull(),
                    ]),
                Section::make('文件场景')
                    ->description('非图片：宽高为 null，下载以原始文件名返回')
                    ->columns(2)
                    ->schema([
                        MediaPicker::make('certificate')
                            ->label('证书（仅 PDF）')
                            ->acceptedMimes(['application/pdf']),
                        MediaPicker::make('attachment')
                            ->label('附件（全格式）'),
                    ]),
                Section::make('直接上传场景（FilePicker：不打开素材弹窗）')
                    ->description('点击直接拉起系统文件选择器；值契约与 MediaPicker 一致；支持 ->disk() 私有盘（如支付证书，不可直链，预览走 file 路由）')
                    ->columns(2)
                    ->schema([
                        FilePicker::make('contract')
                            ->label('支付证书（私有盘 local）')
                            ->acceptedMimes(['application/pdf'])
                            ->disk('local'),
                        FilePicker::make('photos')
                            ->label('多文件（默认盘）')
                            ->multiple()
                            ->acceptImages(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('提交（测试页不落库）')
                ->action(fn () => $this->submit()),
        ];
    }

    public function submit(): void
    {
        Notification::make()->title('表单已提交：值见页面底部 JSON 面板')->success()->send();
    }

    /**
     * 字段保存值（JSON 契约）展示
     */
    protected function valueFields(): array
    {
        return [
            'product.image（单值对象）' => $this->data['image'] ?? null,
            'navigation.banner（3:1 单值）' => $this->data['banner'] ?? null,
            'product.gallery（多值数组，序即排序）' => $this->data['gallery'] ?? null,
            'award.certificate（仅 PDF）' => $this->data['certificate'] ?? null,
            'attachment（全格式）' => $this->data['attachment'] ?? null,
            'pay.contract（FilePicker 私有盘单值）' => $this->data['contract'] ?? null,
            'photos（FilePicker 多值）' => $this->data['photos'] ?? null,
        ];
    }
}
