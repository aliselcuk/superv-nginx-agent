<?php

namespace SuperV\Agents\Nginx\Domains\Site;

class SiteConfig
{
    /** @var  SiteUser */
    protected $user;

    protected $hostname;

    protected $webroot;

    protected $public;

    protected $logsDir;

    protected $sessionDir;

    public function __construct(SiteUser $user, $hostname)
    {
        $this->hostname = $hostname;
        $this->user = $user;
    }

    /**
     * @return SiteUser
     */
    public function user(): SiteUser
    {
        return $this->user;
    }

    /**
     * @param SiteUser $user
     *
     * @return SiteConfig
     */
    public function setUser(SiteUser $user): SiteConfig
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function hostname()
    {
        return $this->hostname;
    }

    /**
     * Site Hostname
     * @param string $hostname
     *
     * @return SiteConfig
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * Public directory
     *
     * @return mixed
     */
    public function public()
    {
        return $this->public;
    }

    /**
     * @param mixed $public
     *
     * @return SiteConfig
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * @param mixed $webroot
     *
     * @return SiteConfig
     */
    public function setWebroot($webroot)
    {
        $this->webroot = $webroot;

        return $this;
}

    /**
     * @return mixed
     */
    public function webroot()
    {
        return $this->webroot;
    }

    /**
     * @param mixed $logsDir
     *
     * @return SiteConfig
     */
    public function setLogsDir($logsDir)
    {
        $this->logsDir = $logsDir;

        return $this;
}

    /**
     * @return mixed
     */
    public function logsDir()
    {
        return $this->logsDir;
    }

    /**
     * @param mixed $sessionDir
     *
     * @return SiteConfig
     */
    public function setSessionDir($sessionDir)
    {
        $this->sessionDir = $sessionDir;

        return $this;
}

    /**
     * @return mixed
     */
    public function sessionDir()
    {
        return $this->sessionDir;
    }
}