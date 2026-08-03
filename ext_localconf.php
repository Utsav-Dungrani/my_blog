<?php
defined('TYPO3') || die();

use NitsanAi\MyBlog\Controller\PostController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTypoScriptSetup(
    '@import "EXT:my_blog/Configuration/TypoScript/setup.typoscript"'
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['checkFeUserPid'] = false;

ExtensionUtility::configurePlugin(
    'MyBlog',
    'BlogPlugin',
    [
        PostController::class => 'list, show, new, create, edit, update, delete, addComment',
        \NitsanAi\MyBlog\Controller\AuthController::class => 'register, createAccount',
    ],
    [
        PostController::class => 'list, show, new, create, edit, update, delete, addComment',
        \NitsanAi\MyBlog\Controller\AuthController::class => 'register, createAccount',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_myblog_blog[sortBy]';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_myblog_blog[myPosts]';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_myblog_blog[commentedByMe]';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_myblog_blog[selectedCategory]';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_myblog_blog[showAll]';
