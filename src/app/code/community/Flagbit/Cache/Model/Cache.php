<?php
/**
 * Flagbit_Cache
 *
 * @category  Mage
 * @package   Flagbit_Cache
 * @copyright Copyright (c) 2011 Flagbit GmbH & Co. KG (http://www.flagbit.de/)
 */

/**
 * Cache Backend class
 * 
 * This Model Class add memcached support to Magento Cache
 * 
 * @category  Mage
 * @package   Flagbit_Cache
 * @copyright Copyright (c) 2011 Flagbit GmbH & Co. KG (http://www.flagbit.de/)
 * @author    Joerg Weller <weller@flagbit.de>
 */
class Flagbit_Cache_Model_Cache extends Mage_Core_Model_Cache
{


    /**
     * Shared memory backend models list (required TwoLevels backend model)
     *
     * @var array
     */
    protected $_shmBackends = array(
        'apc', 'memcached', 'xcache',
        'zendserver_shmem', 'zendserver_disk', 'varien_eaccelerator',
    	'libmemcached'
    );


    /**
     * Get cache backend options. Result array contain backend type ('type' key) and backend options ('options')
     *
     * @param   array $cacheOptions
     * @return  array
     */
    protected function _getBackendOptions(array $cacheOptions)
    {
        $enable2levels = false;
        $type   = isset($cacheOptions['backend']) ? $cacheOptions['backend'] : $this->_defaultBackend;
        if (isset($cacheOptions['backend_options']) && is_array($cacheOptions['backend_options'])) {
            $options = $cacheOptions['backend_options'];
        } else {
            $options = array();
        }
		
        $backendType = false;
        switch (strtolower($type)) {
            case 'sqlite':
                if (extension_loaded('sqlite') && isset($options['cache_db_complete_path'])) {
                    $backendType = 'Sqlite';
                }
                break;
            case 'memcached':
                if (extension_loaded('memcache')) {
                    if (isset($cacheOptions['memcached'])) {
                        $options = $cacheOptions['memcached'];
                    }
                    $enable2levels = true;
                    $backendType = 'Memcached';
                }
                break;
            case 'libmemcached':
                if (extension_loaded('memcached')) {
                    if (isset($cacheOptions['libmemcached'])) {
                        $options = $cacheOptions['libmemcached'];
                    }
                    $enable2levels = true;
                    $backendType = 'Zend_Cache_Backend_Libmemcached';
                }
                break;                
            case 'apc':
                if (extension_loaded('apc') && ini_get('apc.enabled')) {
                    $enable2levels = true;
                    $backendType = 'Apc';
                }
                break;
            case 'xcache':
                if (extension_loaded('xcache')) {
                    $enable2levels = true;
                    $backendType = 'Xcache';
                }
                break;
            case 'eaccelerator':
            case 'varien_cache_backend_eaccelerator':
                if (extension_loaded('eaccelerator') && ini_get('eaccelerator.enable')) {
                    $enable2levels = true;
                    $backendType = 'Varien_Cache_Backend_Eaccelerator';
                }
                break;
            case 'database':
                $backendType = 'Varien_Cache_Backend_Database';
                $options = $this->getDbAdapterOptions();
                break;
            default:
                if ($type != $this->_defaultBackend) {
                    try {
                        if (class_exists($type, true)) {
                            $implements = class_implements($type, true);
                            if (in_array('Zend_Cache_Backend_Interface', $implements)) {
                                $backendType = $type;
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
        }

        if (!$backendType) {
            $backendType = $this->_defaultBackend;
            foreach ($this->_defaultBackendOptions as $option => $value) {
                if (!array_key_exists($option, $options)) {
                    $options[$option] = $value;
                }
            }
        }

        $backendOptions = array('type' => $backendType, 'options' => $options);
        if ($enable2levels) {
            $backendOptions = $this->_getTwoLevelsBackendOptions($backendOptions, $cacheOptions);
            $backendOptions['type'] = 'Flagbit_Cache_Backend_TwoLevels';
        }
        return $backendOptions;
    }

   
}
