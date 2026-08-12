<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\Xclass;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use NitsanAi\MyBlog\Controller\Backend\PostController as OriginalPostController;

class MyBackendPostController extends OriginalPostController
{
    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate, int $pageId): void
    {
        // 1. Let the base controller add its standard buttons (Reload, View, Create)
        parent::registerDocHeaderButtons($moduleTemplate, $pageId);

        // 2. Add our extra "Go to List" button
        if ($pageId > 0) {
            $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            
            $hasComponentFactory = class_exists(\TYPO3\CMS\Backend\Template\Components\ComponentFactory::class);
            $componentFactory = $hasComponentFactory ? GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\Components\ComponentFactory::class) : null;

            $listModuleUrl = (string)$uriBuilder->buildUriFromRoute('web_list', ['id' => $pageId]);
            $listButton = $hasComponentFactory ? $componentFactory->createLinkButton() : $buttonBar->makeLinkButton();
            $listButton->setHref($listModuleUrl)
                ->setTitle('Go to Record List')
                ->setShowLabelText(true)
                ->setIcon($iconFactory->getIcon('actions-system-list-open', IconSize::SMALL));
            $buttonBar->addButton($listButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
    }
}
