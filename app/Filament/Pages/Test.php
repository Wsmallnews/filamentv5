<?php

namespace App\Filament\Pages;

use App\Models\Navigation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    protected static ?int $level = 3;

    protected static ?string $model = Navigation::class;

    protected static string $recordTitleAttribute = 'name';

    protected static bool $isScopedToTenant = false;

    protected static ?string $tabFieldName = 'type';

    protected static Alignment $infolistAlignment = Alignment::Right;

    public function getTabs(): array
    {
        return [
            'route' => Tab::make('Route')
                ->icon(Heroicon::MapPin),
            'action' => Tab::make('Action')
                ->icon(Heroicon::Bolt),
        ];
    }

    protected function nestedScoped(): array
    {
        return [
            'status' => 'active',
        ];
    }

    protected function getRecordLabel(Model $record): HtmlString|string
    {
        $icon = $record->type === 'route' ? '📍' : '⚡';

        return new HtmlString("{$icon} {$record->name}");
    }

    public function schema(array $arguments): array
    {
        return [
            Select::make('type')
                ->label('Type')
                ->options([
                    'route' => 'Route',
                    'action' => 'Action',
                ])
                ->default('route')
                ->required(),
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->markAsRequired(),
            TextInput::make('description')
                ->label('Description'),
            Select::make('status')
                ->label('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->default('active')
                ->required(),
        ];
    }

    public function infolistSchema(): array
    {
        return [
            TextEntry::make('type')
                ->label('Type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'route' => 'info',
                    'action' => 'warning',
                    default => 'gray',
                }),
            // TextEntry::make('name')
            //     ->label('Name'),
            // TextEntry::make('description')
            //     ->label('Description'),
            TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'inactive' => 'gray',
                    default => 'gray',
                }),
        ];
    }
}
