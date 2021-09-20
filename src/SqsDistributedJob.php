<?php
namespace Mamitech\LaravelSqsSubscriber;

use Illuminate\Queue\Jobs\SqsJob;

class SqsDistributedJob extends SqsJob
{
    public function payload()
    {
        $payload = parent::payload();
        $topic = $payload['topic'];
        $payload['job'] = config("sqs-topic-map.$topic");

        return $payload;
    }

    public function getTopic()
    {
        $payload = $this->payload();
        $topic = null;

        # TopicARM will be used when listening messages for messages from SNS
        if (isset($payload['TopicARM'])) {
            $topic = $payload['TopicARM'];
        }

        # topic key have higher priority to be used
        if (isset($payload['topic'])) {
            $topic = $payload['topic'];
        }
        return $topic;
    }

    public function fire()
    {
        $payload = $this->payload();
        $listener = $this->getListener($this->getTopic());
        $listener->handle($payload['message']);

        if (!$this->isDeletedOrReleased()) {
            $this->delete();
        }
    }

    protected function getListener(string $topic)
    {
        $listenerClass = config("sqs-topic-map.$topic");
        if (empty($listenerClass)) {
            throw new \Exception("Listener for topic $topic is not found");
        }

        return app()->make($listenerClass);
    }
}
