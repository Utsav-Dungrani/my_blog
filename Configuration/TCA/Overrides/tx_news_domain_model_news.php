<?php
defined('TYPO3') or die();

$newFields = [
    'subtitle' => [
        'label' => 'Subtitle',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
    'description_news' => [
        'label' => 'Description News',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
        ],
    ],
    'feature_image' => [
        'label' => 'Feature Image',
        'config' => [
            'type' => 'file',
            'maxitems' => 1,
            'allowed' => 'common-image-types'
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_news_domain_model_news', $newFields);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_news_domain_model_news',
    'subtitle, description_news',
    '',
    'after:title'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_news_domain_model_news',
    'feature_image',
    '',
    'after:fal_media'
);
