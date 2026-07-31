<?php

declare(strict_types=1);

namespace NitsanAi\MyBlog\ViewHelpers;

use DateTimeInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns whether a record has been updated after its creation time.
 */
final class HasBeenEditedViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('created', DateTimeInterface::class, 'Record creation date');
        $this->registerArgument('updated', DateTimeInterface::class, 'Record last-update date');
    }

    public function render(): bool
    {
        /** @var DateTimeInterface|null $created */
        $created = $this->arguments['created'];
        /** @var DateTimeInterface|null $updated */
        $updated = $this->arguments['updated'];

        return $created !== null && $updated !== null
            && $updated->getTimestamp() > $created->getTimestamp();
    }
}
