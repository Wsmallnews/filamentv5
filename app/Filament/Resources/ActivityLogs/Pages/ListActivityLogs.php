<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Pages\ListActivityLogs as ListActivityLogsRecords;

class ListActivityLogs extends ListActivityLogsRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}