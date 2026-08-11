<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use NitsanAi\MyBlog\Domain\Repository\PostRepository;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;

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
        $posts = $pageId > 0 ? $this->postRepository->findAll() : [];

        // Fetch categories and assign to template
        $categoryRepository = GeneralUtility::makeInstance(\NitsanAi\MyBlog\Domain\Repository\CategoryRepository::class);
        $moduleTemplate->assign('categories', $categoryRepository->findAll());

        // Load JS module
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@my-blog/backend/PostFilter.js');

        // Allow buttons to be registered (XCLASS friendly!)
        $this->registerDocHeaderButtons($moduleTemplate, $pageId);

        $moduleTemplate->assign('posts', $posts);
        $moduleTemplate->assign('pageId', $pageId);

        return $moduleTemplate->renderResponse('Backend/Post/List');
    }

    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate, int $pageId): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        // 1. Reload Button
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref((string)$uriBuilder->buildUriFromRoute('myblog_posts', ['id' => $pageId]))
            ->setTitle('Reload')
            ->setIcon($iconFactory->getIcon('actions-refresh', IconSize::SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

        if ($pageId > 0) {
            // 2. View Page Button
            $previewUriBuilder = PreviewUriBuilder::create($pageId);
            $previewDataAttributes = $previewUriBuilder->buildDispatcherDataAttributes();
            if ($previewDataAttributes) {
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes)
                    ->setTitle('View Webpage')
                    ->setShowLabelText(true)
                    ->setIcon($iconFactory->getIcon('actions-view-page', IconSize::SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }

            // 3. Create New Post Button
            $newRecordUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['tx_myblog_domain_model_post' => [$pageId => 'new']],
                'returnUrl' => (string)$uriBuilder->buildUriFromRoute('myblog_posts', ['id' => $pageId])
            ]);
            $createButton = $buttonBar->makeLinkButton()
                ->setHref($newRecordUrl)
                ->setTitle('Create New Post')
                ->setShowLabelText(true)
                ->setIcon($iconFactory->getIcon('actions-add', IconSize::SMALL));
            $buttonBar->addButton($createButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }
    }
}
