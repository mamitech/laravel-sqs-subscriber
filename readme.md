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

## For example

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

in your config.php:

```
return [
    'user-verified' => `App\Worker\UserVerifiedListener`
];
```

you should have a class inside `app\Worker\UserVerifiedListener.php`

```
<?php
namespace Mamitech\LaravelSqsSubscriber;

class UserVerifiedListener
{
    public function handle(string $message)
    {
        $user = $message['user'];
        $email = $user['email'];
        .. your logic here ..
    }
}
```

Everytime there is a new message in the queue with topic `user-registered`, laravel will spawn a new instance of
`App\Worker\UserRegisteredListener` and call method `handle` of it by passing a `$message` parameter.

As you can see, because `message` field inside the message also contains a valid json object, then you can
directly access array data inside it using `$message['user']`. This is because when passing the parameter
to `handle`, the library will decode the message using `json_decode`.