<?php
defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'my_blog',
    'Configuration/TypoScript',
    'My Blog'
);
