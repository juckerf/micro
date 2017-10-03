<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Auth;

use \Psr\Log\LoggerInterface as Logger;

class AttributeMap
{
    /**
     * Attribute map
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
     * Initialize
     *
     * @param   Iterable $map
     * @param   Logger $logger
     * @return  void
     */
    public function __construct(Iterable $map, Logger $logger)
    {
        $this->logger  = $logger;
        $this->map     = $map;
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


    /**
     * Prepare attributes
     *
     * @param  array $data
     * @return array
     */
    public function map(array $data): array
    {
        $attrs = [];
        foreach ($this->map as $attr => $value) {
            if (array_key_exists($value['attr'], $data)) {
                $this->logger->info('found attribute mapping ['.$attr.'] => [('.$value['type'].') '.$value['attr'].']', [
                    'category' => get_class($this),
                ]);

                if ($value['type'] == 'array') {
                    $store = $data[$value['attr']];
                } else {
                    $store = $data[$value['attr']];
                    if (is_array($store)) {
                        $store = $store[0];
                    }
                }

                switch ($value['type']) {
                    case 'array':
                        $arr =  (array)$data[$value['attr']];
                        unset($arr['count']);
                        $attrs[$attr] = $arr;
                    break;
                        
                    case 'string':
                         $attrs[$attr] = (string)$store;
                    break;
                                            
                    case 'int':
                         $attrs[$attr] = (int)$store;
                    break;
                                            
                    case 'bool':
                         $attrs[$attr] = (bool)$store;
                    break;
                    
                    default:
                        $this->logger->error('unknown attribute type ['.$value['type'].'] for attribute ['.$attr.']; use one of [array,string,int,bool]', [
                            'category' => get_class($this),
                        ]);
                    break;
                }
            } else {
                $this->logger->warning('auth attribute ['.$value['attr'].'] was not found from authentication adapter response', [
                    'category' => get_class($this),
                ]);
            }
        }

        return $attrs;
    }
}
