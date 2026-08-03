<?php

namespace NitsanAi\MyBlog\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

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

    protected function applySortBy(QueryInterface $query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'oldest':
                $query->setOrderings(['crdate' => QueryInterface::ORDER_ASCENDING]);
                break;
            case 'views_desc':
                $query->setOrderings(['views' => QueryInterface::ORDER_DESCENDING]);
                break;
            case 'views_asc':
                $query->setOrderings(['views' => QueryInterface::ORDER_ASCENDING]);
                break;
            case 'reading_asc':
                $query->setOrderings(['readingTime' => QueryInterface::ORDER_ASCENDING]);
                break;
            case 'reading_desc':
                $query->setOrderings(['readingTime' => QueryInterface::ORDER_DESCENDING]);
                break;
            default:
                $query->setOrderings(['crdate' => QueryInterface::ORDER_DESCENDING]);
                break;
        }
    }

    public function findAllLimited(?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null, string $sortBy = 'newest', ?array $commentedPostUids = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->applySortBy($query, $sortBy);
        
        $constraints = [];
        if ($feUser !== null) {
            $constraints[] = $query->equals('feUser', $feUser);
        }
        if ($commentedPostUids !== null) {
            $constraints[] = $query->in('uid', $commentedPostUids);
        }
        if (count($constraints) > 0) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        if ($limit !== null && $limit > 0) {
            $query->setLimit($limit);
        }
        return $query->execute();
    }

    public function findByCategory(Category $category, ?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null, string $sortBy = 'newest', ?array $commentedPostUids = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->applySortBy($query, $sortBy);
        
        $constraints = [$query->contains('categories', $category)];
        if ($feUser !== null) {
            $constraints[] = $query->equals('feUser', $feUser);
        }
        if ($commentedPostUids !== null) {
            $constraints[] = $query->in('uid', $commentedPostUids);
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
    public function findByCategories(array $categories, ?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null, string $sortBy = 'newest', ?array $commentedPostUids = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->applySortBy($query, $sortBy);
        
        if ($categories === []) {
            return $query->matching($query->equals('uid', 0))->execute();
        }
        $catConstraints = [];
        foreach ($categories as $category) {
            $catConstraints[] = $query->contains('categories', $category);
        }
        $constraint = $query->logicalOr(...$catConstraints);
        
        $additionalConstraints = [];
        if ($feUser !== null) {
            $additionalConstraints[] = $query->equals('feUser', $feUser);
        }
        if ($commentedPostUids !== null) {
            $additionalConstraints[] = $query->in('uid', $commentedPostUids);
        }
        
        if (count($additionalConstraints) > 0) {
            $query->matching($query->logicalAnd($constraint, ...$additionalConstraints));
        } else {
            $query->matching($constraint);
        }
        
        if ($limit !== null && $limit > 0) {
            $query->setLimit($limit);
        }
        return $query->execute();
    }
}
