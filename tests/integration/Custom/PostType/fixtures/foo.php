<?php

return [
    'autoload'     => true,
    'postTypeName' => 'foo',
    'config'       => [
        'postTypeArgs'   => [
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'has_archive'  => true,
            'menu_icon'    => 'dashicons-video-alt2',
            'description'  => 'Foo - example custom post type',
        ],
        'labelsConfig'   => [
            'pluralName'   => 'Foos',
            'singularName' => 'Foo',
        ],
        'supportsConfig' => [
            'comments'      => false,
            'post-formats'  => false,
            'trackbacks'    => false,
            'custom-fields' => false,
            'revisions'     => false,
        ],
        'columnsConfig'  => [],
    ],
];
