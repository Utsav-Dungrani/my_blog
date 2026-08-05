<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\Hooks;

use NitsanAi\MyBlog\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CommentDataHandlerHook
{
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray, $pObj): void
    {
        if ($table === 'tx_myblog_domain_model_comment') {
            $postRepository = GeneralUtility::makeInstance(PostRepository::class);
            $postRepository->recalculateFromCommentDatabaseId((int)$id);
        }
    }
    
    public function processCmdmap_postProcess(string $command, string $table, $id, $value, $pObj, &$pasteUpdate, &$pasteDatamap): void
    {
        if ($table === 'tx_myblog_domain_model_comment') {
            $postRepository = GeneralUtility::makeInstance(PostRepository::class);
            $postRepository->recalculateFromCommentDatabaseId((int)$id);
        }
    }
}
