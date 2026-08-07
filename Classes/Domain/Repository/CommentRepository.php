<?php

namespace NitsanAi\MyBlog\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

class CommentRepository extends Repository
{
    public function findPostUidsCommentedBy(int $feUserUid): array
    {
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('tx_myblog_domain_model_comment');
        
        $uids = $queryBuilder->select('post')
            ->from('tx_myblog_domain_model_comment')
            ->where(
                $queryBuilder->expr()->eq('fe_user', $queryBuilder->createNamedParameter($feUserUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('approved', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchFirstColumn();
            
        return empty($uids) ? [-1] : array_map('intval', $uids);
    }

    public function deleteUnapprovedOlderThan(int $days): int
    {
        $thresholdTimestamp = strtotime('-' . $days . ' days');
        if ($thresholdTimestamp === false) {
            return 0;
        }

        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('tx_myblog_domain_model_comment');

        return (int)$queryBuilder
            ->delete('tx_myblog_domain_model_comment')
            ->where(
                $queryBuilder->expr()->eq('approved', $queryBuilder->createNamedParameter(0, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->lt('crdate', $queryBuilder->createNamedParameter($thresholdTimestamp, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeStatement();
    }
}
