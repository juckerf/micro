<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Db\Wrapper;

use \Psr\Log\LoggerInterface as Logger;
use \Pdo as PdoServer;
use \PDOStatement;

class Pdo
{
    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;


    /**
     * Dsn
     *
     * @var string
     */
    protected $dsn = 'mysql:host=localhost;dbname=mysql';


    /**
     * Username
     *
     * @var string
     */
    protected $username = 'root';


    /**
     * Password
     *
     * @var string
     */
    protected $password = '';


    /**
     * Driver specific options
     *
     * @var array
     */
    protected $options = [];


    /**
     * Connection resource
     *
     * @var resource
     */
    protected $connection;


    /**
     * Last inserted id
     *
     * @var array
     */
    protected $last_inserted_ids;


    /**
     * construct
     *
     * @param   Iterable $config
     * @param   Logger   $logger
     */
    public function __construct(? Iterable $config, Logger $logger)
    {
        $this->setOptions($config);
        $this->logger = $logger;
    }


    /**
     * Connect
     *
     * @return Pdo
     */
    public function connect(): Pdo
    {
        $this->connection = new PdoServer($this->dsn, $this->username, $this->password, $this->options);
        $this->logger->info('connection to db server ['.$this->dsn.'] using pdo was succesful', [
            'category' => get_class($this),
        ]);

        return $this;
    }


    /**
     * Forward calls
     *
     * @param  array $method
     * @param  array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments = [])
    {
        return call_user_func_array([&$this->connection, $method], $arguments);
    }


    /**
     * Set options
     *
     * @param  Iterable $config
     * @return Pdo
     */
    public function setOptions(? Iterable $config = null) : Pdo
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'dsn':
                    $this->dsn = (string)$value;
                    break;
                case 'username':
                    $this->username = (string)$value;
                    break;
                case 'password':
                    $this->password = (string)$value;
                    break;
                case 'options':
                    foreach ($value as $opt => $val) {
                        $this->options[$opt] = (string)$val;
                    }
                    break;
            }
        }

        return $this;
    }


    /**
     * Get connection
     *
     * @return resource
     */
    public function getResource()
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }


    /**
     * Query
     *
     * @param  string $query
     * @return PDOStatement
     */
    public function select(string $query): PDOStatement
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $link   = $this->getResource();
        $result = $link->query($query);

        if ($result === false) {
            throw new Exception('failed to execute sql query with error '.$link->errorInfo()[2].' ('.$link->errorCode().')');
        }

        return $result;
    }


    /**
     * Select query
     *
     * @param  string $query
     * @return bool
     */
    public function query(string $query): bool
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $link   = $this->getResource();
        $result = $link->exec($query);

        if ($result === false) {
            throw new Exception('failed to execute sql query with error '.$link->errorInfo().' ('.$link->errorCode().')');
        } else {
            $this->logger->debug('sql query affected ['.$result.'] rows', [
                'category' => get_class($this),
            ]);
        }

        return true;
    }


    /**
     * Prepare query
     * 
     * @param  string $query
     * @param  Iterable $values
     * @return mysqli_stmt
     */
    public function prepare(string $query, Iterable $values): mysqli_stmt
    {
        $this->logger->debug('prepare and execute mysql query ['.$query.'] with values [{values}]', [
            'category' => get_class($this),
            'values'   => $values
        ]);

        $link  = $this->getResource();
        $stmt  = $link->prepare($query);

        if (!($stmt instanceof mysqli_stmt)) {
            throw new Exception('failed to prepare mysql query with error '.$link->error.' ('.$link->errno.')');
        }

        $types = '';
        foreach ($values as $attr => $value) {
            $types .= 's';
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt;
    }
}
