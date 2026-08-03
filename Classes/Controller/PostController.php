<?php

namespace NitsanAi\MyBlog\Controller;

use TYPO3\CMS\Core\Context\Context;
use NitsanAi\MyBlog\Domain\Model\Post;

use Psr\Http\Message\ResponseInterface;
use NitsanAi\MyBlog\Domain\Model\Comment;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use NitsanAi\MyBlog\Domain\Model\FrontendUser;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use NitsanAi\MyBlog\Domain\Repository\PostRepository;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use NitsanAi\MyBlog\Domain\Repository\CommentRepository;
use NitsanAi\MyBlog\Domain\Repository\CategoryRepository;
use NitsanAi\MyBlog\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;

class PostController extends ActionController
{
    public function __construct(
        protected readonly PostRepository $postRepository,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly CommentRepository $commentRepository,
        protected readonly FrontendUserRepository $frontendUserRepository
    ) {}

    protected function initializeListAction(): void
    {
        if ($this->arguments->hasArgument('selectedCategory')) {
            $this->arguments->getArgument('selectedCategory')->getPropertyMappingConfiguration()->allowAllProperties();
        }
    }

    protected function initializeAddCommentAction(): void
    {
        if ($this->arguments->hasArgument('newComment')) {
            $pmc = $this->arguments->getArgument('newComment')->getPropertyMappingConfiguration();
            $pmc->allowAllProperties();
        }
    }

    protected function initializeNewAction(): void
    {
        if ($this->arguments->hasArgument('newPost')) {
            $pmc = $this->arguments->getArgument('newPost')->getPropertyMappingConfiguration();
            $pmc->skipProperties('image');
            $pmc->allowAllProperties();
            $pmc->forProperty('categories')->allowAllProperties();
            $pmc->forProperty('categories.*')->allowAllProperties();
        }
    }

    protected function initializeCreateAction(): void
    {
        if ($this->arguments->hasArgument('newPost')) {
            $pmc = $this->arguments->getArgument('newPost')->getPropertyMappingConfiguration();
            $pmc->skipProperties('image');
            $pmc->allowAllProperties();
            $pmc->forProperty('categories')->allowAllProperties();
            $pmc->forProperty('categories.*')->allowAllProperties();
        }
    }

    protected function initializeUpdateAction(): void
    {
        if ($this->arguments->hasArgument('post')) {
            $pmc = $this->arguments->getArgument('post')->getPropertyMappingConfiguration();
            $pmc->skipProperties('image');
            $pmc->allowAllProperties();
            $pmc->forProperty('categories')->allowAllProperties();
            $pmc->forProperty('categories.*')->allowAllProperties();
        }
    }

