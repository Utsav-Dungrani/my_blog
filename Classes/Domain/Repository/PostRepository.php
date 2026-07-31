<?php

namespace NitsanAi\MyBlog\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Domain\Model\Category;

class PostRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        /** @var Typo3QuerySettings $querySettings */
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(true);

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $pageInformation = $request?->getAttribute('frontend.page.information');
        $pageId = 0;
        if ($pageInformation !== null && method_exists($pageInformation, 'getId')) {
            $pageId = (int)$pageInformation->getId();
        } elseif (isset($GLOBALS['TSFE']->id)) {
            $pageId = (int)$GLOBALS['TSFE']->id;
        }

        if ($pageId > 0) {
            $querySettings->setStoragePageIds([$pageId]);
        }

        return $query;
    }

    public function findAllLimited(?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null): QueryResultInterface
    {
        $query = $this->createQuery();
        if ($feUser !== null) {
            $query->matching($query->equals('feUser', $feUser));
        }
        if ($limit !== null && $limit > 0) {
            $query->setLimit($limit);
        }
        return $query->execute();
    }

    public function findByCategory(Category $category, ?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [$query->contains('categories', $category)];
        if ($feUser !== null) {
            $constraints[] = $query->equals('feUser', $feUser);
        }
        $query->matching($query->logicalAnd(...$constraints));
        
        if ($limit !== null && $limit > 0) {
            $query->setLimit($limit);
        }
        return $query->execute();
    }

    /**
     * @param Category[] $categories
     */
    public function findByCategories(array $categories, ?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null): QueryResultInterface
    {
        $query = $this->createQuery();
        if ($categories === []) {
            return $query->matching($query->equals('uid', 0))->execute();
        }
        $catConstraints = [];
        foreach ($categories as $category) {
            $catConstraints[] = $query->contains('categories', $category);
        }
        $constraint = $query->logicalOr(...$catConstraints);
        if ($feUser !== null) {
            $constraint = $query->logicalAnd($constraint, $query->equals('feUser', $feUser));
        }
        $query->matching($constraint);
        
        if ($limit !== null && $limit > 0) {
            $query->setLimit($limit);
        }
        return $query->execute();
    }
}
