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
            if (empty($commentedPostUids)) {
                $commentedPostUids = [-1]; // Prevent empty array error in IN clause
            }
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
            if (empty($commentedPostUids)) {
                $commentedPostUids = [-1];
            }
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
            if (empty($commentedPostUids)) {
                $commentedPostUids = [-1];
            }
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

    public function removeImageReferencesForPost(int $postUid): void
    {
        if ($postUid <= 0) {
            return;
        }
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->delete('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($postUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_myblog_domain_model_post')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter('image'))
            )
            ->executeStatement();
    }

    public function findPostsFilteredAndSorted(
        ?Category $selectedCategory = null,
        array $configuredCategories = [],
        ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null,
        string $sortBy = 'newest',
        ?array $commentedPostUids = null
    ): array {
        if ($selectedCategory !== null) {
            $posts = $this->findByCategory($selectedCategory, 0, $feUser, $sortBy, $commentedPostUids);
        } elseif ($configuredCategories !== []) {
            $posts = $this->findByCategories($configuredCategories, 0, $feUser, $sortBy, $commentedPostUids);
        } else {
            $posts = $this->findAllLimited(0, $feUser, $sortBy, $commentedPostUids);
        }

        $postsArray = $posts->toArray();

        if (in_array($sortBy, ['comments_desc', 'comments_asc', 'community_desc', 'public_desc'], true)) {
            $postUids = array_map(fn($p) => $p->getUid(), $postsArray);
            if (!empty($postUids)) {
                $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                    ->getQueryBuilderForTable('tx_myblog_domain_model_comment');
                
                $queryBuilder->select('post')
                    ->addSelectLiteral('SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) AS count_approved')
                    ->addSelectLiteral('SUM(CASE WHEN approved = 1 AND fe_user > 0 THEN 1 ELSE 0 END) AS count_registered')
                    ->addSelectLiteral('SUM(CASE WHEN approved = 1 AND fe_user = 0 THEN 1 ELSE 0 END) AS count_guest')
                    ->from('tx_myblog_domain_model_comment')
                    ->where($queryBuilder->expr()->in('post', $queryBuilder->createNamedParameter($postUids, \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY)))
                    ->groupBy('post');
                
                $counts = [];
                $statement = $queryBuilder->executeQuery();
                while ($row = $statement->fetchAssociative()) {
                    $counts[$row['post']] = $row;
                }
                
                usort($postsArray, function ($a, $b) use ($sortBy, $counts) {
                    $uidA = $a->getUid(); $uidB = $b->getUid();
                    $cA = $counts[$uidA] ?? ['count_approved' => 0, 'count_registered' => 0, 'count_guest' => 0];
                    $cB = $counts[$uidB] ?? ['count_approved' => 0, 'count_registered' => 0, 'count_guest' => 0];
                    
                    $valA = 0; $valB = 0;
                    if ($sortBy === 'comments_desc' || $sortBy === 'comments_asc') {
                        $valA = (int)$cA['count_approved']; $valB = (int)$cB['count_approved'];
                    } elseif ($sortBy === 'community_desc') {
                        $valA = (int)$cA['count_registered']; $valB = (int)$cB['count_registered'];
                    } elseif ($sortBy === 'public_desc') {
                        $valA = (int)$cA['count_guest']; $valB = (int)$cB['count_guest'];
                    }
                    
                    if ($valA === $valB) {
                        return ($b->getCrdate() ?? new \DateTime()) <=> ($a->getCrdate() ?? new \DateTime());
                    }
                    
                    if ($sortBy === 'comments_asc') {
                        return $valA <=> $valB;
                    }
                    return $valB <=> $valA;
                });
            }
        }

        return $postsArray;
    }
}