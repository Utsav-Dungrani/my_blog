<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'My Blog Extension',
    'description' => 'A custom Extbase blog extension',
    'category' => 'plugin',
    'author' => 'Utsav',
    'author_email' => 'utsav.dungrani@mail.nitsan.ai',
    'state' => 'alpha',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-14.99.99',
            'news' => '13.0.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],   
    ],
];