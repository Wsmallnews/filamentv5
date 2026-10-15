<?php

namespace App\Filament\Components;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Wsmallnews\FilamentNestedset\Filament\Components\SidebarNestedset;
use Wsmallnews\Media\Models\Media;
use Wsmallnews\Media\Models\MediaDirectory;

/**
 * SidebarNestedset 组件验证用子类（临时，未提交）：
 * media 目录树——nameLabel 组装「名称 + 含子孙计数」。
 * 点击事件（leaf/node-click）与选中高亮（$activeId 回传）由宿主页处理。
 */
class TestDirectoriesTree extends SidebarNestedset
{
    protected static ?string $model = MediaDirectory::class;

    protected static ?int $level = 3;

    /**
     * 创建/编辑共用表单（子类必须实现，与 page 契约一致）
     *
     * @return array<mixed>
     */
    protected function schema(array $arguments): array
    {
        return [
            TextInput::make('name')
                ->label(__('sn-media::media.directories.name'))
                ->maxLength(20)
                ->required(),
        ];
    }

    protected function getRecordLabel(Model $record): HtmlString | string
    {
        $count = Media::query()
            ->whereIn('directory_id', $record->descendants()->pluck('id')->merge([$record->id]))
            ->count();

        return new HtmlString('<span class="min-w-0 truncate">' . e($record->name) . '</span> <span class="text-xs tabular-nums text-gray-400">' . $count . '</span>');
    }
}
