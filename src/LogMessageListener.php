<?php
namespace Mamitech\LaravelSqsSubscriber;

/**
 * This class would simply receives a string and log it.
 * Please ignore this class because in most cases you wouldn't need it.
 */
class LogMessageListener
{
    public function handle($message)
    {
        info($message);
    }
}
