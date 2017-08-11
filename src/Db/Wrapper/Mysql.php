<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Db\Wrapper;

use \Psr\Log\LoggerInterface as Logger;
use \Mysqli;
use \mysqli_stmt;
use \mysqli_result;

class Mysql
{

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;


    /**
     * Host
     *
     * @var string
     */
    protected $host = 'localhost';


    /**
     * Port
     *
     * @var int
     */
    protected $port = 3306;


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
     * Database
     *
     * @var string
     */
    protected $database = '';


    /**
     * Charset
     *
     * @var string
     */
    protected $charset = 'utf8';


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
     * @return Mysql
     */
    public function connect(): Mysql
    {
        $this->connection = new Mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
        $this->connection->set_charset($this->charset);

        if (!$this->connection->connect_errno) {
            $this->logger->info('connection to mysql server ['.$this->host.'] was succesful', [
                'category' => get_class($this),
            ]);
        } else {
            throw new Exception('failed to connect to mysql server, error: '.$this->connection->connect_error.' ('.$this->connection->connect_errno.')');
        }

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
     * @return Mysql
     */
    public function setOptions(? Iterable $config = null) : Mysql
    {
        if ($config === null) {
            return $this;
        }
        
        foreach ($config as $option => $value) {
            switch ($option) {
                case 'host':
                    $this->host = (string)$value;
                    break;
                case 'port':
                    $this->port = (int)$value;
                    break;
                case 'username':
                    $this->username = (string)$value;
                    break;
                case 'password':
                    $this->password = (string)$value;
                    break;
                case 'database':
                    $this->database = (string)$value;
                    break;
                case 'charset':
                    $this->charset = (string)$value;
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
     * @return mysqli_result
     */
    public function select(string $query): mysqli_result
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $link   = $this->getResource();
        $result = $link->query($query);

        if ($result === false) {
            throw new Exception('failed to execute sql query with error '.$link->error.' ('.$link->errno.')');
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
        $result = $link->query($query);

        if ($result === false) {
            throw new Exception('failed to execute sql query with error '.$link->error.' ('.$link->errno.')');
        }

        return $result;
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
        $this->logger->debug('prepare and execute sql query ['.$query.'] with values [{values}]', [
            'category' => get_class($this),
            'values'   => $values
        ]);

        $link  = $this->getResource();
        $stmt  = $link->prepare($query);

        if (!($stmt instanceof mysqli_stmt)) {
            throw new Exception('failed to prepare sql query with error '.$link->error.' ('.$link->errno.')');
        }

        $types = '';
        foreach ($values as $attr => $value) {
            $types .= 's';
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        if ($stmt->error) {
            throw new Exception($stmt->error);
        }
        
        return $stmt;
    }
}
