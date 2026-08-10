<?php

return [
    // 'myblog_main' => [
    //     'labels' => [
    //         'title' => 'Blogs',
    //     ],
    //     'iconIdentifier' => 'actions-document',
    //     'position' => [
    //         'after' => 'web',
    //     ],
    // ],

    'myblog_posts' => [
        'parent' => 'web',
        'position' => [
            'after' => 'info',
        ],
        'access' => 'user',
        'path' => '/module/myblog/posts',
        'iconIdentifier' => 'actions-document',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'labels' => 'LLL:EXT:my_blog/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'MyBlog',
        'routes' => [
            '_default' => [
                'target' => \NitsanAi\MyBlog\Controller\Backend\PostController::class . '::listAction',
            ],
        ],
        'controllerActions' => [
            \NitsanAi\MyBlog\Controller\Backend\PostController::class => [
                'list',
            ],
        ],
    ],
];