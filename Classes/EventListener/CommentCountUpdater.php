<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\EventListener;

use NitsanAi\MyBlog\Domain\Model\Comment;
use NitsanAi\MyBlog\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

class CommentCountUpdater
{
    #[AsEventListener(identifier: 'my-blog/comment-added')]
    public function onAdded(EntityAddedToPersistenceEvent $event): void
    {
        $this->handleExtbaseEvent($event->getObject());
    }

    #[AsEventListener(identifier: 'my-blog/comment-updated')]
    public function onUpdated(EntityUpdatedInPersistenceEvent $event): void
    {
        $this->handleExtbaseEvent($event->getObject());
    }

    #[AsEventListener(identifier: 'my-blog/comment-removed')]
    public function onRemoved(EntityRemovedFromPersistenceEvent $event): void
    {
        $this->handleExtbaseEvent($event->getObject());
    }

    private function handleExtbaseEvent(object $entity): void
    {
        if (!($entity instanceof Comment)) {
            return;
        }
        $post = $entity->getPost();
        if ($post) {
            $postRepository = GeneralUtility::makeInstance(PostRepository::class);
            $postRepository->recalculateCommentCounts($post->getUid());
        }
    }
}
