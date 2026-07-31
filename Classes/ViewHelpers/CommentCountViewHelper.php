<?php

declare(strict_types=1);

namespace NitsanAi\MyBlog\ViewHelpers;

use Countable;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Formats a comment collection as a human-friendly count.
 */
final class CommentCountViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('comments', 'mixed', 'Comment collection to count', true);
    }

    public function render(): string
    {
        $comments = $this->arguments['comments'];
        $count = $comments instanceof Countable ? count($comments) : 0;

        return match ($count) {
            0 => 'No comments',
            1 => '1 comment',
            default => $count . ' comments',
        };
    }
}
