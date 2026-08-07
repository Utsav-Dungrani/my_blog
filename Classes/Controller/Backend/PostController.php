<?php

declare(strict_types=1);

namespace NitsanAi\MyBlog\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use NitsanAi\MyBlog\Domain\Repository\PostRepository;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class PostController extends ActionController
{
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected PostRepository $postRepository
    ) {
    }

    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        
        $moduleTemplate->setTitle('Blog Post Manager');

        $pageId = (int)($this->request->getQueryParams()['id'] ?? 0);

        if ($pageId > 0) {
            $posts = $this->postRepository->findAll();
        } else {
            $posts = [];
        }

        $moduleTemplate->assign('posts', $posts);
        $moduleTemplate->assign('pageId', $pageId);

        return $moduleTemplate->renderResponse('Backend/Post/List');
    }
}
