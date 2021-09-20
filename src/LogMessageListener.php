<?php
namespace Mamitech\LaravelSqsSubscriber;

use Illuminate\Support\Facades\Log;

/**
 * This class would simply receives a string and log it.
 * Please ignore this class because in most cases you wouldn't need it.
 */
class LogMessageListener
{
    public function handle($message)
    {
        Log::info($message);
    }
}