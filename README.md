## Mail Manager

Framework agnostic library to queue and send emails from multiple services using a consistent API.

**NOTE:** Development is currently underway to integrate additional mail services (adapters) to Mail Manager, 
 and these will be released as they are developed.

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](LICENSE).

## Author

<img src="https://cdn1.onbayfront.com/bfm/brand/bfm-logo.svg" alt="Bayfront Media" width="250" />

- [Bayfront Media homepage](https://www.bayfrontmedia.com?utm_source=github&amp;utm_medium=direct)
- [Bayfront Media GitHub](https://github.com/bayfrontmedia)

## Requirements

* PHP `^8.0`
* `PDO` PHP extension

## Installation

```
composer require bayfrontmedia/mail-manager
```

## Usage

**NOTE:** All exceptions thrown by Mail Manager extend `Bayfront\MailManager\Exceptions\MailException`, so you can choose to catch exceptions as narrowly or broadly as you like.

### Adapter

A `Bayfront\MailManager\AdapterInterface` must be passed to both the `Bayfront\MailManager\Mail`, and the `Bayfront\MailManager\MailQueue` constructors.
There are a variety of adapters available, each with their own required configuration.

In addition, you may also create and use your own adapters to be used with Mail Manager.

All adapters have a `getInstance()` method, which can be used to get the underlying instance used by the adapter.

**PHPMailer**

The PHPMailer adapter allows you to use [PHPMailer](https://github.com/PHPMailer/PHPMailer) for sending messages.

```
use Bayfront\MailManager\Adapters\PHPMailer;

$config = [
    'smtp' => true,
    'smtp_auth' => true,
    'smtp_secure' => 'tls', // STARTTLS or SMTPS
    'host' => 'mail.example.com',
    'port' => 587,
    'username' => 'your_name@example.com',
    'password' => 'your_password',
    'debug' => false
];

try {

    $adapter = new PHPMailer($config);

} catch (AdapterException $e) {
    die($e->getMessage());
}
```

The PHPMailer adapter also has a `testConnection()` method you can use to test for a successful connection to the SMTP server.

### Start using Mail Manager

You may choose one of the following classes to use:

The `Bayfront\MailManager\Mail` class allows for the creation and immediate sending of messages.
No database is needed.

The `Bayfront\MailManager\MailQueue` class is the same as above, only it requires a `PDO` instance to work with queued messages.
Queued messages allow for messages to be sent programmatically at a later date.

**Mail default configuration**

```
use Bayfront\MailManager\Mail;

$mail = new Mail($adapter);
```

**MailQueue default configuration**

```
use Bayfront\MailManager\MailQueue;
use Bayfront\MailManager\Exceptions\QueueException;

$pdo = new PDO('mysql:host=example.com;dbname=database_name', 'username', 'password');

$queue_config = [
    'table' => 'mail_queue', // Name of database table to use
    'max_attempts' => 3 // Maximum number of failed sending attempts before deleting message
];

try {

    $queue = new MailQueue($adapter, $pdo, $queue_config);

} catch (QueueException $e) {
    die($e->getMessage());
}
```

### Public methods

- [create](#create)
- [addAddress](#addaddress)
- [addCc](#addcc)
- [addBcc](#addbcc)
- [addAttachment](#addattachment)
- [discard](#discard)
- [send](#send)

**MailQueue only**

- [addQueue](#addqueue)
- [removeQueue](#removequeue)
- [getQueue](#getqueue)
- [sendQueue](#sendqueue)

<hr />

### create

**Description:**

Create a new message.

**Parameters:**

- `$message` (array)

**Returns:**

- (self)

**Throws**

- `Bayfront\MailManager\Exceptions\MessageException`

**Example:**

```
$message = [
    'to' => [ // Array of recipients
        [
            'address' => 'to@example.com'.
            'name' => 'Recipient name' // Optional
        ]
    ],
    'from' => [
        'address' => 'you@example.com'
        'name' => 'Your name' // Optional
    ],
    'subject' => 'Message subject',
    'body' => 'Message body'
];

try {

    $mail->create($message);

} catch (MessageException $e) {
    die($e->getMessage());
}
```

<hr />

### addAddress

**Description:**

Add a "To" recipient.

**Parameters:**

- `$address` (string)
- `$name = NULL` (string|null): If `NULL`, a name will not be defined

**Returns:**

- (self)

**Example:**

```
$mail->addAddress('jane.doe@example.com', 'Jane Doe');
```

<hr />

### addCc

**Description:**

Add a "Cc" recipient.

**Parameters:**

- `$address` (string)
- `$name = NULL` (string|null): If `NULL`, a name will not be defined

**Returns:**

- (self)

**Example:**

```
$mail->addCC('jane.doe@example.com', 'Jane Doe');
```

<hr />

### addBcc

**Description:**

Add a "Bcc" recipient.

**Parameters:**

- `$address` (string)
- `$name = NULL` (string|null): If `NULL`, a name will not be defined

**Returns:**

- (self)

**Example:**

```
$mail->addBCC('jane.doe@example.com', 'Jane Doe');
```

<hr />

### addAttachment

**Description:**

Add an attachment.

**Parameters:**

- `$file` (string): Path to the file
- `$name = NULL` (string|null): New name to assign to file. 
If `NULL`, the existing name will be used.

**Returns:**

- (self)

**Example:**

```
$mail->addAttachment('/path/to/existing/image.jpg', 'new-name.jpg');
```

<hr />

### discard

**Description:**

Discard message.

**Parameters:**

- None

**Returns:**

- (self)

**Example:**

```
$mail->discard();
```

<hr />

### send

**Description:**

Send message.

**Parameters:**

- None

**Returns:**

- (void)

**Throws:**

- `Bayfront\MailManager\MessageException`

**Example:**

```
$mail->send();
```

<hr />

### addQueue

**NOTE:** This method is only available with the `MailQueue` class.

**Description:**

Queue message.

**Parameters:**

- `$date_due` (`\DateTimeInterface`)
- `$priority = 5` (int): Messages will be sent in order by due date sorted by priority in descending order

**Returns:**

- (void)

**Throws:**

- `Bayfront\MailManager\QueueException`

**Example:**

```
$message = [
    'to' => [ // Array of recipients
        [
            'address' => 'to@example.com'.
            'name' => 'Recipient name' // Optional
        ]
    ],
    'from' => [
        'address' => 'you@example.com'
        'name' => 'Your name' // Optional
    ],
    'subject' => 'Message subject',
    'body' => 'Message body'
];

try {

    $mail->create($message);

} catch (MessageException $e) {
    die($e->getMessage());
}

try {

    $mail->addQueue(new \Datetime('2020-10-01 10:35:00'));

} catch (QueueException $e) {
    die($e->getMessage());
}
```

<hr />

### removeQueue

**NOTE:** This method is only available with the `MailQueue` class.

**Description:**

Remove a given ID from the queue.

**Parameters:**

- `$id` (int): Unique `id` from the database table

**Returns:**

- (bool)

**Throws:**

- `Bayfront\MailManager\QueueException`

**Example:**

```
$mail->removeQueue(35);
```

<hr />

### getQueue

**NOTE:** This method is only available with the `MailQueue` class.

**Description:**

Get all messages in queue that are due, up to a given limit.

**Parameters:**

- `$limit = 0` (int): `0` to get all

**Returns:**

- (array)

**Throws:**

- `Bayfront\MailManager\QueueException`

**Example:**

```
try {

    print_r($mail->getQueue());

} catch (QueueException $e) {
    die($e->getMessage());
}
```

<hr />

### sendQueue

**NOTE:** This method is only available with the `MailQueue` class.

**Description:**

Send messages in queue that are due, up to a given limit.

**Parameters:**

- `$limit = 0` (int): `0` to send all

**Returns:**

- (array)

**Throws:**

- `Bayfront\MailManager\QueueException`

**Example:**

```
try {

    $results = $mail->sendQueue(50); // Send up to 50 queued messages

} catch (QueueException $e) {
    die($e->getMessage());
}
```

The returned array contains a result summary with the following structure:

```
[
    'sent' => 0, // Number of messages sent
    'removed' => 0, // Number of messages removed (exceeded max attempts)
    'failed' => 0, // Number of messages failed
    'failed_ids' => [] // Unique ID's of failed messages
];
```