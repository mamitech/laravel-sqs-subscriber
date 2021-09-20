<?php
namespace Mamitech\LaravelSqsSubscriber;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Queue\QueueManager;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/sqs-topic-map.php' => config_path('sqs-topic-map.php'),
        ]);
    }
    public function register()
    {
        $this->app->afterResolving(QueueManager::class, function (QueueManager $manager) {
            $manager->addConnector('sqs-distributed', function () {
                return new SqsDistributedConnector();
            });
        });
    }
}