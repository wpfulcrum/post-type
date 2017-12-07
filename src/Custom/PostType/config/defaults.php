<?php

return [
    'autoload' => true,
    'postType' => 'book',
    'config'   => [
        'postTypeArgs'   => [
            'hierarchical' => false,
        ],
        'labelsConfig'   => [
            'useBuilder'   => true,
            'pluralName'   => 'Books',
            'singularName' => 'Book',
            'labels'       => [],
        ],
        'supportsConfig' => [
            'additionalSupports' => [],
        ],
        'columnsConfig'  => [
            'columnsFilter' => [],
            'columnsData'   => [],
        ],
    ],
];
