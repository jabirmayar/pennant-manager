<?php

return [
    'navigation_group' => 'Settings',

    /*
    |--------------------------------------------------------------------------
    | Scope Models
    |--------------------------------------------------------------------------
    | Models that can be used as feature flag scopes.
    | Extended format lets you set per-model search and display columns.
    */
    'scope_models' => [
        'User' => [
            'model'         => \App\Models\User::class,
            'search_column' => 'email',
            'label_column'  => 'name',   // optional — what shows in the list
        ],
        // 'Team' => [
        //     'model'         => \App\Models\Team::class,
        //     'search_column' => 'name',
        //     'segment_columns' => ['plan', 'role', 'country'], // optional - used for filtering and segmenting adoption
        // ],
    ],

    // Global fallback — used when a model entry is a plain string
    'scope_search_column' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Feature Discovery
    |--------------------------------------------------------------------------
    | Paths scanned for Feature::define() calls. Accepts files or directories.
    | Discovered features are merged with `discovered_features` below.
    */
    'discovery_paths' => [
        app_path('Providers/AppServiceProvider.php'),
        // app_path('Features'),  // if you use a dedicated folder
    ],

    /*
    | Manually list features here as a fallback — useful for features defined
    | dynamically or in packages that can't be scanned reliably.
    */
    'discovered_features' => [
        // 'some-dynamic-feature',
    ],
];