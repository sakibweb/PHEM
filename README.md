# PHEM
## PHEM - A PHP Email Management library for handling SMTP, IMAP, and POP3 operations
PHEM (PHP Email Management) is a versatile library designed to simplify email operations in your PHP projects. It provides a unified interface for interacting with SMTP, IMAP, and POP3 protocols, enabling you to send and receive emails with ease. PHEM supports secure connections, authentication, and provides logging for debugging and monitoring.

## Features

* **Unified Interface:** Interact with SMTP, IMAP, and POP3 using a consistent API.
* **Secure Connections:** Supports TLS/SSL encryption for secure email communication.
* **Authentication:** Securely authenticate with email servers using usernames and passwords.
* **Logging:** Detailed logging of email transactions for debugging and analysis.
* **Send Emails (SMTP):**  Easily send emails with support for CC and BCC.
* **Receive Emails (IMAP/POP3):** Retrieve emails with various filtering options.


## Installation

Simply include the `PHEM.php` file in your project:

```php
require_once 'PHEM.php';
```

## Usage

### 1. Configuration

Before using PHEM, you need to configure the connection settings for the desired protocol (SMTP, IMAP, or POP3).

**SMTP Configuration:**

```php
PHEM::smtp('smtp.example.com', 587, 'tls'); // Host, Port, Security
PHEM::smtpLogin('your_smtp_username', 'your_smtp_password');
```

**IMAP Configuration:**

```php
PHEM::imap('imap.example.com', 993, '/ssl'); // Host, Port, Security
PHEM::imapLogin('your_imap_username', 'your_imap_password');
```

**POP3 Configuration:**

```php
PHEM::pop('pop.example.com', 995, '/ssl'); // Host, Port, Security
PHEM::popLogin('your_pop_username', 'your_pop_password');
```

### 2. Sending Emails (SMTP)

```php
$from = 'sender@example.com';
$name = 'Sender Name';
$to = 'recipient@example.com';
$cc = 'cc@example.com'; // Optional
$bcc = 'bcc@example.com'; // Optional
$subject = 'Email Subject';
$message = 'Email body text';

if (PHEM::smtpSend($from, $name, $to, $cc, $bcc, $subject, $message)) {
    echo "Email sent successfully!";
    PHEM::showLog(); // Optional: display the SMTP transaction log.
} else {
    echo "Email sending failed.";
}
```


### 3. Receiving Emails (IMAP)

```php
$filter = 'unread'; //  See filtering options below.
$limit = 10;  // Number of emails to retrieve

$emails = PHEM::imapGet($filter, $limit);

if (!empty($emails)) {
    foreach ($emails as $email) {
        echo "Subject: " . $email['subject'] . "<br>";
        echo "From: " . $email['from'] . "<br>";
        echo "Date: " . $email['date'] . "<br>";
        echo "Message: " . $email['message'] . "<br><hr>";
    }
} else {
    echo "No emails found.";
}

```

### 3. Receiving Emails (POP3)

Though the library code has a `popGet` function, it's declared as `private`. If you need POP3 functionality, you would need to modify the code to make `popGet` public. Its usage would then be similar to `imapGet`:

```php
// Assuming popGet is made public

$filter = 'unread'; //  See filtering options below.
$limit = 10;  // Number of emails to retrieve

$emails = PHEM::popGet($filter, $limit);

// ... process emails (similar to IMAP example)
```



### Filtering Options for Receiving Emails

The `$filter` parameter in the `imapGet` and `popGet` methods accepts the following filter criteria:


* `unread`/`unseen`: Unread emails.
* `read`/`seen`: Read emails.
* `all`: All emails.
* `latest`: Most recent emails.
* `important`/`starred`: Flagged emails.
* `spam`: Emails marked as spam.
* `deleted`: Deleted emails.
* `draft`: Draft emails
* `from:{email}`: Emails from a specific address.
* `to:{email}`: Emails sent to a specific address.
* `subject:{text}`: Emails with a specific subject.
* `body:{text}`: Emails containing specific text in the body.
* `before:{date}`: Emails sent before a specific date (format: "DD-Mon-YYYY").
* `since:{date}`: Emails sent since a specific date (format: "DD-Mon-YYYY").
* `on:{date}`: Emails sent on a specific date (format: "DD-Mon-YYYY").
* And more (see `getSearchCriteria` function in `PHEM.php` for a full list).





## Logging

To view the SMTP transaction log, call the `showLog()` method after sending an email:

```php
PHEM::showLog();
```

This will output the SMTP communication details in a `<pre>` block, which can be helpful for debugging.


## Contributing

Contributions are welcome! If you find any bugs or have suggestions for improvement, please feel free to open an issue or submit a pull request.


## License

This project is licensed under the MIT License.
