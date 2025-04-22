# Dbug PHP SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

Send debug payloads from PHP to the [dbug desktop app](https://github.com/dbugapp/desktop). The `Dbug` PHP SDK allows you to easily send structured debug information from your PHP application to a local server running the dbug desktop app for interactive debugging.

## Features

- Serialize complex PHP data structures to JSON with circular reference handling.
- Send payloads to a local `dbug` desktop app instance via HTTP.
- Easily configure the endpoint to customize the server URL.
- Designed for local development and debugging.

---

## Installation

You can install the `dbug-php` SDK via Composer.

```bash
composer require dbugapp/dbug-php --dev
```

## Usage

### Basic Usage
Send debug payloads by calling the `send()` method:


```php
use DbugApp\Dbug;

Dbug::send([
    'event' => 'user.registered',
    'user' => [
        'id' => 123,
        'email' => 'user@example.com',
    ],
]);
```

This will serialize the payload and send it to the default dbug server at http://127.0.0.1:53821.

### Custom Endpoint

If you need to change the endpoint (e.g., for testing or different environments), you can use the `setEndpoint()` method to specify a custom URL:

```php
Dbug::setEndpoint("http://127.0.0.1:54000");  // Set custom port
Dbug::send([
    'event' => 'order.completed',
    'order' => [
        'id' => 98765,
        'amount' => 49.99,
    ],
]);
```

### Laravel Log Intergration
If you are using Laravel, you can easily integrate the `dbug` SDK into your logging system. You can create a custom log channel in your `config/logging.php` file:

```php
<?php

return [
    // ...

    'channels' => [
        // ...

        'dbug' => [
            'driver' => 'custom',
            'via' => App\Log\DbugLogChannel::class,
        ],
    ],

];
```

Then, create the `DbugLogChannel` class in your `app/Log` directory:

`app/Log/DbugLogChannel.php`

```php
<?php

namespace App\Log;

use DbugApp\Dbug;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class DbugLogChannel
{
    /**
     * Create the custom Monolog logger.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger('dbug');
        $logger->pushHandler(new DbugHandler());

        return $logger;
    }
}
```

Finally, create the `DbugHandler` class in the same directory:
`app/Log/DbugHandler.php`

```php
<?php

namespace App\Log;

use DbugApp\Dbug;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class DbugHandler extends AbstractProcessingHandler
{
    /**
     * Write a log record to Dbug.
     *
     * @param  array  $record
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        // Send the log data to Dbug
        Dbug::send([
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => $record['context'],
        ]);
    }

    /**
     * Constructor.
     *
     * @param  int  $level  The minimum logging level at which this handler will be triggered
     * @param  bool  $bubble  Whether the messages should bubble up the stack or not
     */
    public function __construct(int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }
}
```
