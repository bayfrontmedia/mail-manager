<?php

namespace Bayfront\MailManager;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\MailManager\Exceptions\MessageException;

class Mail
{

    protected AdapterInterface $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Is the current message missing any required keys?
     *
     * @param array $message
     * @return bool
     */
    protected function _isMessageInvalid(array $message): bool
    {

        return Arr::isMissing(Arr::dot($message), [
            'to.0.address',
            'from.address',
            'subject',
            'body'
        ]);

    }

    protected array $message = [];

    /**
     * Create a new message.
     *
     * @param array $message
     * @return self
     * @throws MessageException
     */
    public function create(array $message): self
    {


        if ($this->_isMessageInvalid($message)) {

            throw new MessageException('Unable to create message: missing required keys');

        }

        $this->message = $message;

        return $this;

    }

    /**
     * Add a "To" recipient.
     *
     * @param string $address
     * @param string|null $name (If NULL, a name will not be defined)
     * @return self
     */
    public function addAddress(string $address, ?string $name = NULL): self
    {

        if (NULL === $name) {

            $this->message['to'][] = [
                'address' => $address
            ];

        } else {

            $this->message['to'][] = [
                'address' => $address,
                'name' => $name
            ];

        }

        return $this;

    }

    /**
     * Add a "CC" recipient.
     *
     * @param string $address
     * @param string|null $name
     * @return self
     */
    public function addCC(string $address, ?string $name = NULL): self
    {

        if (NULL === $name) {

            $this->message['cc'][] = [
                'address' => $address
            ];

        } else {

            $this->message['cc'][] = [
                'address' => $address,
                'name' => $name
            ];

        }

        return $this;

    }

    /**
     * Add a "BCC" recipient.
     *
     * @param string $address
     * @param string|null $name
     * @return self
     */
    public function addBCC(string $address, ?string $name = NULL): self
    {

        if (NULL === $name) {

            $this->message['bcc'][] = [
                'address' => $address
            ];

        } else {

            $this->message['bcc'][] = [
                'address' => $address,
                'name' => $name
            ];

        }

        return $this;

    }

    /**
     * Add an attachment.
     *
     * @param string $file
     * @param string|null $name (New name to assign to file. If NULL, the existing name will be used.)
     * @return self
     */
    public function addAttachment(string $file, ?string $name = NULL): self
    {

        if (NULL === $name) {

            $this->message['attachment'][] = [
                'file' => $file
            ];

        } else {

            $this->message['attachment'][] = [
                'file' => $file,
                'name' => $name
            ];

        }

        return $this;

    }

    /**
     * Discard message.
     *
     * @return self
     */
    public function discard(): self
    {
        $this->message = [];
        return $this;
    }

    /**
     * Send message.
     *
     * @returns void
     * @throws MessageException
     */
    public function send(): void
    {

        if ($this->_isMessageInvalid($this->message)) {
            throw new MessageException('Unable to send message: missing required keys');
        }

        $this->adapter->send($this->message);
        $this->discard();

    }

}