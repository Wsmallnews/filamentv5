<?php

use Wsmallnews\Preference\Models;

return [
    /**
     * Default scopeable configuration
     */
    'scopeable' => [
        'scope_type' => 'sn-preference',
        'scope_id' => 0,
    ],

    /**
     * Custom models
     */
    'models' => [
        'preference' => Models\Preference::class,
    ],

    /**
     * Base file directory, will automatically append current date (used only for filament default upload component)
     */
    'file_directory' => 'sn/preference/',
];
