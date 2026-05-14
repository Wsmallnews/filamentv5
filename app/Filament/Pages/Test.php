<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Kalnoy\Nestedset\QueryBuilder;
use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{

    protected static ?string $model = \App\Models\Navigation::class;

    protected static string $recordTitleAttribute = 'name';

    protected function schema(array $arguments): array
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
            TextInput::make('name')->label('Route Name')
                ->required(fn(Get $get) => $get('type') === 'route')
                ->markAsRequired()
                ->visibleJs(<<<'JS'
                        $get('type') == 'route'
                    JS),
            TextInput::make('description')->label('Description')
                ->required(fn(Get $get) => $get('type') === 'action')
                ->markAsRequired()
                ->visibleJs(<<<'JS'
                        $get('type') == 'action'
                    JS),
        ];
    }


    public function infolistSchema(): array
    {
        return [
            //
        ];
    }
}