    public function listAction(?Category $selectedCategory = null, bool $myPosts = false, string $sortBy = 'newest', bool $commentedByMe = false, int $currentPage = 1): ResponseInterface
    {
        $postsPerPage = (int)($this->settings['postsPerPage'] ?? 10);
        if ($postsPerPage <= 0) {
            $postsPerPage = 10;
        }
        $configuredCategoryUids = GeneralUtility::intExplode(',', (string)($this->settings['filterCategories'] ?? ''), true);
        $categories = $configuredCategoryUids === []
            ? $this->categoryRepository->findAll()
            : $this->categoryRepository->findByUids($configuredCategoryUids);

        $allowedCategoryUids = [];
        foreach ($categories as $category) {
            $allowedCategoryUids[] = $category->getUid();
        }

        if ($selectedCategory !== null && !in_array($selectedCategory->getUid(), $allowedCategoryUids, true)) {
            $selectedCategory = null;
        }
        if ($selectedCategory === null) {
            $defaultCategoryUid = (int)($this->settings['defaultCategory'] ?? 0);
            if ($defaultCategoryUid > 0) {
                $selectedCategory = $this->categoryRepository->findByUid($defaultCategoryUid);
            }
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
        $this->view->assign('loggedInUserUid', $feUserUid);
        
        $authorFilter = null;
        if ($myPosts && $feUserUid > 0) {
            $authorFilter = $this->frontendUserRepository->findByUid($feUserUid);
        }

        $commentedPostUids = null;
        if ($commentedByMe && $feUserUid > 0) {
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('tx_myblog_domain_model_comment');
            $uids = $queryBuilder->select('post')
                ->from('tx_myblog_domain_model_comment')
                ->where(
                    $queryBuilder->expr()->eq('fe_user', $queryBuilder->createNamedParameter($feUserUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('approved', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchFirstColumn();
            
            $commentedPostUids = empty($uids) ? [-1] : array_map('intval', $uids);
        }

        // Fetch all matching posts without limit so we can sort and paginate in PHP
        if ($selectedCategory !== null) {
            $posts = $this->postRepository->findByCategory($selectedCategory, 0, $authorFilter, $sortBy, $commentedPostUids);
        } elseif ($configuredCategoryUids !== []) {
            $posts = $this->postRepository->findByCategories($categories->toArray(), 0, $authorFilter, $sortBy, $commentedPostUids);
        } else {
            $posts = $this->postRepository->findAllLimited(0, $authorFilter, $sortBy, $commentedPostUids);
        }

        $postsArray = $posts->toArray();

        if (in_array($sortBy, ['comments_desc', 'comments_asc', 'community_desc', 'public_desc'], true)) {
            usort($postsArray, function (Post $a, Post $b) use ($sortBy) {
                $aApproved = 0; $aRegistered = 0; $aGuest = 0;
                foreach ($a->getComments() as $c) {
                    if ($c->getApproved()) {
                        $aApproved++;
                        if ($c->getFeUser() !== null) { $aRegistered++; } else { $aGuest++; }
                    }
                }
                
                $bApproved = 0; $bRegistered = 0; $bGuest = 0;
                foreach ($b->getComments() as $c) {
                    if ($c->getApproved()) {
                        $bApproved++;
                        if ($c->getFeUser() !== null) { $bRegistered++; } else { $bGuest++; }
                    }
                }
                
                $valA = 0; $valB = 0;
                if ($sortBy === 'comments_desc' || $sortBy === 'comments_asc') {
                    $valA = $aApproved; $valB = $bApproved;
                } elseif ($sortBy === 'community_desc') {
                    $valA = $aRegistered; $valB = $bRegistered;
                } elseif ($sortBy === 'public_desc') {
                    $valA = $aGuest; $valB = $bGuest;
                }
                
                if ($valA === $valB) {
                    return ($b->getCrdate() ?? new \DateTime()) <=> ($a->getCrdate() ?? new \DateTime());
                }
                
                if ($sortBy === 'comments_asc') {
                    return $valA <=> $valB;
                }
                return $valB <=> $valA;
            });
        }

        $paginator = new \TYPO3\CMS\Core\Pagination\ArrayPaginator($postsArray, $currentPage, $postsPerPage);
        $pagination = new \TYPO3\CMS\Core\Pagination\SimplePagination($paginator);

        $this->view->assign('paginator', $paginator);
        $this->view->assign('pagination', $pagination);
        $this->view->assign('posts', $paginator->getPaginatedItems());

        $this->view->assign('myPostsActive', $myPosts);
        $this->view->assign('commentedByMeActive', $commentedByMe);
        $this->view->assign('currentSortBy', $sortBy);
        
        $this->view->assign('categories', $categories);
        $this->view->assign('selectedCategory', $selectedCategory);
        $this->view->assign('showCategoryFilter', (bool)($this->settings['showCategoryFilter'] ?? true));
        $this->view->assign('displayMode', ($this->settings['displayMode'] ?? 'card') === 'list' ? 'list' : 'card');
        $this->view->assign('cardColumnClass', $this->getColumnClass((int)($this->settings['cardItemsPerRow'] ?? 2)));
        $this->view->assign('listColumnClass', $this->getColumnClass((int)($this->settings['listItemsPerRow'] ?? 1)));
        return $this->htmlResponse();
    }

    private function getColumnClass(int $itemsPerRow): string
    {
        return match ($itemsPerRow) {
            2 => 'col-12 col-md-6',
            3 => 'col-12 col-md-6 col-lg-4',
            4 => 'col-12 col-md-6 col-lg-3',
            default => 'col-12',
        };
    }

    public function showAction(Post $post, int $currentPage = 1): ResponseInterface
    {
        $post->setViews($post->getViews() + 1);
        $this->postRepository->update($post);
        GeneralUtility::makeInstance(PersistenceManagerInterface::class)->persistAll();

        $comments = $post->getApprovedComments();
        usort(
            $comments,
            static fn (Comment $first, Comment $second): int => ($second->getCrdate()?->getTimestamp() ?? 0)
                <=> ($first->getCrdate()?->getTimestamp() ?? 0)
        );
        $commentPaginator = new ArrayPaginator($comments, max(1, $currentPage), 10);

        $context = GeneralUtility::makeInstance(Context::class);
        $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
        $loggedInUser = null;
        if ($feUserUid > 0) {
            $loggedInUser = $this->frontendUserRepository->findByUid($feUserUid);
        }

        $this->view->assign('post', $post);
        $this->view->assignMultiple([
            'comments' => $comments,
            'paginatedComments' => $commentPaginator->getPaginatedItems(),
            'commentPagination' => new SimplePagination($commentPaginator),
            'loggedInUser' => $loggedInUser,
        ]);
        return $this->htmlResponse();
    }

    public function newAction(?Post $newPost = null): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            $this->addFlashMessage('You must be logged in to create a post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }

        $this->view->assign('newPost', $newPost);
        $this->view->assign('categories', $this->categoryRepository->findAll());
        return $this->htmlResponse();
    }

    public function createAction(Post $newPost): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
        if (!$feUserUid) {
            $this->addFlashMessage('You must be logged in to create a post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }
        $loggedInUser = $this->frontendUserRepository->findByUid($feUserUid);
        if ($loggedInUser) {
            $newPost->setFeUser($loggedInUser);
        }

        $this->handleImageUpload($newPost);

        if ($newPost->getImage() === null) {
            $this->addFlashMessage('Please upload an image for the blog post.', '', ContextualFeedbackSeverity::ERROR);
            $this->view->assign('newPost', $newPost);
            $this->view->assign('categories', $this->categoryRepository->findAll());
            $this->view->setTemplate('New');
            return $this->htmlResponse();
        }

        if ($newPost->getCategories()->count() === 0) {
            $this->addFlashMessage('Please select at least one category.', '', ContextualFeedbackSeverity::ERROR);
            $this->view->assign('newPost', $newPost);
            $this->view->assign('categories', $this->categoryRepository->findAll());
            $this->view->setTemplate('New');
            return $this->htmlResponse();
        }

        if ($newPost->getReadingTime() <= 0) {
            $this->addFlashMessage('Please enter a valid reading time in minutes.', '', ContextualFeedbackSeverity::ERROR);
            $this->view->assign('newPost', $newPost);
            $this->view->assign('categories', $this->categoryRepository->findAll());
            $this->view->setTemplate('New');
            return $this->htmlResponse();
        }

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
        $context = GeneralUtility::makeInstance(Context::class);
        $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
        if (!$feUserUid) {
            $this->addFlashMessage('You must be logged in to edit a post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }
        if ($post->getFeUser() === null || $post->getFeUser()->getUid() !== $feUserUid) {
            $this->addFlashMessage('You are not authorized to edit this post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }
        $this->view->assign('categories', $this->categoryRepository->findAll());
        $this->view->assign('post', $post);
        return $this->htmlResponse();
    }

    public function updateAction(Post $post): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
        if (!$feUserUid) {
            $this->addFlashMessage('You must be logged in to update a post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }
        if ($post->getFeUser() === null || $post->getFeUser()->getUid() !== $feUserUid) {
            $this->addFlashMessage('You are not authorized to update this post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }
        $this->handleImageUpload($post);

        if ($post->getImage() === null) {
            $this->addFlashMessage('Please upload an image for the blog post.', '', ContextualFeedbackSeverity::ERROR);
            $this->view->assign('post', $post);
            $this->view->assign('categories', $this->categoryRepository->findAll());
            $this->view->setTemplate('Edit');
            return $this->htmlResponse();
        }

        if ($post->getCategories()->count() === 0) {
            $this->addFlashMessage('Please select at least one category.', '', ContextualFeedbackSeverity::ERROR);
            $this->view->assign('post', $post);
            $this->view->assign('categories', $this->categoryRepository->findAll());
            $this->view->setTemplate('Edit');
            return $this->htmlResponse();
        }

        if ($post->getReadingTime() <= 0) {
            $this->addFlashMessage('Please enter a valid reading time in minutes.', '', ContextualFeedbackSeverity::ERROR);
            $this->view->assign('post', $post);
            $this->view->assign('categories', $this->categoryRepository->findAll());
            $this->view->setTemplate('Edit');
            return $this->htmlResponse();
        }

        $this->postRepository->update($post);
        $this->addFlashMessage('Blog post updated successfully!');
        return $this->redirect('list');
    }

    public function deleteAction(Post $post): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
        if (!$feUserUid) {
            $this->addFlashMessage('You must be logged in to delete a post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }
        if ($post->getFeUser() === null || $post->getFeUser()->getUid() !== $feUserUid) {
            $this->addFlashMessage('You are not authorized to delete this post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }
        $this->postRepository->remove($post);
        $this->addFlashMessage('Blog post deleted successfully!');
        return $this->redirect('list');
    }

    protected function handleImageUpload(Post $post): void
    {
        $uploadedFiles = $this->request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['imageUpload'] ?? null;
        if ($uploadedFile === null && isset($uploadedFiles['tx_myblog_blogplugin']['imageUpload'])) {
            $uploadedFile = $uploadedFiles['tx_myblog_blogplugin']['imageUpload'];
        }

        if ($uploadedFile instanceof UploadedFileInterface && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            $tempPath = $uploadedFile->getStream()->getMetadata('uri');
            if (!is_string($tempPath) || !file_exists($tempPath)) {
                return;
            }

            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $storage = $storageRepository->getDefaultStorage();
            if ($storage === null) {
                return;
            }

            if (!$storage->hasFolder('user_upload')) {
                $folder = $storage->createFolder('user_upload');
            } else {
                $folder = $storage->getFolder('user_upload');
            }

            $file = $storage->addFile(
                $tempPath,
                $folder,
                $uploadedFile->getClientFilename(),
                DuplicationBehavior::RENAME
            );

            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $fileReferenceObject = $resourceFactory->createFileReferenceObject([
                'uid_local' => $file->getUid(),
                'tablenames' => 'tx_myblog_domain_model_post',
                'fieldname' => 'image',
                'pid' => $post->getPid() > 0 ? $post->getPid() : 0,
            ]);

            // Delete old image reference from DB to prevent conflicts
            if ($post->getImage() !== null && $post->getImage()->getUid() > 0) {
                $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                $queryBuilder->delete('sys_file_reference')
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($post->getImage()->getUid(), \TYPO3\CMS\Core\Database\Connection::PARAM_INT)))
                    ->executeStatement();
            }

            $extbaseFileReference = GeneralUtility::makeInstance(ExtbaseFileReference::class);
            $extbaseFileReference->setOriginalResource($fileReferenceObject);
            $post->setImage($extbaseFileReference);
        }
    }

    public function addCommentAction(Post $post, Comment $newComment): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $isLoggedIn = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        if ($isLoggedIn) {
            $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
            $loggedInUser = $this->frontendUserRepository->findByUid($feUserUid);
            if ($loggedInUser) {
                $newComment->setAuthorName($loggedInUser->getName() ?: $loggedInUser->getUsername());
                $newComment->setAuthorEmail($loggedInUser->getEmail());
                $newComment->setFeUser($loggedInUser);
            }
        }

        if (!$post->getAllowComments()) {
            $this->addFlashMessage('Comments are disabled for this blog post.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('show', null, null, ['post' => $post]);
        }


        if (empty(trim($newComment->getAuthorName())) || empty(trim($newComment->getAuthorEmail())) || empty(trim($newComment->getContent()))) {
            $this->addFlashMessage('Please fill in all comment fields.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('show', null, null, ['post' => $post]);
        }

        $newComment->setPost($post);
        if ($post->getPid() > 0) {
            $newComment->setPid($post->getPid());
        }

        $newComment->setApproved(false);
        $post->addComment($newComment);
        $this->commentRepository->add($newComment);
        $this->postRepository->update($post);

        GeneralUtility::makeInstance(PersistenceManagerInterface::class)->persistAll();

        $this->addFlashMessage('Your comment has been submitted and is awaiting moderation.');
        return $this->redirect('show', null, null, ['post' => $post]);
    }

}
