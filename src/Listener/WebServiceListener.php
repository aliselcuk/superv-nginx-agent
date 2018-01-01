<?php

namespace SuperV\Agents\Nginx\Listener;

use SuperV\Agents\Nginx\NginxAgent;
use SuperV\Modules\Hosting\Domains\Services\Web\Model\WebServiceModel;
use SuperV\Platform\Domains\Droplet\Agent\Agent;
use SuperV\Platform\Domains\Event\Listener;
use SuperV\Platform\Domains\Task\Jobs\DeployTask;
use SuperV\Platform\Domains\Task\TaskBuilder;

class WebServiceListener extends Listener
{
    public function created(WebServiceModel $model)
    {
        echo '........WebServiceListener.........';
        $agent = new Agent($model->getPart()->getDrop()->getAgent());

        if (! $agent instanceof NginxAgent) {
            echo 'not';

            return;
        }

        $builder = app(TaskBuilder::class);
        $task = $builder->setTitle('Provision Drop Task')->setPayload([
            'drop_id' => $model->getPart()->getDropId(),
            'feature' => $agent->getFeature('provision'),
        ])->build();

        $this->dispatchNow(new DeployTask($task));

        echo '........nginx.........';

        print_r([
            'drop_id' => $model->getPart()->getDropId(),
            'feature' => $agent->getFeature('provision'),
        ]);
    }
}

