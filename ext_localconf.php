<?php
defined('TYPO3') || die();

use NitsanAi\MyBlog\Controller\PostController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
    ],
    'CType'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \NitsanAi\MyBlog\Hooks\CommentDataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \NitsanAi\MyBlog\Hooks\CommentDataHandlerHook::class;

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][1710000000] = 'EXT:my_blog/Resources/Private/Templates/Email/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][1710000000] = 'EXT:my_blog/Resources/Private/Layouts/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'][1710000000] = 'EXT:my_blog/Resources/Private/Partials/';

// Register custom CKEditor YAML configuration for the Blog Extension
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['myblog_custom'] = 'EXT:my_blog/Configuration/RTE/Custom.yaml';

// Register extended News model via ProxyClassGenerator
$GLOBALS['TYPO3_CONF_VARS']['EXT']['news']['classes']['Domain/Model/News']['my_blog'] = 'my_blog';

// Register XCLASS for your own MyBlog Backend Module Controller
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\NitsanAi\MyBlog\Controller\Backend\PostController::class] = [
    'className' => \NitsanAi\MyBlog\Xclass\MyBackendPostController::class,
];
