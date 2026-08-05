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
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \NitsanAi\MyBlog\Hooks\CommentDataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \NitsanAi\MyBlog\Hooks\CommentDataHandlerHook::class;
