<?php

return [
    'ctrl'=> [
        'title' => 'Post',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'iconfile' => 'EXT:my_blog/Resources/Public/Icons/Extension.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'title, image, description, author, fe_user, reading_time, allow_comments, views, categories, comments, crdate, tstamp'],
    ],
    'columns' => [
        'title' => [
            'label' => 'title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'image' => [
            'label' => 'Blog Image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'minitems' => 1,
                'maxitems' => 1,
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'author' => [
            'label' => 'author',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'fe_user' => [
            'label' => 'Frontend Author (fe_users)',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'items' => [
                    ['label' => 'None / Guest', 'value' => 0],
                ],
                'default' => 0,
            ],
        ],
        'reading_time' => [
            'label' => 'Reading Time (minutes)',
            'config' => [
                'type' => 'number',
                'min' => 1,
                'default' => 5,
                'required' => true,
            ],
        ],
        'allow_comments' => [
            'label' => 'Allow Comments',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'views' => [
            'label' => 'Views Count',
            'config' => [
                'type' => 'number',
                'readOnly' => true,
                'default' => 0,
            ],
        ],
        'categories' => [
            'label' => 'Categories',
            'config' => [
                'type' => 'category',
                'required' => true,
            ],
        ],
        'comments' => [
            'label' => 'Comments',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_myblog_domain_model_comment',
                'foreign_field' => 'post',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'top',
                ],
            ],
        ],
        'crdate' => [
            'label' => 'Created Date',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'tstamp' => [
            'label' => 'Last Updated',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
    ],
];