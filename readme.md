# Usage

Add the library into your composer' dependencies

```
composer require mamitech/laravel-sqs-subscriber
```

Then, publish the configureation:

```
php artisan vendor:publish --provider=Mamitech\LaravelSqsSubscriber\ServiceProvider
```

at this point you should see a new file named `sqs-topic-map.php` in your `config/` directory. If you
experience a weirdness that somehow don't see this new file (see [link](https://codeburst.io/if-vendor-publish-doesnt-work-laravel-ca889198f828)), 
please re-run vendor:publish without parameter:

```
php artisan vendor:publish
```

and select the number with `Provider: Mamitech\LaravelSqsSubscriber\ServiceProvider`. Now you should have `sqs-topic-map.php` in your config.

# Configuration

in `config/sqs-topic-map.php`, specify the mapping between topics to classes that will handle the message.
Your class should have a `handle` receiving one `message` parameter. `message` would contains either string
or array depending on how the string inside the queue being encoded. If it's a proper json encoded string
then it would be an array, otherwise it would be string.

## Add a new connector using `sqs-distributed` driver into your config/queue.php

Now you will need a new queue connection using the new driver called `sqs-distributed` that is provided by
this library. See example on the following section.

# Example

Suppose that you have a queue in Amazon SQS named `user-registration`. An example of the message inside the queue
looks like this:

```
{
  "topic": "user-verified",
  "message": {
    "user": {
      "email": "walker@gmail.com"
    }
  }
}
```

Here's what you need to do.

## Add new connection in your `config/queue.php`

Add the new connection inside your queue config using `sqs-distributed` as driver value:

```
<?php
# config/queue.php

return [
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        .. other connections ..

        'sqs-distributed' => [
            'driver' => 'sqs-distributed', # NOTE THIS PART
            'key' => env('AWS_ACCESS_KEY_ID', 'your-public-key'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', 'your-secret-key'),
            'prefix' => env('AWS_SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('AWS_DISTRIBUTED_DEFAULT_QUEUE', 'user-registration'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],

        ...
```

note that in the new connection you will use `sqs-distributed` as driver value. you can also
specify the default queue inside `queue` key that will be used when you don't specify particular
queue to listen to.

Then you should specify which class would handle each topic in your `config/sqs-topic-map.php`:

```
return [
    'user-verified' => `App\Worker\UserVerifiedListener`
];
```

you must have the corresponding class inside `app\Worker\UserVerifiedListener.php`

```
<?php
namespace App\Worker;

class UserVerifiedListener
{
    public function handle(string $message) # THIS METHOD MUST EXISTS
    {
        $user = $message['user'];
        $email = $user['email'];
        .. your logic here ..
    }
}
```

Everytime there is a new message in the `user-registration` queue with topic of `user-verified`,
laravel will spawn a new instance of `App\Worker\UserVerifiedListener` and call method `handle`
of it by passing a `$message` parameter.

As you can see, because `message` field inside the message also contains a valid json object, then you can
directly access array data inside it using `$message['user']`. This is because when passing the parameter
to `handle`, the library will decode the message using `json_decode`.