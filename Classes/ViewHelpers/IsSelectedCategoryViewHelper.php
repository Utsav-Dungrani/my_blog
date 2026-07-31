<?php

declare(strict_types=1);

namespace NitsanAi\MyBlog\ViewHelpers;

use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns whether a category is the currently selected frontend filter.
 */
final class IsSelectedCategoryViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('category', Category::class, 'Category to check', true);
        $this->registerArgument('selectedCategory', Category::class, 'Currently selected category');
    }

    public function render(): bool
    {
        /** @var Category $category */
        $category = $this->arguments['category'];
        /** @var Category|null $selectedCategory */
        $selectedCategory = $this->arguments['selectedCategory'];

        return $selectedCategory !== null && $category->getUid() === $selectedCategory->getUid();
    }
}
