<?php

use Wsmallnews\Preference\Models;

return [
    /**
     * Scopeable 实例声明（main 为默认实例，必须存在；差异实例按需在此声明）
     */
    'scopeables' => [
        'main' => [
            'scope_type' => 'sn-preference',
            'scope_id' => 0,
        ],
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
