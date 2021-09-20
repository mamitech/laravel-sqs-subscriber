<?php
namespace Mamitech\LaravelSqsSubscriber;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;

class SqsDistributedConnector extends SqsConnector implements ConnectorInterface {

    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return new SqsDistributedQueue(
            new SqsClient($config), $config['queue']
        );
    }
}