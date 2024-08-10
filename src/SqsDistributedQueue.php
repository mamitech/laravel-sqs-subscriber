<?php
namespace Mamitech\LaravelSqsSubscriber;

use Illuminate\Queue\SqsQueue;

class SqsDistributedQueue extends SqsQueue
{
    private $messageBuffer = [];

    private $messagesToBeDeleted = [];

    public function pop($queue = null)
    {
        $maxReceiveMessage = $this->getMaxReceiveMessage();
        $maxWaitTime = $this->getMaxWaitTime();

        if (empty($this->messageBuffer)) {
            $response = $this->sqs->receiveMessage([
                'QueueUrl' => $queue = $this->getQueue($queue),
                'AttributeNames' => ['ApproximateReceiveCount'],
                'MaxNumberOfMessages' => $maxReceiveMessage,
                'WaitTimeSeconds' => $maxWaitTime,
            ]);
            $this->logSqsResponse($response);

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

    public function getMaxReceiveMessage()
    {
        $maxReceiveMessage = 1;
        if (! $this->isUseTopic()) {
            // only allow batch operation when message not using topic format (for now, it is internal-tracker)
            $maxReceiveMessage = config('queue.connections.sqs-distributed.max_receive_message', 1);
        }
        return $maxReceiveMessage;
    }

    public function isUseTopic()
    {
        return config('queue.connections.sqs-distributed.use_topic', true);
    }

    public function getMaxWaitTime()
    {
        $maxWaitTime = 0;
        if ($this->getMaxReceiveMessage() > 1) {
            $maxWaitTime = 20;
        }
        return $maxWaitTime;
    }

    public function pushMessageToBeDeleted($messageReceiptHandler)
    {
        $this->messagesToBeDeleted[] = $messageReceiptHandler;
    }

    public function deleteMessages($queue = null)
    {
        if (count($this->messagesToBeDeleted) > 0) {
            $this->sqs->deleteMessageBatch([
                'QueueUrl' => $this->getQueue($queue),
                'Entries' => array_map(function ($key, $value) {
                    return [
                        'Id' => $key,
                        'ReceiptHandle' => $value,
                    ];
                }, array_keys($this->messagesToBeDeleted), array_values($this->messagesToBeDeleted)),
            ]);
            $this->messagesToBeDeleted = [];
        }
    }

    private function logSqsResponse($response)
    {
        if ($this->getMaxReceiveMessage() > 1) {
            // only log response from SQS if it is internal-tracker
            if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
                info(sprintf('Received %d messages from the queue.', count($response['Messages'])));
            } else {
                info('No messages in the queue.');
            }
        }
    }
}
