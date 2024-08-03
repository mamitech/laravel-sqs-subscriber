<?php
namespace Mamitech\LaravelSqsSubscriber;

use Illuminate\Queue\Jobs\SqsJob;

class SqsDistributedJob extends SqsJob
{
    public function fire()
    {
        $payload = $this->payload();
        $listener = app()->make($payload['job']);
        $listener->handle($this->isUseTopic() ? $payload['message'] : $payload);

        if (! $this->isDeletedOrReleased()) {
            $this->delete();
        }
    }

    public function payload()
    {
        $payload = parent::payload();
        if (! is_array($payload)) {
            $payload = ['body' => $payload];
        }
        $payload['job'] = LogMessageListener::class;
        if ($this->isUseTopic()) {
            $topic = $this->getTopic($payload);
            $payload['job'] = $this->getListener($topic);
        }

        return $payload;
    }

    protected function isUseTopic()
    {
        return config('queue.connections.sqs-distributed.use_topic', true);
    }

    protected function getTopic(array $payload)
    {
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

    protected function getListener(string $topic)
    {
        $queue = str_replace('/', '', $this->queue);
        $listenerClass = config("sqs-topic-map.$queue.$topic");
        if (empty($listenerClass)) {
            throw new \Exception("Listener for topic $topic is not found");
        }

        return $listenerClass;
    }
}
