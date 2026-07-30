<?php

namespace NitsanAi\MyBlog\Controller;

use NitsanAi\MyBlog\Domain\Model\Post;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use NitsanAi\MyBlog\Domain\Repository\PostRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class PostController extends ActionController
{
    public function __construct(
        protected readonly PostRepository $postRepository
    ) {}

    public function listAction(): ResponseInterface
    {
        $posts = $this->postRepository->findAll();
        $this->view->assign('posts', $posts);
        return $this->htmlResponse();
    }

    public function showAction(Post $post): ResponseInterface
    {
        $this->view->assign('post', $post);
        return $this->htmlResponse();
    }

    public function newAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function createAction(Post $newPost): ResponseInterface
    {
        $pageInformation = $this->request->getAttribute('frontend.page.information');
        $pageId = 0;
        if ($pageInformation !== null && method_exists($pageInformation, 'getId')) {
            $pageId = (int)$pageInformation->getId();
        }

        if ($pageId > 0) {
            $newPost->setPid($pageId);
        } else {
                    $this->addFlashMessage('Warning: could not detect current page id; record may be stored with pid=0', '', ContextualFeedbackSeverity::WARNING);
        }

        $this->postRepository->add($newPost);

        // Persist immediately so that the record is written with the intended PID
        GeneralUtility::makeInstance(PersistenceManagerInterface::class)->persistAll();

        $this->addFlashMessage('Blog post created successfully!');
        return $this->redirect('list');
    }

    public function editAction(Post $post): ResponseInterface
    {
        $this->view->assign('post', $post);
        return $this->htmlResponse();
    }

    public function updateAction(Post $post): ResponseInterface
    {
        $this->postRepository->update($post);
        $this->addFlashMessage('Blog post updated successfully!');
        return $this->redirect('list');
    }

    public function deleteAction(Post $post): ResponseInterface
    {
        $this->postRepository->remove($post);
        $this->addFlashMessage('Blog post deleted successfully!');
        return $this->redirect('list');
    }
}