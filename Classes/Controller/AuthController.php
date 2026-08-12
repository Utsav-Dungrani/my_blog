<?php

namespace NitsanAi\MyBlog\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use NitsanAi\MyBlog\Domain\Model\FrontendUser;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use NitsanAi\MyBlog\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

final class AuthController extends ActionController
{
    public function __construct(private readonly FrontendUserRepository $frontendUserRepository) {}

    public function registerAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function createAccountAction(string $username = '', string $name = '', string $email = '', string $password = '', string $passwordConfirmation = ''): ResponseInterface
    {
        $username = trim($username);
        $name = trim($name);
        $email = trim($email);
        if ($username === '' || $name === '' || !filter_var($email, \FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $password !== $passwordConfirmation) {
            $this->addFlashMessage('Enter a name, a valid email, and matching passwords of at least 8 characters.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('register');
        }
        if ($this->frontendUserRepository->findOneByUsername($username) !== null || $this->frontendUserRepository->findOneByEmail($email) !== null) {
            $this->addFlashMessage('That username or email address is already registered.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('register');
        }
        $user = new FrontendUser();
        $user->setUsername($username);
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword(GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE')->getHashedPassword($password));
        $defaultUsergroup = $this->settings['defaultUsergroup'];
        $user->setUsergroup((string)$defaultUsergroup);
        $pageInformation = $this->request->getAttribute('frontend.page.information');
        if ($pageInformation !== null && method_exists($pageInformation, 'getId')) {
            $user->setPid((int)$pageInformation->getId());
        }
        $this->frontendUserRepository->add($user);
        GeneralUtility::makeInstance(PersistenceManagerInterface::class)->persistAll();
        $this->addFlashMessage('Your account has been created. Please sign in using the login form.', '', ContextualFeedbackSeverity::OK);

        $loginPageId = (int)($this->settings['loginPageId'] ?? 0);
        if ($loginPageId > 0) {
            $uri = $this->uriBuilder->reset()->setTargetPageUid($loginPageId)->build();
            if ($uri !== '') {
                return $this->redirectToUri($uri);
            }
        }

        return $this->redirect('list', 'Post');
    }
}
