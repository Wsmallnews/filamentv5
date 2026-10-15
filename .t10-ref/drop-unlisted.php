<?php

use Illuminate\Support\Facades\Schema;

if (Schema::hasColumn('sn_media', 'unlisted')) {
    Schema::table('sn_media', function ($table) {
        $table->dropColumn('unlisted');
    });
    echo 'column dropped' . PHP_EOL;
} else {
    echo 'column not exists' . PHP_EOL;
}
