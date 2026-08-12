<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\Hooks;

use NitsanAi\MyBlog\Domain\Repository\PostRepository;
use NitsanAi\MyBlog\Domain\Repository\CommentRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use Symfony\Component\Mime\Address;

class CommentDataHandlerHook
{
    protected array $commentsBeingApproved = [];

    public function processDatamap_preProcessFieldArray(array &$fieldArray, string $table, string $id, $pObj): void
    {
        if ($table === 'tx_myblog_domain_model_comment' && isset($fieldArray['approved'])) {
            if ((int)$fieldArray['approved'] === 1 && is_numeric($id)) {
                $commentRepository = GeneralUtility::makeInstance(CommentRepository::class);
                $currentStatus = $commentRepository->getRawApprovalStatus((int)$id);
                
                if ($currentStatus === 0) {
                    $this->commentsBeingApproved[] = (int)$id;
                }
            }
        }
    }

    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray, $pObj): void
    {
        if ($table === 'tx_myblog_domain_model_comment') {
            $postRepository = GeneralUtility::makeInstance(PostRepository::class);
            $postRepository->recalculateFromCommentDatabaseId((int)$id);

            if (is_numeric($id) && in_array((int)$id, $this->commentsBeingApproved, true)) {
                $this->sendApprovalEmail((int)$id);
                $this->commentsBeingApproved = array_diff($this->commentsBeingApproved, [(int)$id]);
            }
        }
    }
    
    public function processCmdmap_postProcess(string $command, string $table, $id, $value, $pObj, &$pasteUpdate, &$pasteDatamap): void
    {
        if ($table === 'tx_myblog_domain_model_comment') {
            $postRepository = GeneralUtility::makeInstance(PostRepository::class);
            $postRepository->recalculateFromCommentDatabaseId((int)$id);
        }
    }

    protected function sendApprovalEmail(int $commentId): void
    {
        $commentRepository = GeneralUtility::makeInstance(CommentRepository::class);
        $comment = $commentRepository->findByUid($commentId);
        
        if ($comment && $comment->getAuthorEmail()) {
            $post = $comment->getPost();
            if ($post) {
                $postLink = '';
                try {
                    $siteFinder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\SiteFinder::class);
                    $site = $siteFinder->getSiteByPageId($post->getPid());
                    $router = $site->getRouter();
                    $postLink = (string)$router->generateUri(
                        $post->getPid(),
                        ['tx_myblog_blogplugin' => ['action' => 'show', 'controller' => 'Post', 'post' => $post->getUid()]]
                    );
                } catch (\Exception $e) {
                    // Fallback if site routing is unavailable
                }

                $email = GeneralUtility::makeInstance(FluidEmail::class);
                $email->to($comment->getAuthorEmail())
                    ->from(new Address('no-reply@demo.local', 'My Blog'))
                    ->subject('Your Comment is Approved: ' . $post->getTitle())
                    ->setTemplate('CommentApproved')
                    ->assign('comment', $comment)
                    ->assign('post', $post)
                    ->assign('postLink', $postLink);
                    
                GeneralUtility::makeInstance(MailerInterface::class)->send($email);
            }
        }
    }
}
