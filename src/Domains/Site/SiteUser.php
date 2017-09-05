<?php

namespace SuperV\Agents\Nginx\Domains\Site;

class SiteUser
{
    protected $username;

    protected $password;

    protected $home;

    /** @var  bool */
    protected $exists;

    public function __construct($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     *
     * @return SiteUser
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return mixed
     */
    public function password()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     *
     * @return SiteUser
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return mixed
     */
    public function home()
    {
        return $this->home;
    }

    /**
     * @param mixed $home
     *
     * @return SiteUser
     */
    public function setHome($home)
    {
        $this->home = $home;

        return $this;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * @param bool $exists
     *
     * @return SiteUser
     */
    public function setExists(bool $exists): SiteUser
    {
        $this->exists = $exists;

        return $this;
    }
}