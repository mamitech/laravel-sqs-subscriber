<?php

return [
    env('AWS_SQS_DISTRIBUTED_DEFAULT_QUEUE', 'test-local') => [
        'log-message' => Mamitech\LaravelSqsSubscriber\LogMessageListener::class,
    ]
];
