<?php
defined('TYPO3') || die();

use NitsanAi\MyBlog\Controller\PostController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTypoScriptSetup(
    '@import "EXT:my_blog/Configuration/TypoScript/setup.typoscript"'
);

ExtensionUtility::configurePlugin(
    'MyBlog',
    'BlogPlugin',
    [
        PostController::class => 'list, show, new, create, edit, update, delete',
    ],
    
    [
        PostController::class => 'new, create, edit, update, delete',
    ]
);