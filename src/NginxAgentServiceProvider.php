<?php namespace SuperV\Agents\Nginx;

use SuperV\Agents\Nginx\Listener\WebServiceListener;
use SuperV\Platform\Domains\Droplet\DropletServiceProvider;

class NginxAgentServiceProvider extends DropletServiceProvider
{
    protected $listeners = [
        'superv::web.*'   => WebServiceListener::class,
    ];
}