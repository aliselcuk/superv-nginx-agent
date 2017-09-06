<?php

namespace SuperV\Agents\Nginx\Features;

use SuperV\Agents\Nginx\Domains\Site\SiteConfig;
use SuperV\Agents\Nginx\Domains\Site\SiteUser;
use SuperV\Agents\Nginx\Jobs\MakeNginxSite;
use SuperV\Modules\Supreme\Domains\Script\StubBuilder;
use SuperV\Modules\Supreme\Domains\Server\Jobs\MakeStub;
use SuperV\Modules\Supreme\Domains\Server\Jobs\SaveToFile;
use SuperV\Platform\Domains\Droplet\Agent\AgentFeature;

class InstallNginx extends AgentFeature
{
    public function handle(StubBuilder $builder)
    {
        $distro = 'jessie';

        $this->job('Add DotDeb Sources',
            $builder->wrapper('save_to_file', ['target' => '/etc/apt/sources.list.d/dotdeb.list'])
                    ->build('superv.agents.nginx::dotdeb_sources', ['distro' => $distro])
        );

        $this->job('Install GnuPG Key', 'wget -q -O - http://www.dotdeb.org/dotdeb.gpg | apt-key add -');

        $this->job('Update Apt', 'apt-get update');

        $this->job('Install Nginx', 'apt-get install -y nginx');
        $this->job('Install PHP 7.0', 'apt-get install -y php7.0-fpm');

        $this->job('Install PHP Extensions',
            'apt-get install -y php7.0-memcached php7.0-mysql php7.0-gd php7.0-mcrypt php7.0-curl php7.0-sqlite3 php7.0-mbstring php7.0-xml php7.0-zip'
        );

        $this->job('Install Git', 'apt-get install -y git');
        $this->job('Install Composer', $this->dispatch(new MakeStub('superv::installer.composer')));

        $this->server->mkdirR('/etc/supervisor/conf.d');
        $this->server->saveToFile(
            $this->dispatch(new MakeStub('superv::installer.supervisor', ['home_dir' => '/usr/local/superv/app'])),
            '/etc/supervisor/conf.d/superv.conf'
        );

        $this->job('Install Redis Server', 'apt-get install -y redis-server');
        $this->job('Start Redis Server', 'service redis-server start');

        $this->job('Get NodeJS Source', 'wget -q -O - https://deb.nodesource.com/setup_8.x | bash -');
        $this->job('Install NodeJS', 'apt-get install -y nodejs');
        $this->job('Upgrade NPM', 'npm install npm@latest -g');

        $mysqlAdminPass = base64_encode(random_bytes(16));
        $this->server->saveToFile("[client]\nuser=root\npassword={$mysqlAdminPass}", '/root/.my.cnf');

        $this->job('Install Mysql Server')
             ->stub('superv.agents.power_dns::install_mysql', ['mysql_admin_pass' => $mysqlAdminPass]);

        $mysqlAppPass = base64_encode(random_bytes(16));
//        $mysqlAppPass = 'sallama';
        $this->job('Create SuperV DB', "mysql -u root -e \"CREATE DATABASE superv_app;\"");
        $this->job('Create SuperV DB User', "mysql -u root -e \"CREATE USER 'superv_app'@'localhost' IDENTIFIED BY '{$mysqlAppPass}';\"");
        $this->job('Grant DB PRIVILEGES', "mysql -u root -e \"GRANT ALL ON superv_app.* TO 'superv_app'@'localhost'; FLUSH PRIVILEGES;\"");

        $this->server->mkdirR('/usr/local/superv');
//        $this->job('Composer Github Token', 'composer config -g github-oauth.github.com dfd37cbc260cbb29f310f329f0a37f21dcf32328');
        $this->job('Get SuperV', 'rm -Rf /usr/local/superv/app; composer create-project superv/superv /usr/local/superv/app dev-master  --no-dev');

        $this->job('Set MySQL App Pass', "php /usr/local/superv/app/artisan env:set DB_PASSWORD={$mysqlAppPass}");

        $this->job('Install SuperV', 'php /usr/local/superv/app/artisan superv:install');

        $this->job('Set ACP Hostname', "php /usr/local/superv/app/artisan env:set SUPERV_PORTS_ACP_HOSTNAME=acp.superv.io");

        // site
        $username = 'superv';
        $config = new SiteConfig(
            (new SiteUser($username))->setExists(false)->setPassword('salla')->setHome("/usr/local/superv"),
            'acp.superv.io'
        );
        $config->setWebroot("/usr/local/superv/app/public")->setLogsDir("/usr/local/superv/app/_logs");
        $this->job('Create SuperV WebSite!', new MakeNginxSite($this->server, $config));

        $this->job('Link ACP Port Public', 'cd /usr/local/superv/app; mkdir public/ports; ln -s /usr/local/superv/app/droplets/superv/ports/acp/public /usr/local/superv/app/public/ports/acp');
        $this->job('Install NPM Packages', 'cd /usr/local/superv/app; npm install --production --unsafe-perm');

        $this->job('Install Supervisor', 'apt-get install -y supervisor');
        $this->job('Start Supervisor', 'service supervisor start');


        return $this->jobs;
    }

    public function compile()
    {
        $version = '1.13.4';
        $nginxSource = "http://nginx.org/download/nginx-{$version}.tar.gz";

        $this->job('Install Build Packages', 'apt-get install -y build-essential libpcre3 libpcre3-dev zlib1g zlib1g-dev');
        $this->job('Build Nginx Dep', 'apt-get -y build-dep nginx');
        $this->job('Download Source', "rm -Rf /tmp/superv; mkdir /tmp/superv ; wget $nginxSource -P /tmp/superv ");
        $this->job('Extract Source', "tar zxf /tmp/superv/nginx-{$version}.tar.gz -C /tmp/superv");
        $this->job('Configure', "cd /tmp/superv/nginx-{$version}; ./configure --prefix=/usr/local/nginx --with-debug --with-http_ssl_module --with-http_realip_module --with-http_ssl_module --with-http_perl_module --with-http_stub_status_module");

        $this->job('Make', "cd /tmp/superv/nginx-{$version}; make");
        $this->job('Make Install', "cd /tmp/superv/nginx-{$version}; make install");
        $this->job('Create Symlink', "ln -s /usr/local/nginx/sbin/nginx /usr/bin/nginx");
        $this->job('Start Nginx', "nginx");

        return $this->jobs;
    }
}