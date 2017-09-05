<?php namespace SuperV\Agents\Nginx;

use SuperV\Agents\Nginx\Features\InstallNginx;
use SuperV\Agents\Nginx\Features\ProvisionDrop;
use SuperV\Platform\Domains\Droplet\Agent\Agent;

class NginxAgent extends Agent
{
    protected $features = [
        'install' => InstallNginx::class,
        'provision' => ProvisionDrop::class
    ];
}