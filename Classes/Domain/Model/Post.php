<?php

namespace NitsanAi\MyBlog\Domain\Model;

use NitsanAi\MyBlog\Domain\Model\Comment;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use NitsanAi\MyBlog\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Post extends AbstractEntity
{
    protected string $title = '';

    protected string $description = '';

    protected string $author = '';

    protected ?FrontendUser $feUser = null;

    protected ?FileReference $image = null;

    protected int $views = 0;

    protected int $readingTime = 0;

    protected bool $allowComments = true;

    protected ?\DateTime $crdate = null;

    protected ?\DateTime $tstamp = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    protected ?ObjectStorage $categories = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\NitsanAi\MyBlog\Domain\Model\Comment>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected ?ObjectStorage $comments = null;

    public function __construct()
    {
        $this->categories = new ObjectStorage();
        $this->comments = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setImage(?FileReference $image): void
    {
        $this->image = $image;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(int $views): void
    {
        $this->views = $views;
    }

    public function getCrdate(): ?\DateTime
    {
        return $this->crdate;
    }

    public function setCrdate(?\DateTime $crdate): void
    {
        $this->crdate = $crdate;
    }

    public function getTstamp(): ?\DateTime
    {
        return $this->tstamp;
    }

    public function setTstamp(?\DateTime $tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    public function setReadingTime(int $readingTime): void
    {
        $this->readingTime = $readingTime;
    }

    /**
     * Returns manual reading time if set (> 0), otherwise calculates automatically from description.
     */
    public function getReadingTime(): int
    {
        if ($this->readingTime > 0) {
            return $this->readingTime;
        }
        $cleanContent = strip_tags($this->description);
        $wordCount = str_word_count($cleanContent);
        $minutes = (int)ceil($wordCount / 200);
        return max(1, $minutes);
    }

    public function getCategories(): ObjectStorage
    {
        if ($this->categories === null) {
            $this->categories = new ObjectStorage();
        }
        return $this->categories;
    }

    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    public function addCategory(Category $category): void
    {
        $this->getCategories()->attach($category);
    }

    public function removeCategory(Category $category): void
    {
        $this->getCategories()->detach($category);
    }

    public function getComments(): ObjectStorage
    {
        if ($this->comments === null) {
            $this->comments = new ObjectStorage();
        }
        return $this->comments;
    }

    public function getApprovedComments(): array
    {
        return array_values(array_filter(
            $this->getComments()->toArray(),
            fn (Comment $comment) => $comment->getApproved()
        ));
    }

    public function setComments(ObjectStorage $comments): void
    {
        $this->comments = $comments;
    }

    public function addComment(Comment $comment): void
    {
        $this->getComments()->attach($comment);
    }

    public function removeComment(Comment $comment): void
    {
        $this->getComments()->detach($comment);
    }

    public function getAllowComments(): bool
    {
        return $this->allowComments;
    }

    public function isAllowComments(): bool
    {
        return $this->allowComments;
    }

    public function setAllowComments(bool $allowComments): void
    {
        $this->allowComments = $allowComments;
    }

    public function getFeUser(): ?FrontendUser
    {
        return $this->feUser;
    }

    public function setFeUser(?FrontendUser $feUser): void
    {
        $this->feUser = $feUser;
    }
}
