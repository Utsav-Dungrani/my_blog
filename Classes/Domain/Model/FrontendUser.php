<?php

namespace NitsanAi\MyBlog\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class FrontendUser extends AbstractEntity
{
    protected string $username = '';

    protected string $name = '';

    protected string $email = '';

    protected string $password = '';

    protected string $usergroup = '1';
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getUsergroup(): string
    {
        return $this->usergroup;
    }

    public function setUsergroup(string $usergroup): void
    {
        $this->usergroup = $usergroup;
    }
}
