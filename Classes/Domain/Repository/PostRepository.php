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
            case 'comments_desc':
                $query->setOrderings(['commentCountTotal' => QueryInterface::ORDER_DESCENDING]);
                break;
            case 'comments_asc':
                $query->setOrderings(['commentCountTotal' => QueryInterface::ORDER_ASCENDING]);
                break;
            case 'community_desc':
                $query->setOrderings(['commentCountRegistered' => QueryInterface::ORDER_DESCENDING]);
                break;
            case 'public_desc':
                $query->setOrderings(['commentCountGuest' => QueryInterface::ORDER_DESCENDING]);
                break;
            default:
                $query->setOrderings(['crdate' => QueryInterface::ORDER_DESCENDING]);
                break;
        }
    }

    public function findAllLimited(?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null, string $sortBy = 'newest', ?array $commentedPostUids = null, string $search = '', ?int $startDate = null, ?int $endDate = null): QueryResultInterface
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
        if ($search !== '') {
            $constraints[] = $query->like('title', '%' . $search . '%');
        }
        if ($startDate !== null && $startDate > 0) {
            $constraints[] = $query->greaterThanOrEqual('crdate', $startDate);
        }
        if ($endDate !== null && $endDate > 0) {
            $constraints[] = $query->lessThanOrEqual('crdate', $endDate);
        }
        if (count($constraints) > 0) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        if ($limit !== null && $limit > 0) {
            $query->setLimit($limit);
        }
        return $query->execute();
    }

    public function findByCategory(Category $category, ?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null, string $sortBy = 'newest', ?array $commentedPostUids = null, string $search = '', ?int $startDate = null, ?int $endDate = null): QueryResultInterface
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
        if ($search !== '') {
            $constraints[] = $query->like('title', '%' . $search . '%');
        }
        if ($startDate !== null && $startDate > 0) {
            $constraints[] = $query->greaterThanOrEqual('crdate', $startDate);
        }
        if ($endDate !== null && $endDate > 0) {
            $constraints[] = $query->lessThanOrEqual('crdate', $endDate);
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
    public function findByCategories(array $categories, ?int $limit = null, ?\NitsanAi\MyBlog\Domain\Model\FrontendUser $feUser = null, string $sortBy = 'newest', ?array $commentedPostUids = null, string $search = '', ?int $startDate = null, ?int $endDate = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->applySortBy($query, $sortBy);
        
        if ($categories === [] && $search === '' && $startDate === null && $endDate === null) {
            return $query->matching($query->equals('uid', 0))->execute();
        }
        $constraint = null;
        if ($categories !== []) {
            $catConstraints = [];
            foreach ($categories as $category) {
                $catConstraints[] = $query->contains('categories', $category);
            }
            $constraint = $query->logicalOr(...$catConstraints);
        }
        
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
        if ($search !== '') {
            $additionalConstraints[] = $query->like('title', '%' . $search . '%');
        }
        if ($startDate !== null && $startDate > 0) {
            $additionalConstraints[] = $query->greaterThanOrEqual('crdate', $startDate);
        }
        if ($endDate !== null && $endDate > 0) {
            $additionalConstraints[] = $query->lessThanOrEqual('crdate', $endDate);
        }
        
        if ($constraint !== null) {
            if (count($additionalConstraints) > 0) {
                $query->matching($query->logicalAnd($constraint, ...$additionalConstraints));
            } else {
                $query->matching($constraint);
            }
        } elseif (count($additionalConstraints) > 0) {
            $query->matching($query->logicalAnd(...$additionalConstraints));
        } else {
            return $query->matching($query->equals('uid', 0))->execute();
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
        ?array $commentedPostUids = null,
        string $search = '',
        ?int $startDate = null,
        ?int $endDate = null
    ): array {
        if ($selectedCategory !== null) {
            $posts = $this->findByCategory($selectedCategory, 0, $feUser, $sortBy, $commentedPostUids, $search, $startDate, $endDate);
        } elseif ($configuredCategories !== [] || $search !== '' || $startDate !== null || $endDate !== null) {
            $posts = $this->findByCategories($configuredCategories, 0, $feUser, $sortBy, $commentedPostUids, $search, $startDate, $endDate);
        } else {
            $posts = $this->findAllLimited(0, $feUser, $sortBy, $commentedPostUids, $search, $startDate, $endDate);
        }

        $postsArray = $posts->toArray();

        return $postsArray;
    }

    public function recalculateCommentCounts(int $postUid): void
    {
        if ($postUid <= 0) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('tx_myblog_domain_model_comment');
        
        $row = $queryBuilder
            ->addSelectLiteral('SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) AS count_approved')
            ->addSelectLiteral('SUM(CASE WHEN approved = 1 AND fe_user > 0 THEN 1 ELSE 0 END) AS count_registered')
            ->addSelectLiteral('SUM(CASE WHEN approved = 1 AND fe_user = 0 THEN 1 ELSE 0 END) AS count_guest')
            ->from('tx_myblog_domain_model_comment')
            ->where(
                $queryBuilder->expr()->eq('post', $queryBuilder->createNamedParameter($postUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($row) {
            $updateBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                ->getQueryBuilderForTable('tx_myblog_domain_model_post');
            
            $updateBuilder
                ->update('tx_myblog_domain_model_post')
                ->where($updateBuilder->expr()->eq('uid', $updateBuilder->createNamedParameter($postUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)))
                ->set('comment_count_total', (int)$row['count_approved'])
                ->set('comment_count_registered', (int)$row['count_registered'])
                ->set('comment_count_guest', (int)$row['count_guest'])
                ->executeStatement();
        }
    }

    public function recalculateFromCommentDatabaseId(int $commentId): void
    {
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('tx_myblog_domain_model_comment');
        
        // Remove global restrictions so we can still find the post ID even if deleted
        $queryBuilder->getRestrictions()->removeAll();

        $comment = $queryBuilder
            ->select('post')
            ->from('tx_myblog_domain_model_comment')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($commentId, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if ($comment && !empty($comment['post'])) {
            $this->recalculateCommentCounts((int)$comment['post']);
        }
    }
}