<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Pages\ViewActivityLog as ViewActivityLogRecords;

class ViewActivityLog extends ViewActivityLogRecords
{
    protected static string $resource = ActivityLogResource::class;
}