<?php

namespace NitsanAi\MyBlog\Domain\Repository;

use NitsanAi\MyBlog\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Persistence\Repository;

class FrontendUserRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();
        $querySettings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function findOneByUsername(string $username): ?FrontendUser
    {
        $query = $this->createQuery();
        $query->matching($query->equals('username', $username));
        /** @var FrontendUser|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }

    public function findOneByEmail(string $email): ?FrontendUser
    {
        $query = $this->createQuery();
        $query->matching($query->equals('email', $email));
        /** @var FrontendUser|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }
}
