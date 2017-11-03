<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Auth\Adapter;

use \Psr\Log\LoggerInterface;
use \Micro\Auth\Exception;

class Oidc extends AbstractAdapter
{
    /**
     * OpenID-connect discovery path
     */
    CONST DISCOVERY_PATH = '/.well-known/openid-configuration';


    /**
     * OpenID-connect provider url
     *
     * @var string
     */
    protected $provider_url = 'https://oidc.example.org';


    /**
     * Token validation endpoint (rfc7662)
     *
     * @var string
     */
    protected $token_validation_url;


    /**
     * Identity attribute
     *
     * @var string
     */
    protected $identity_attribute = 'preferred_username';


    /**
     * Attributes
     *
     * @var array
     */
    protected $attributes = [];


    /**
     * Access token
     *
     * @var string
     */
    private $access_token;


    /**
     * Init adapter
     *
     * @param   LoggerInterface $logger
     * @param   Iterable $config
     * @return  void
     */
    public function __construct(LoggerInterface $logger, ?Iterable $config=null)
    {
        $this->logger = $logger;
        $this->setOptions($config);
    }


    /**
     * Set options
     *
     * @param   Iterable $config
     * @return  AdapterInterface
     */
    public function setOptions(? Iterable $config = null) : AdapterInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach($config as $option => $value) {
            switch($option) {
                case 'provider_url':
                case 'token_validation_url':
                case 'identity_attribute':
                    $this->{$option} = (string)$value;
                    unset($config[$option]);
                break;
            }
        }

        return  parent::setOptions($config);
    }


    /**
     * Authenticate
     *
     * @return bool
     */
    public function authenticate(): bool
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->logger->debug('skip auth adapter ['.get_class($this).'], no http authorization header or access_token param found', [
                'category' => get_class($this)
            ]);

            return false;
        } else {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
            $parts  = explode(' ', $header);

            if ($parts[0] == 'Bearer') {
                $this->logger->debug('found http bearer authorization header', [
                    'category' => get_class($this)
                ]);

                return $this->verifyToken($parts[1]);
            } else {
                $this->logger->debug('http authorization header contains no bearer string or invalid authentication string', [
                    'category' => get_class($this)
                ]);

                return false;
            }
        }
    }


    /**
     * Get discovery url
     *
     * @return string
     */
    public function getDiscoveryUrl(): string
    {
        return $this->provider_url.self::DISCOVERY_PATH;
    }


    /**
     * Get discovery document
     *
     * @return array
     */
    public function getDiscoveryDocument(): array
    {
        if ($apc = extension_loaded('apc') && apc_exists($this->provider_url)) {
            return apc_get($this->provider_url);
        } else {
            $ch = curl_init();
            $url = $this->getDiscoveryUrl();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $this->logger->debug('fetch openid-connect discovery document from ['.$url.']', [
                'category' => get_class($this)
            ]);

            $result = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if($code === 200) {
                $discovery = json_decode($result, true);
                $this->logger->debug('received openid-connect discovery document from ['.$url.']', [
                    'category' => get_class($this),
                    'discovery'=> $discovery
                ]);

                if ($apc === true) {
                    apc_store($this->provider_url, $discovery);
                }

                return $discovery;
            } else {
                $this->logger->error('failed to receive openid-connect discovery document from ['.$url.'], request ended with status ['.$code.']', [
                    'category' => get_class($this),
                ]);

                throw new Exception('failed to get openid-connect discovery document');
            }
        }
    }


    /**
     * Token verification
     *
     * @param   string $token
     * @return  bool
     */
    protected function verifyToken(string $token): bool
    {
        if($this->token_validation_url) {
            $this->logger->debug('validate oauth2 token via rfc7662 token validation endpoint ['.$this->token_validation_url.']', [
               'category' => get_class($this),
            ]);

            $url = str_replace('{token}', $token, $this->token_validation_url);
        } else {
            $discovery = $this->getDiscoveryDocument();
            if (!(isset($discovery['userinfo_endpoint']))) {
                throw new Exception('userinfo_endpoint could not be determained');
            }

            $this->logger->debug('validate token via openid-connect userinfo_endpoint ['.$discovery['userinfo_endpoint'].']', [
               'category' => get_class($this),
            ]);

            $url = $discovery['userinfo_endpoint'].'?access_token='.$token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $response = json_decode($result, true);

        if($code === 200) {
            $attributes = json_decode($result, true);
            $this->logger->debug('successfully verified oauth2 access token via authorization server', [
               'category' => get_class($this),
            ]);

            if(!isset($attributes[$this->identity_attribute])) {
                throw new Exception('identity attribute '.$this->identity_attribute.' not found in oauth2 response');
            }

            $this->identifier = $attributes['preferred_username'];

            if($this->token_validation_url) {
                $this->attributes = $attributes;
            } else {
                $this->access_token = $token;
            }

            return true;
        } else {
            $this->logger->error('failed verify oauth2 access token via authorization server, received status ['.$code.']', [
               'category' => get_class($this),
            ]);

            throw new Exception('failed verify oauth2 access token via authorization server');
        }
    }


    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        if(count($this->attributes) !== 0) {
            return $this->attributes;
        }

        $discovery = $this->getDiscoveryDocument();
        if (!(isset($discovery['authorization_endpoint']))) {
            throw new Exception('authorization_endpoint could not be determained');
        }

        $this->logger->debug('fetch user attributes from userinfo_endpoint ['.$discovery['userinfo_endpoint'].']', [
           'category' => get_class($this),
        ]);

        $url = $discovery['userinfo_endpoint'].'?access_token='.$this->access_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $response = json_decode($result, true);

        if($code === 200) {
            $attributes = json_decode($result, true);
            $this->logger->debug('successfully requested user attributes from userinfo_endpoint', [
               'category' => get_class($this),
            ]);

            return $this->attributes = $attributes;
        } else {
            $this->logger->error('failed requesting user attributes from userinfo_endpoint, status code ['.$code.']', [
               'category' => get_class($this),
            ]);

            throw new Exception('failed requesting user attribute from userinfo_endpoint');
        }

    }
}
