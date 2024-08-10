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
            if ($this->getMaxReceiveMessage() === 1) {
                $this->delete();
            } else {
                $this->pushMessageToBeDeleted($this->job['ReceiptHandle']);
                $this->deleted = true;
            }
        }
    }

    public function payload()
    {
        $payload = parent::payload();
        if (! is_array($payload)) {
            $payload = ['body' => $payload];
        }
        $payload['job'] = config('sqs-topic-map.default', LogMessageListener::class);
        if ($this->isUseTopic()) {
            $topic = $this->getTopic($payload);
            $payload['job'] = $this->getListener($topic);
        }

        return $payload;
    }

    protected function isUseTopic()
    {
        $queue = $this->container->make('queue')->connection($this->connectionName);
        return $queue->isUseTopic();
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

    protected function getMaxReceiveMessage()
    {
        $queue = $this->container->make('queue')->connection($this->connectionName);
        return $queue->getMaxReceiveMessage();
    }

    protected function pushMessageToBeDeleted($messageId)
    {
        $queue = $this->container->make('queue')->connection($this->connectionName);
        $queue->pushMessageToBeDeleted($messageId);
    }
}
