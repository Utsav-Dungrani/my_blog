<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\Controller\Backend;

use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
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
        $posts = $pageId > 0 ? $this->postRepository->findAll() : [];

        // Fetch categories and assign to template
        $categoryRepository = GeneralUtility::makeInstance(\NitsanAi\MyBlog\Domain\Repository\CategoryRepository::class);
        $moduleTemplate->assign('categories', $categoryRepository->findAll());

        // Load JS module
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@my-blog/backend/PostFilter.js');
        // Ensure modal links work in backend modules (Bootstrap may not auto-init in some contexts)
        $pageRenderer->loadJavaScriptModule('@my-blog/backend/InitModals.js');

        // Allow buttons to be registered (XCLASS friendly!)
        $this->registerDocHeaderButtons($moduleTemplate, $pageId);

        $moduleTemplate->assign('posts', $posts);
        $moduleTemplate->assign('pageId', $pageId);

        return $moduleTemplate->renderResponse('Backend/Post/List');
    }

    /**
     * Generates standard buttons for the backend module
     */
    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate, int $pageId): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        
        $hasComponentFactory = class_exists(\TYPO3\CMS\Backend\Template\Components\ComponentFactory::class);
        $componentFactory = $hasComponentFactory ? GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\Components\ComponentFactory::class) : null;

        // 1. Reload Button (TYPO3 v14 adds this automatically, so we only add it in v13)
        $typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
        if ($typo3Version->getMajorVersion() < 14) {
            $reloadButton = $hasComponentFactory ? $componentFactory->createLinkButton() : $buttonBar->makeLinkButton();
            $reloadButton->setHref((string)$uriBuilder->buildUriFromRoute('myblog_posts', ['id' => $pageId]))
                ->setTitle('Reload')
                ->setIcon($iconFactory->getIcon('actions-refresh', IconSize::SMALL));
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }

        // 2. View webpage (only if a valid page id is selected)
        if ($pageId > 0) {
            // Using PreviewUriBuilder for safe preview links
            $previewUriBuilder = PreviewUriBuilder::create($pageId);
            $previewDataAttributes = $previewUriBuilder->buildDispatcherDataAttributes();
            if ($previewDataAttributes) {
                $viewButton = $hasComponentFactory ? $componentFactory->createLinkButton() : $buttonBar->makeLinkButton();
                $viewButton->setHref('#')
                    ->setDataAttributes($previewDataAttributes)
                    ->setTitle('View Webpage')
                    ->setShowLabelText(true)
                    ->setIcon($iconFactory->getIcon('actions-view-page', IconSize::SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            }

            // 3. Create New Record button
            $newRecordUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['tx_myblog_domain_model_post' => [$pageId => 'new']],
                'returnUrl' => (string)$uriBuilder->buildUriFromRoute('myblog_posts', ['id' => $pageId])
            ]);
            $createButton = $hasComponentFactory ? $componentFactory->createLinkButton() : $buttonBar->makeLinkButton();
            $createButton->setHref($newRecordUrl)
                ->setTitle('Create New Post')
                ->setShowLabelText(true)
                ->setIcon($iconFactory->getIcon('actions-add', IconSize::SMALL));
            $buttonBar->addButton($createButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }
    }
}
