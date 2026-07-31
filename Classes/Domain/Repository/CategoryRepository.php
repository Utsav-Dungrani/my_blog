<?php

namespace NitsanAi\MyBlog\Domain\Repository;

use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 * @extends Repository<Category>
 */
class CategoryRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();
        $this->objectType = Category::class;
    }

    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        /** @var Typo3QuerySettings $querySettings */
        $querySettings = $query->getQuerySettings();

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $pageInformation = $request?->getAttribute('frontend.page.information');
        $pageId = 0;
        if ($pageInformation !== null && method_exists($pageInformation, 'getId')) {
            $pageId = (int)$pageInformation->getId();
        } elseif (isset($GLOBALS['TSFE']->id)) {
            $pageId = (int)$GLOBALS['TSFE']->id;
        }

        if ($pageId > 0) {
            $querySettings->setRespectStoragePage(true);
            $querySettings->setStoragePageIds([$pageId]);
        } else {
            $querySettings->setRespectStoragePage(false);
        }

        return $query;
    }

    public function findByUids(array $uids): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query->matching($query->in('uid', $uids))->execute();
    }
}
