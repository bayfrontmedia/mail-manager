<?php

namespace Bayfront\MailManager;

use Bayfront\MailManager\Exceptions\MessageException;
use Bayfront\MailManager\Exceptions\QueueException;
use DateTimeInterface;
use PDO;
use PDOException;

class MailQueue extends Mail
{

    protected PDO $pdo;
    protected array $config;

    /**
     * MailQueue constructor.
     *
     * @param AdapterInterface $adapter
     * @param PDO $pdo
     * @param array $config
     * @throws QueueException
     */
    public function __construct(AdapterInterface $adapter, PDO $pdo, array $config = [])
    {

        parent::__construct($adapter);

        $this->pdo = $pdo;

        $this->config = array_merge([
            'table' => 'mail_queue',
            'max_attempts' => 3
        ], $config);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Throw exceptions

        try {

            $table = $this->config['table'];

            $query = $this->pdo->prepare("CREATE TABLE IF NOT EXISTS $table (
                `id` int(32) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `message` longtext NOT NULL,
                `priority` int(11) NOT NULL,
                `date_due` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `date_attempted` datetime NULL DEFAULT NULL,
                `attempts` int(11) NOT NULL DEFAULT '0'
            )");

            $query->execute();

        } catch (PDOException $e) {

            throw new QueueException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Queue message.
     *
     * @param DateTimeInterface $date_due
     * @param int $priority (Messages will be sent in order by due date sorted by priority in descending order)
     * @return void
     * @throws QueueException
     */
    public function addQueue(DateTimeInterface $date_due, int $priority = 5): void
    {

        if ($this->_isMessageInvalid($this->message)) {
            throw new QueueException('Unable to queue message: missing required keys');
        }

        try {

            $table = $this->config['table'];

            $stmt = $this->pdo->prepare("INSERT INTO $table (message, priority, date_due) values (:message, :priority, :date_due)");

            $stmt->execute([
                ':message' => json_encode($this->message),
                ':priority' => $priority,
                ':date_due' => $date_due->format('Y-m-d H:i:s')
            ]);

        } catch (PDOException $e) {
            throw new QueueException($e->getMessage(), 0, $e);
        }

        $this->discard();

    }

    /**
     * Remove a given ID from the queue.
     *
     * @param int $id (Unique id from the database table)
     * @return bool
     * @throws QueueException
     */
    public function removeQueue(int $id): bool
    {

        try {

            $table = $this->config['table'];

            $stmt = $this->pdo->prepare("DELETE FROM $table WHERE id = :id");

            $stmt->execute([
                ':id' => $id
            ]);

            if ($stmt->rowCount()) {
                return true;
            }

        } catch (PDOException $e) {
            throw new QueueException($e->getMessage(), 0, $e);
        }

        return false; // No rows affected

    }

    /**
     * Get all messages in queue that are due, up to a given limit.
     *
     * @param int $limit (0 to get all)
     * @return array
     * @throws QueueException
     */
    public function getQueue(int $limit = 0): array
    {

        try {

            $table = $this->config['table'];

            if ($limit == 0) {

                $stmt = $this->pdo->query("SELECT * FROM $table WHERE date_due <= NOW() ORDER BY priority DESC, date_due");

            } else {

                $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE date_due <= NOW() ORDER BY priority DESC, date_due LIMIT :limit");

                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

                $stmt->execute();

            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $k => $v) {

                $results[$k]['message'] = json_decode($v['message'], true);

            }

            return $results;

        } catch (PDOException $e) {
            throw new QueueException($e->getMessage(), 0, $e);
        }

    }

    /**
     * Send messages in queue that are due.
     *
     * @param int $limit (0 to send all)
     * @return array
     * @throws QueueException
     */
    public function sendQueue(int $limit = 0): array
    {

        $queue = $this->getQueue($limit);

        $results = [
            'sent' => 0,
            'removed' => 0,
            'failed' => 0,
            'failed_ids' => []
        ];

        foreach ($queue as $message) {

            // Check number of attempts

            if ($message['attempts'] >= $this->config['max_attempts']) {

                $this->removeQueue($message['id']);
                $results['removed']++;
                break;

            }

            try {

                $this->message = $message['message'];

                $this->send();

                // If sent successfully, remove from queue

                $this->removeQueue($message['id']);

                $results['sent']++;

            } catch (MessageException) { // If unable to send message

                $this->discard();

                // Update attempts

                $message['attempts']++;

                try {

                    $table = $this->config['table'];

                    $stmt = $this->pdo->prepare("UPDATE $table SET date_attempted = NOW(), attempts = :attempts WHERE id = :id");

                    $stmt->execute([
                        ':attempts' => $message['attempts'],
                        ':id' => $message['id']
                    ]);

                    $results['failed']++;

                    $results['failed_ids'][] = $message['id'];

                } catch (PDOException $e) {
                    throw new QueueException($e->getMessage(), 0, $e);
                }

            }

        }

        return $results;

    }

}