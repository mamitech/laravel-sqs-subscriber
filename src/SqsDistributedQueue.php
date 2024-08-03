<?php
namespace Mamitech\LaravelSqsSubscriber;

use Illuminate\Queue\SqsQueue;

class SqsDistributedQueue extends SqsQueue
{
    private $messageBuffer = [];

    public function pop($queue = null)
    {
        $maxReceiveMessage = 1;
        if (! config('queue.connections.sqs-distributed.use_topic', true)) {
            // only allow batch operation when message not using topic format
            $maxReceiveMessage = config('queue.connections.sqs-distributed.max_receive_message', 1);
        }

        if (empty($this->messageBuffer)) {
            $response = $this->sqs->receiveMessage([
                'QueueUrl' => $queue = $this->getQueue($queue),
                'AttributeNames' => ['ApproximateReceiveCount'],
                'MaxNumberOfMessages' => $maxReceiveMessage,
            ]);

            if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
                if (count($response['Messages']) === 1) {
                    return new SqsDistributedJob(
                        $this->container, $this->sqs, $response['Messages'][0],
                        $this->connectionName, $queue
                    );
                } else {
                    $this->messageBuffer = $response['Messages'];
                }
            }
        }
        if (! empty($this->messageBuffer)) {
            return new SqsDistributedJob(
                $this->container, $this->sqs, array_shift($this->messageBuffer),
                $this->connectionName, $queue
            );
        }
    }
}
