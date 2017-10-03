<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author      Raffael Sahli <sahli@gyselroth.net>
 * @copyright   Copryright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license     MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Auth\Adapter;

use \Psr\Log\LoggerInterface as Logger;
use \Micro\Auth\Adapter\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Identity
     *
     * @var string
     */
    protected $identifier;


    /**
     * attribute sync cache
     *
     * @var int
     */
    protected $attr_sync_cache = 0;
    
    
    /**
     * attribute map
     *
     * @var Iterable
     */
    protected $map = [];
   

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;


    /**
     * Init adapter
     *
     * @param   Logger $logger
     * @param   Iterable $config
     * @return  void
     */
    public function __construct(Logger $logger, ?Iterable $config=null)
    {
        $this->logger = $logger;
        $this->setOptions($config);
    }
    

    /**
     * Get attribute sync cache
     *
     * @return int
     */
    public function getAttributeSyncCache(): int
    {
        return $this->attr_sync_cache;
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
        
        foreach ($config as $option => $value) {
            switch ($option) {
                case 'map':
                    $this->map = $value;
                break;
                
                case 'attr_sync_cache':
                    $this->attr_sync_cache = (int)$value;
                break;
            }
        }
        
        return $this;
    }
    

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    
    /**
     * Get attribute map
     *
     * @return Iterable
     */
    public function getAttributeMap(): Iterable
    {
        return $this->map;
    }
}
