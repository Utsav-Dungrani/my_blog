<?php
defined('TYPO3') || die();

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerPlugin(
    'MyBlog',
    'BlogPlugin',
    'Blog Posts Display & Management',
    'EXT:core/Resources/Public/Icons/T3icons/content/content-text.svg'
);