<?php

namespace NitsanAi\MyBlog\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

class PostRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(true);

        // Try to limit queries to the current frontend page so posts saved to that page are returned
        $pageId = 0;
        if (isset($GLOBALS['TSFE']->id)) {
            $pageId = (int)$GLOBALS['TSFE']->id;
        }
        if ($pageId > 0) {
            $querySettings->setStoragePageIds([$pageId]);
        }
        
        $this->setDefaultQuerySettings($querySettings);
    }
}