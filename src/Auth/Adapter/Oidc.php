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

use \Psr\Log\LoggerInterface as Logger;
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

        if (isset($config['provider_url'])) {
            $this->discovery_url = (string)$config['provider_url'];
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
    protected function getDiscoveryUrl(): string
    {
        return $this->provider_url.self::DISCOVERY_URL;    
    }


    /**
     * Get discovery document
     *
     * @return array
     */    
    protected function getDiscoveryDocument(): array
    {
        if ($apc = extension_loaded('apc') && apc_exists($this->provider_url)) {
            return apc_get($this->provider_url);
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->getDiscoveryUrl());
            $result = curl_exec($ch);
            curl_close($ch);

            $discovery = json_decode($result, true);
            
            if ($apc === true) {
                apc_store($this->provider_url, $discovery);
            }

            return $discovery;
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
        $discovery = $this->getDiscoverDocument();
        if (!(isset($discovery['authorization_endpoint']))) {
            throw new Exception('authorization_endpoint could not be determained');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $discovery['authorization_endpoint'].'?token='.$token);
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result, true);

        $this->identifier = $response['id'];
        return true;
    }


    /**
     * Get attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        return [];
    }
}
