<?php

namespace Bayfront\MailManager;

use Bayfront\MailManager\Exceptions\AdapterException;
use Bayfront\MailManager\Exceptions\MessageException;

interface AdapterInterface
{

    /**
     * Adapter constructor.
     *
     * @param array $config
     * @throws AdapterException
     */
    public function __construct(array $config);

    /**
     * Get underlying instance used by the adapter.
     *
     * @return mixed
     */
    public function getInstance(): mixed;

    /**
     * Send message.
     *
     * @param array $message
     * @return void
     * @throws MessageException
     */
    public function send(array $message): void;

}