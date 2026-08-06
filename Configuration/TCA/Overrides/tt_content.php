<?php
defined('TYPO3') || die();

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$contentType = ExtensionUtility::registerPlugin(
    'MyBlog',
    'BlogPlugin',
    'Blog Posts Display & Management',
    'content-plugin',
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:my_blog/Configuration/FlexForms/BlogPlugin.xml',
    $contentType
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'pi_flexform',
    $contentType,
    'after:header'
);
