<?php

/**
 * PHEM - A PHP Email Management library for handling SMTP, IMAP, and POP3 operations.
 * @author Sakibur Rahman (@sakibweb)
 *
 * This class allows sending and receiving emails through SMTP, IMAP, and POP3 protocols.
 * It supports secure connections, authentication, and provides logging of email transactions.
 */
define("NL", "\r\n");
class PHEM {
    // Class properties for SMTP, IMAP, POP3 configurations and credentials.
    private static $smtpHost;
    private static $smtpPort;
    private static $imapHost;
    private static $imapPort;
    private static $popHost;
    private static $popPort;
    private static $smtpSecure;
    private static $imapSecure;
    private static $popSecure;
    private static $smtpUsername;
    private static $smtpPassword;
    private static $imapUsername;
    private static $imapPassword;
    private static $popUsername;
    private static $popPassword;
    private static $socket;
    private static $local;
    private static $log = array();
    private static $smtpServer;
    private static $imapServer;
    private static $popServer;

    /**
     * Configure SMTP settings.
     *
     * @param string $smtpHost SMTP server hostname.
     * @param int $smtpPort SMTP server port.
     * @param string $smtpSecure Security protocol ('tls' or 'ssl').
     */
    public static function smtp($smtpHost, $smtpPort, $smtpSecure) {
        self::$smtpHost = $smtpHost;
        self::$smtpPort = $smtpPort;
        self::$smtpSecure = strtolower($smtpSecure);

        self::$smtpServer = self::$smtpHost;
        if (self::$smtpSecure == 'tls') self::$smtpServer = 'tcp://' . self::$smtpHost;
        if (self::$smtpSecure == 'ssl') self::$smtpServer = 'ssl://' . self::$smtpHost;

        self::$local = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (!empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR']);
    }

    /**
     * Configure IMAP settings.
     *
     * @param string $imapHost IMAP server hostname.
     * @param int $imapPort IMAP server port.
     * @param string $imapSecure Security protocol (e.g., '/ssl', '/tls').
     */
    public static function imap($imapHost, $imapPort, $imapSecure) {
        self::$imapHost = $imapHost;
        self::$imapPort = $imapPort;
        self::$imapSecure = $imapSecure;

        self::$imapServer = '{'.self::$imapHost.':'.self::$imapPort.self::$imapSecure.'}INBOX';
    }

    /**
     * Configure POP3 settings.
     *
     * @param string $popHost POP3 server hostname.
     * @param int $popPort POP3 server port.
     * @param string $popSecure Security protocol (e.g., '/ssl', '/tls').
     */
    public static function pop($popHost, $popPort, $popSecure) {
        self::$popHost = $popHost;
        self::$popPort = $popPort;
        self::$popSecure = $popSecure;

        self::$popServer = '{'.self::$popHost.':'.self::$popPort.self::$popSecure.'}INBOX';
    }

    /**
     * Set SMTP login credentials.
     *
     * @param string $username SMTP username.
     * @param string $password SMTP password.
     */
    public static function smtpLogin($username, $password) {
        self::$smtpUsername = $username;
        self::$smtpPassword = $password;
    }

    /**
     * Set IMAP login credentials.
     *
     * @param string $username IMAP username.
     * @param string $password IMAP password.
     */
    public static function imapLogin($username, $password) {
        self::$imapUsername = $username;
        self::$imapPassword = $password;
    }

    /**
     * Set POP3 login credentials.
     *
     * @param string $username POP3 username.
     * @param string $password POP3 password.
     */
    public static function popLogin($username, $password) {
        self::$popUsername = $username;
        self::$popPassword = $password;
    }

    /**
     * Retrieve emails using SMTP settings (alias for IMAP get).
     *
     * @param string $filter Search filter criteria.
     * @param int $limit Number of emails to retrieve.
     * @return array Retrieved emails.
     */
    public static function smtpGet($filter, $limit) {
        return self::imapGet($filter, $limit);
    }

    /**
     * Send an email using IMAP settings (alias for SMTP send).
     *
     * @param string $from Sender's email address.
     * @param string $name Sender's name.
     * @param string $to Recipient's email address.
     * @param string $cc CC email addresses.
     * @param string $bcc BCC email addresses.
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return bool True on success, false on failure.
     */
    public static function imapSend($from, $name, $to, $cc, $bcc, $subject, $message) {
        return self::smtpSend($from, $name, $to, $cc, $bcc, $subject, $message);
    }

    /**
     * Send an email using POP3 settings (alias for SMTP send).
     *
     * @param string $from Sender's email address.
     * @param string $name Sender's name.
     * @param string $to Recipient's email address.
     * @param string $cc CC email addresses.
     * @param string $bcc BCC email addresses.
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return bool True on success, false on failure.
     */
    public static function popSend($from, $name, $to, $cc, $bcc, $subject, $message) {
        return self::smtpSend($from, $name, $to, $cc, $bcc, $subject, $message);
    }

    /**
     * Retrieve emails using IMAP settings.
     *
     * @param string $filter Search filter criteria.
     * @param int $limit Number of emails to retrieve.
     * @return array Retrieved emails.
     */
    public static function imapGet($filter, $limit) {
        $inbox = imap_open(self::$imapServer, self::$imapUsername, self::$imapPassword);
        if (!$inbox) {
            echo 'Connection error: ' . imap_last_error();
            return [];
        }

        if (strpos($filter, ":") !== false) {
            list($pre, $end) = explode(":", $filter);
            $pre = strtolower($pre);
        } else {
            $pre = strtolower($filter);
            $end = "";
        }
        $searchCriteria = self::getSearchCriteria($pre, $end);
        $emails = imap_search($inbox, $searchCriteria, SE_UID);
        if ($emails === false) {
            return [];
        }

        $emails = array_slice(array_reverse($emails), 0, $limit);
        $result = [];
        foreach ($emails as $emailUID) {
            $overview = imap_fetch_overview($inbox, $emailUID, FT_UID);
            $message = imap_fetchbody($inbox, $emailUID, 2, FT_UID);

            $result[] = [
                'subject' => $overview[0]->subject,
                'from' => $overview[0]->from,
                'date' => $overview[0]->date,
                'message' => $message
            ];
        }

        imap_close($inbox);
        return $result;
    }

    /**
     * Retrieve emails using POP3 settings.
     *
     * @param string $filter Search filter criteria.
     * @param int $limit Number of emails to retrieve.
     * @return array Retrieved emails.
     */
    private static function popGet($filter, $limit) {
        $inbox = @imap_open(self::$popServer, self::$popUsername, self::$popPassword);
        if (!$inbox) {
            echo 'Connection error: ' . imap_last_error();
            return [];
        }

        if (strpos($filter, ":") !== false) {
            list($pre, $end) = explode(":", $filter);
            $pre = strtolower($pre);
        } else {
            $pre = strtolower($filter);
            $end = "";
        }
        $searchCriteria = self::getSearchCriteria($pre, $end);
        $emails = imap_search($inbox, $searchCriteria, SE_UID);
        if ($emails === false) {
            imap_close($inbox);
            return [];
        }

        $emails = array_slice(array_reverse($emails), 0, $limit);
        $result = [];
        foreach ($emails as $emailUID) {
            $overview = imap_fetch_overview($inbox, $emailUID, FT_UID);
            $message = imap_fetchbody($inbox, $emailUID, 2, FT_UID);

            $result[] = [
                'subject' => $overview[0]->subject,
                'from' => $overview[0]->from,
                'date' => $overview[0]->date,
                'message' => $message
            ];
        }

        imap_close($inbox);
        return $result;
    }

    /**
     * Generate search criteria based on the filter key and value.
     *
     * @param string $key Filter key.
     * @param string $value Filter value.
     * @return string IMAP search criteria.
     */
    protected static function getSearchCriteria($key, $value) {
        switch ($key) {
            case 'unread':
            case 'unseen':
                return 'UNSEEN';
            case 'read':
            case 'seen':
                return 'SEEN';
            case 'latest':
                return 'ALL';
            case 'important':
            case 'starred':
                return 'FLAGGED';
            case 'spam':
                return 'KEYWORD "Junk"';
            case 'snoozed':
                return 'KEYWORD "Snoozed"';
            case 'draft':
                return 'DRAFT';
            case 'trash':
            case 'deleted':
                return 'DELETED';
            case 'social':
                return 'KEYWORD "Social"';
            case 'updates':
                return 'KEYWORD "Updates"';
            case 'forums':
                return 'KEYWORD "Forums"';
            case 'promotions':
                return 'KEYWORD "Promotions"';
            case 'all':
                return 'ALL';
            case 'bcc':
                return 'BCC "' . $value . '"';
            case 'cc':
                return 'CC "' . $value . '"';
            case 'before':
                return 'BEFORE "' . $value . '"';
            case 'from':
                return 'FROM "' . $value . '"';
            case 'to':
                return 'TO "' . $value . '"';
            case 'subject':
                return 'SUBJECT "' . $value . '"';
            case 'body':
                return 'BODY "' . $value . '"';
            case 'text':
                return 'TEXT "' . $value . '"';
            case 'on':
                return 'ON "' . $value . '"';
            case 'since':
                return 'SINCE "' . $value . '"';
            case 'unkeyword':
                return 'UNKEYWORD "' . $value . '"';
            case 'answered':
                return 'ANSWERED';
            case 'unanswered':
                return 'UNANSWERED';
            case 'deleted':
                return 'DELETED';
            case 'undeleted':
                return 'UNDELETED';
            case 'flagged':
                return 'FLAGGED';
            case 'unflagged':
                return 'UNFLAGGED';
            case 'new':
                return 'NEW';
            case 'old':
                return 'OLD';
            case 'recent':
                return 'RECENT';
            default:
                return 'ALL';
        }
    }

    /**
     * Send an email using SMTP settings.
     *
     * @param string $from Sender's email address.
     * @param string $name Sender's name.
     * @param string $to Recipient's email address.
     * @param string $cc CC email addresses.
     * @param string $bcc BCC email addresses.
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return bool True on success, false on failure.
     */
    public static function smtpSend($from, $name, $to, $cc, $bcc, $subject, $message) {
        $headers = self::prepareHeaders($from, $name, $to, $cc, $bcc, $subject, $message);
        $user64 = base64_encode(self::$smtpUsername);
        $pass64 = base64_encode(self::$smtpPassword);
        $mailfrom = '<' . $from . '>';
        $mailto = '<' . $to . '>';

        self::$socket = fsockopen(self::$smtpServer, self::$smtpPort, $errno, $errstr, 30);
        if (!self::$socket) exit('Socket connection error: ' . self::$smtpServer);
        self::$log[] = 'CONNECTION: fsockopen(' . self::$smtpServer . ')';
        self::response('220');
        self::logreq('EHLO ' . self::$local, '250');

        if (self::$smtpSecure == 'tls') {
            self::logreq('STARTTLS', '220');
            stream_socket_enable_crypto(self::$socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            self::logreq('EHLO ' . self::$local, '250');
        }

        self::logreq('AUTH LOGIN', '334');
        self::logreq($user64, '334');
        self::logreq($pass64, '235');

        self::logreq('MAIL FROM: ' . $mailfrom, '250');
        self::logreq('RCPT TO: ' . $mailto, '250');

        self::logreq('DATA', '354');
        self::$log[] = htmlspecialchars($headers);
        self::request($headers, '250');

        self::logreq('QUIT', '221');
        fclose(self::$socket);

        return true;
    }

    /**
     * Prepare email headers for sending.
     *
     * @param string $from Sender's email address.
     * @param string $name Sender's name.
     * @param string $to Recipient's email address.
     * @param string $cc CC email addresses.
     * @param string $bcc BCC email addresses.
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return string Formatted email headers.
     */
    private static function prepareHeaders($from, $name, $to, $cc, $bcc, $subject, $message) {
        $headers = array();
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'To: ' . self::formatAddress($to);
        $headers[] = 'From: ' . self::formatAddress(array($from, $name));
        if (!empty($cc)) $headers[] = 'Cc: ' . self::formatAddress($cc);
        if (!empty($bcc)) $headers[] = 'Bcc: ' . self::formatAddress($bcc);
        $headers[] = 'Subject: ' . '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'Message-ID: ' . self::generateMessageID();
        $headers[] = 'X-Mailer: ' . 'PHP/' . phpversion();
        $headers[] = 'MIME-Version: ' . '1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = '';
        $headers[] = $message;
        $headers[] = '.';

        return implode(NL, $headers);
    }

    /**
     * Log the request and check the server response.
     *
     * @param string $cmd Command to be sent to the server.
     * @param string $code Expected response code.
     */
    private static function logreq($cmd, $code) {
        self::$log[] = htmlspecialchars($cmd);
        self::request($cmd, $code);
    }

    /**
     * Send a request to the server and check the response.
     *
     * @param string $cmd Command to be sent to the server.
     * @param string $code Expected response code.
     */
    private static function request($cmd, $code) {
        fwrite(self::$socket, $cmd . NL);
        self::response($code);
    }

    /**
     * Check the server response against the expected code.
     *
     * @param string $code Expected response code.
     */
    private static function response($code) {
        stream_set_timeout(self::$socket, 8);
        $result = fread(self::$socket, 768);
        $meta = stream_get_meta_data(self::$socket);
        if ($meta['timed_out'] === true) {
            fclose(self::$socket);
            self::$log[] = '<b>Was a timeout in Server response</b>';
            self::showLog();
            print_r($meta);
            exit();
        }
        self::$log[] = $result;
        if (substr($result, 0, 3) == $code) return;
        fclose(self::$socket);
        self::$log[] = '<b>SMTP Server response Error</b>';
        self::showLog();
        exit();
    }

    /**
     * Format email addresses.
     *
     * @param mixed $address Single email address or array of address and name.
     * @return string Formatted email address.
     */
    private static function formatAddress($address) {
        return (is_array($address) && isset($address[1]) && $address[1] != '') ? '"' . $address[1] . '" <' . $address[0] . '>' : $address[0];
    }

    /**
     * Generate a unique Message-ID.
     *
     * @return string Generated Message-ID.
     */
    private static function generateMessageID() {
        return sprintf(
            "<%s.%s@%s>",
            base_convert(microtime(), 10, 36),
            base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36),
            self::$local
        );
    }

    /**
     * Display the SMTP log.
     */
    public static function showLog() {
        echo '<pre>';
        echo '<b>SMTP Mail Transaction Log</b><br>';
        print_r(self::$log);
    }
}

?>
