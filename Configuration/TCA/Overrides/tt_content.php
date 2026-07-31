<?php
defined('TYPO3') || die();

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$contentType = ExtensionUtility::registerPlugin(
    'MyBlog',
    'BlogPlugin',
    'Blog Posts Display & Management',
    'EXT:core/Resources/Public/Icons/T3icons/content/content-text.svg'
);

ExtensionManagementUtility::addPiFlexFormValue(
    $contentType,
    'FILE:EXT:my_blog/Configuration/FlexForms/BlogPlugin.xml'
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'pi_flexform',
    'list',
    'after:list_type'
);
