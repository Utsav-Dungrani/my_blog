<?php

return [
    'ctrl' => [
        'title' => 'Comment',
        'label' => 'author_name',
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
        '1' => ['showitem' => 'approved, author_name, author_email, content, fe_user, post, crdate'],
    ],
    'columns' => [
        'approved' => [
            'label' => 'Comment Approved',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'author_name' => [
            'label' => 'Author Name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'author_email' => [
            'label' => 'Author Email',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'content' => [
            'label' => 'Comment Content',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'fe_user' => [
            'label' => 'Frontend User (fe_users)',
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
        'post' => [
            'label' => 'Post',
            'description' => 'No post available for commenting if empty.',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_myblog_domain_model_post',
                'foreign_table_where' => 'AND tx_myblog_domain_model_post.pid=###REC_FIELD_pid### ORDER BY tx_myblog_domain_model_post.title',
                'minitems' => 1,
                'maxitems' => 1,
                'required' => true,
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
    ],
];
