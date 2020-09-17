<?php

/**
 * @package mail-manager
 * @link https://github.com/bayfrontmedia/mail-manager
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\MailManager\Adapters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\MailManager\AdapterInterface;
use Bayfront\MailManager\Exceptions\AdapterException;
use Bayfront\MailManager\Exceptions\MessageException;
use Exception;
use PHPMailer\PHPMailer\PHPMailer as Adapter;
use PHPMailer\PHPMailer\SMTP;
use Soundasleep\Html2Text;

/**
 * See: https://github.com/PHPMailer/PHPMailer
 */
class PHPMailer implements AdapterInterface
{

    protected $config;

    protected $adapter;

    /**
     * PHPMailer constructor.
     *
     * @param array $config
     *
     * @throws AdapterException
     */

    public function __construct(array $config)
    {

        if (Arr::isMissing($config, [
            'smtp',
            'host',
            'port',
            'username',
            'password'
        ])) {
            throw new AdapterException('Unable to create adapter (PHPMailer): invalid configuration');
        }

        $this->config = $config;

        $this->adapter = new Adapter(true);

    }

    /**
     * Get underlying instance used by the adapter.
     *
     * @return Adapter
     */

    public function getInstance(): Adapter
    {
        return $this->adapter;
    }

    /**
     * Test for a successful connection
     *
     * See: https://github.com/PHPMailer/PHPMailer/blob/master/examples/smtp_check.phps
     *
     * @return bool
     *
     * @throws AdapterException
     */

    public function testConnection(): bool
    {

        //Create a new SMTP instance

        $smtp = new SMTP;

        //Enable connection-level debug output

        //$smtp->do_debug = SMTP::DEBUG_CONNECTION;

        //Connect to an SMTP server

        if (!$smtp->connect($this->config['host'], $this->config['port'])) {

            $smtp->quit();
            throw new AdapterException('Connect failed (PHPMailer)');

        }

        //Say hello

        if (!$smtp->hello(gethostname())) {

            $smtp->quit();
            throw new AdapterException('EHLO failed (PHPMailer): ' . $smtp->getError()['error']);

        }

        //Get the list of ESMTP services the server offers

        $e = $smtp->getServerExtList();

        //If server can do TLS encryption, use it

        if (is_array($e) && array_key_exists('STARTTLS', $e)) {

            $tlsok = $smtp->startTLS();

            if (!$tlsok) {

                $smtp->quit();
                throw new AdapterException('Failed to start encryption (PHPMailer): ' . $smtp->getError()['error']);

            }

            //Repeat EHLO after STARTTLS

            if (!$smtp->hello(gethostname())) {

                $smtp->quit();
                throw new AdapterException('EHLO (2) failed (PHPMailer): ' . $smtp->getError()['error']);

            }

            //Get new capabilities list, which will usually now include AUTH if it didn't before

            $e = $smtp->getServerExtList();
        }

        //If server supports authentication, do it (even if no encryption)

        if (is_array($e) && array_key_exists('AUTH', $e)) {

            if ($smtp->authenticate($this->config['username'], $this->config['password'])) {

                $smtp->quit();
                return true; // Successful connection

            } else {

                $smtp->quit();
                throw new AdapterException('Authentication failed: ' . $smtp->getError()['error']);

            }

        }

        $smtp->quit();

        return false;

    }

    /**
     * Send message.
     *
     * @param array $message
     *
     * @return void
     *
     * @throws MessageException
     */

    public function send(array $message): void
    {

        try {

            // -------------------- Server settings --------------------

            if (true === $this->config['smtp']) {

                $this->adapter->isSMTP();

                if (Arr::get($this->config, 'smtp_auth', false)) {

                    $this->adapter->SMTPAuth = true;

                }

                if (isset($this->config['smtp_secure'])) {

                    // Valid values: ssl or tls

                    if (strtolower($this->config['smtp_secure']) == 'tls') {

                        $this->adapter->SMTPSecure = Adapter::ENCRYPTION_STARTTLS;

                    } else {

                        $this->adapter->SMTPSecure = Adapter::ENCRYPTION_SMTPS;

                    }

                }

            }

            $this->adapter->Host = $this->config['host'];

            $this->adapter->Port = $this->config['port'];

            $this->adapter->Username = $this->config['username'];

            $this->adapter->Password = $this->config['password'];

            if (Arr::get($this->config, 'debug', false)) {

                $this->adapter->SMTPDebug = SMTP::DEBUG_SERVER;

            }

            // -------------------- Recipients --------------------

            // From

            if (isset($message['from']['name'])) {

                $this->adapter->setFrom($message['from']['address'], $message['from']['name']);

            } else {

                $this->adapter->addAddress($message['from']['address']);

            }

            // Reply

            if (isset($message['reply']['address'])) {

                if (isset($message['reply']['name'])) {

                    $this->adapter->addReplyTo($message['reply']['address'], $message['reply']['name']);

                } else {

                    $this->adapter->addReplyTo($message['reply']['address']);

                }

            }

            // To

            foreach (Arr::get($message, 'to', []) as $recipient) {

                if (isset($recipient['address'])) {

                    if (isset($recipient['name'])) {

                        $this->adapter->addAddress($recipient['address'], $recipient['name']);

                    } else {

                        $this->adapter->addAddress($recipient['address']);

                    }
                }

            }

            // CC

            foreach (Arr::get($message, 'cc', []) as $cc) {

                if (isset($cc['address'])) {

                    if (isset($cc['name'])) {

                        $this->adapter->addCC($cc['address'], $cc['name']);

                    } else {

                        $this->adapter->addAddress($cc['address']);

                    }
                }

            }

            // BCC

            foreach (Arr::get($message, 'bcc', []) as $bcc) {

                if (isset($bcc['address'])) {

                    if (isset($bcc['name'])) {

                        $this->adapter->addBCC($bcc['address'], $bcc['name']);

                    } else {

                        $this->adapter->addAddress($bcc['address']);

                    }
                }

            }

            // -------------------- Attachments --------------------

            foreach (Arr::get($message, 'attachment', []) as $attachment) {

                if (isset($attachment['file'])) {

                    if (isset($attachment['name'])) {

                        $this->adapter->addAttachment($attachment['file'], $attachment['name']);

                    } else {

                        $this->adapter->addAttachment($attachment['file']);

                    }
                }

            }

            // -------------------- Content --------------------

            $this->adapter->Subject = $message['subject'];

            $this->adapter->Body = $message['body'];

            if (true === Arr::get($message, 'is_html', true)) { // Defaults to true

                $this->adapter->isHTML(true);

                $this->adapter->AltBody = Html2Text::convert($message['body']);

            }

            $this->adapter->send();

            // Clear all addresses and attachments for the next iteration

            $this->adapter->clearAddresses();

            $this->adapter->clearAttachments();

            return;

        } catch (Exception $e) {

            throw new MessageException('Unable to send message (PHPMailer)', 0, $e);

        }

    }

}