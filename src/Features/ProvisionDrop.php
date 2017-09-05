<?php namespace SuperV\Agents\Nginx\Features;

use SuperV\Modules\Hosting\Domains\Package\Model\PackageModel;
use SuperV\Modules\Hosting\Domains\Package\Model\PartModel;
use SuperV\Modules\Hosting\Domains\Services\Web\Model\WebServiceModel;
use SuperV\Modules\Supreme\Domains\Server\Jobs\MakeStub;
use SuperV\Platform\Domains\Droplet\Agent\AgentFeature;

class ProvisionDrop extends AgentFeature
{
    protected $jobs = [];

    public function handle()
    {
        /** @var PackageModel $package */
        $package = $this->drop->getRelated()->getPackage();


        $hostname = $package->getDomain();
        $username = $package->getUsername();
        $homeDir = '/home/'.$username;
        $webDir = $homeDir.'/public_html';
        $logDir = $homeDir.'/_log';
        $sessionDir = $homeDir.'/_session';
        $webserverGroup = 'www-data';
        $nginxSitesAvailable = '/etc/nginx/sites-available';
        $nginxSitesEnabled = '/etc/nginx/sites-enabled';
        $fpmPool = '/etc/php/7.0/fpm/pool.d';

        $nginxConfigFile = $nginxSitesAvailable.'/'.$hostname.'.conf';

        if ($this->server->fileExists($nginxConfigFile)) {
            throw new \Exception('Nginx conf already exists');
        }

        $this->server->addUser($username, 'secret123');

        $this->server->addToGroup($username, $webserverGroup);

        // set home directory permissions
        $this->server->chmod($homeDir, 'g+rx');

        // parse nginx vhost config + chmod permission
        $params = [
            'hostname' => $hostname,
            'username' => $username,
            'web_dir'  => $webDir,
            'log_dir'  => $logDir,
            'socket'   => "/var/run/{$username}_fpm.sock",
        ];
        $nginxConfig = $this->dispatch(new MakeStub('superv.agents.nginx::vhost', $params));
        $this->server->saveToFile($nginxConfig, $nginxConfigFile);

        // parse fpm config
        $params = [
            'username'      => $username,
            'fpm_servers'   => 1,
            'max_servers'   => 2,
            'min_servers'   => 1,
            'start_servers' => 1,
            'max_childs'    => 3,
            'memory_limit'  => '512M',
            'home_dir'      => $homeDir,
            'session_dir'   => $sessionDir,
        ];
        $fpmConfig = $this->dispatch(new MakeStub('superv.agents.nginx::fpm_pool', $params));
        $fpmConfigFile = $fpmPool.'/'.$hostname.'.pool.conf';
        $this->server->saveToFile($fpmConfig, $fpmConfigFile);

        // enable nginx site
        $this->server->symlink($nginxConfigFile, $nginxSitesEnabled.'/'.$hostname.'.conf');

        // create required dirs and set permissions
        $this->server->mkdirR($webDir, 750);
        $this->server->chmodR($homeDir, 750);
        $this->server->mkdir($logDir, 770);
        $this->server->mkdir($sessionDir, 700);

        // geneate sample index.php
        $this->server->saveToFile("<?php echo 'welcome to site: {$hostname}'; \r\n phpinfo(); ", $webDir.'/index.php');

        // chown homedir
        $this->server->chownR($homeDir, $username);

        // reload nginx and restart fpm
        $this->server->execute('service nginx reload');
        $this->server->execute('service php7.0-fpm restart');

        return $this->jobs;
    }
}