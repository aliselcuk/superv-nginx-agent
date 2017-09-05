<?php

namespace SuperV\Agents\Nginx\Jobs;

use Illuminate\Config\Repository;
use SuperV\Agents\Nginx\Domains\Site\SiteConfig;
use SuperV\Modules\Supreme\Domains\Server\Server;
use SuperV\Platform\Domains\Task\Job;

class MakeNginxSite extends Job
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var SiteConfig
     */
    private $siteConfig;

    public function __construct(Server $server, SiteConfig $siteConfig)
    {
        $this->server = $server;
        $this->siteConfig = $siteConfig;
    }

    public function handle(Repository $config)
    {
        $site = $this->siteConfig;
        $user = $this->siteConfig->user();

        $nginxConfig = $config->get('superv.agents.nginx::nginx');
        $nginxConfigFile = $nginxConfig['sites_available'].'/'.$site->hostname().'.conf';

        $fpmConfig = $config->get('superv.agents.nginx::fpm');
        $fpmConfigFile = $fpmConfig['pool_dir'].'/'.$site->hostname().'.pool.conf';

        if ($this->server->fileExists($nginxConfigFile)) {
            throw new \Exception('Nginx conf already exists');
        }

        if (! $user->exists()) {
            $this->server->addUser($user->username(), $user->password(), $user->home());
        }

        $this->server->addToGroup($user->username(), $nginxConfig['group']);

        // set home directory permissions
        $this->server->chmod($user->home(), 'g+rx');

        $tokens = [
            'hostname' => $site->hostname(),
            'username' => $user->username(),
            'web_dir'  => $site->webroot(),
            'log_dir'  => $site->logsDir(),
            'socket'   => "/var/run/{$user->username()}_fpm.sock",
        ];
        $this->server->saveStubToFile('superv.agents.nginx::vhost', $nginxConfigFile, $tokens);

        // enable nginx site
        $this->server->symlink($nginxConfigFile, $nginxConfig['sites_enabled'].'/'.$site->hostname().'.conf');

        $this->server->mkdirR($site->webroot(), 750);
        $this->server->chmodR($user->home(), 750);
        $this->server->mkdir($site->logsDir(), 770);
        $this->server->mkdir($site->sessionDir(), 700);

        // geneate sample index.php
        $this->server->saveToFile("<?php echo 'welcome to site: {$site->hostname()}'; \r\n phpinfo(); ", $site->webroot().'/index.php');

        $this->server->chownR($user->home(), $user->username());

        //
        // FPM
        //
        $tokens = [
            'username'      => $user->username(),
            'fpm_servers'   => 1,
            'max_servers'   => 2,
            'min_servers'   => 1,
            'start_servers' => 1,
            'max_childs'    => 3,
            'memory_limit'  => '512M',
            'home_dir'      => $user->home(),
            'session_dir'   => $site->sessionDir(),
        ];
        $this->server->saveStubToFile('superv.agents.nginx::fpm_pool', $fpmConfigFile, $tokens);

        $this->server->execute('service nginx reload');
        $this->server->execute('service php7.0-fpm restart');
    }
}