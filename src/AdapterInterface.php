<?php

/**
 * @package mail-manager
 * @link https://github.com/bayfrontmedia/mail-manager
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\MailManager;

use Bayfront\MailManager\Exceptions\AdapterException;
use Bayfront\MailManager\Exceptions\MessageException;

interface AdapterInterface
{

    /**
     * Adapter constructor.
     *
     * @param array $config
     *
     * @throws AdapterException
     */

    public function __construct(array $config);

    /**
     * Get underlying instance used by adapter.
     *
     * @return mixed
     */

    public function getInstance();

    /**
     * Send message.
     *
     * @param array $message
     *
     * @return void
     *
     * @throws MessageException
     */

    public function send(array $message): void;

}