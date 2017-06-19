<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @copyright Copryright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 */

namespace Micro\Api;

use \Psr\Log\LoggerInterface as Logger;
use \StdClass;

class Moodle
{
    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;


    /**
     * URI
     *
     * @var string
     */
    protected $uri = 'http://127.0.0.1';


    /**
     * Token
     *
     * @var string
     */
    protected $token;


    /**
     * Username
     *
     * @var string
     */
    protected $username;


    /**
     * Password
     *
     * @var string
     */
    protected $password;


    /**
     * Moodle response format
     *
     * @var string
     */
    protected $moodle_response_format = 'json';


    /**
     * tls
     *
     * @var bool
     */
    protected $tls = false;


    /**
     * CURL options
     *
     * @var array
     */
    protected $curl_options = [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER         => 0,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ];


    /**
     * construct
     *
     * @param   Iterable $config
     * @param   Logger   $logger
     */
    public function __construct(?Iterable $config, Logger $logger)
    {
        $this->setOptions($config);
        $this->logger = $logger;
    }


    /**
     * Set options
     *
     * @param  Iterable $config
     * @return Moodle
     */
    public function setOptions(?Iterable $config): Moodle
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'uri':
                    $this->uri = (string)$value;
                    break;
                case 'token':
                    $this->token = (string)$value;
                    break;
                case 'username':
                    $this->username = (string)$value;
                    break;
                case 'password':
                    $this->password = (string)$value;
                    break;
                case 'tls':
                    $this->tls = (bool)(int)$value;
                    break;
                case 'options':
                    foreach ($value as $opt) {
                        $name                      = constant($opt['attr']);
                        $this->curl_options[$name] = $opt['value'];
                    }
                    break;
            }
        }

        return $this;
    }


    /**
     * Run CURL request
     *
     * @param  string $params
     * @param  string $function
     * @return array
     */
    public function restCall(string $params, $function): ?array
    {
        $url = $this->uri .
            '/webservice/rest/server.php?wstoken=' . $this->token .
            '&wsfunction=' . $function .
            '&moodlewsrestformat=' . $this->moodle_response_format . '&' .
            $params;

        $this->logger->info('execute curl request [' . $url . ']', [
            'category' => get_class($this),
        ]);


        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        foreach ($this->curl_options as $opt => $value) {
            curl_setopt($ch, $opt, $value);
        }

        $body      = curl_exec($ch);
        $http_code = curl_getinfo($ch)['http_code'];
        if ($http_code !== 200) {
            throw new Exception('http request failed with response code ' . $http_code);
        }

        curl_close($ch);

        $body = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('failed decode moodle json response with error ' . json_last_error());
        } elseif ($body instanceof StdClass && isset($body->exception)) {
            if (isset($body->debuginfo)) {
                $this->logger->debug($body->debuginfo, [
                    'category' => get_class($this)
                ]);
            }

            throw new Exception('moodle api request failed with exception ' . $body->message);
        }

        return $body;
    }
}
