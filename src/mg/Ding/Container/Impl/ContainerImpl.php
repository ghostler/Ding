<?php
/**
 * Container implementation.
 *
 * PHP Version 5
 *
 * @category Ding
 * @package  Container
 * @author   Marcelo Gornstein <marcelog@gmail.com>
 * @license  http://www.noneyet.ar/ Apache License 2.0
 * @version  SVN: $Id$
 * @link     http://www.noneyet.ar/
 */
namespace Ding\Container\Impl;

use Ding\Cache\CacheLocator;

use Ding\Cache\CacheLocaCacheLocator;
use Ding\Container\IContainer;
use Ding\Container\Exception\ContainerException;
use Ding\Bean\Factory\BeanFactory;
use Ding\Bean\Factory\Impl\BeanFactoryXmlImpl;
use Ding\Aspect\Proxy;
use Ding\Aspect\InterceptorDefinition;

/**
 * Container implementation.
 *
 * PHP Version 5
 *
 * @category Ding
 * @package  Container
 * @author   Marcelo Gornstein <marcelog@gmail.com>
 * @license  http://www.noneyet.ar/ Apache License 2.0
 * @link     http://www.noneyet.ar/
 */
class ContainerImpl implements IContainer
{
    /**
     * Bean factory
     * @var IBeanFactory
     */
    private $_factory = false;
    
    /**
     * Registered shutdown methods for beans (destroy-methods).
     * @var array
     */
    private $_shutdowners;
    
    /**
     * Holds our beans cache.
     * @var ICache
     */
    private $_beanCache;
    
    /**
     * Container instance.
     * @var ContainerImpl
     */
    private static $_containerInstance = false;

    /**
     * Returns factory in use.
	 * @return IBeanFactory
     */
    private function _getFactory()
    {
        return $this->_factory;
    }

    /**
     * Returns a bean.
     * 
     * @param string $bean Bean name.
     * 
     * @return object
     */
    public function getBean($bean)
    {
        $result = false;
        $ret = $this->_beanCache->fetch($bean . '.bean', $result);
        if ($result === false) {
            $ret = $this->_factory->getBean($bean);
            $this->_beanCache->store($bean . '.bean', $ret);
        }
        return $ret;
    }
    
    /**
     * This will return a container using a BeanFactoryXmlImpl with the
     * given beans.xml file.
     * 
     * @param string $filename   Absolute path to beans.xml
     * @param array  $properties Container properties.
     * 
     * @return ContainerImpl
     */
    public static function getInstanceFromXml(
        $filename, array $properties = array()
    ) {
        if (self::$_containerInstance === false) {
            // Init cache subsystems.
            if (isset($properties['ding']['cache'])) {
                CacheLocator::configure($properties['ding']['cache']);
            }
            $factory = BeanFactoryXmlImpl::getInstance($filename, $properties);
            $ret = new ContainerImpl($factory);
            $factory->setContainer($ret);
            self::$_containerInstance = $ret;
        } else {
            $ret = self::$_containerInstance;
        }
        return $ret;
    }
    
    /**
     * Register a shutdown (destroy-method) method for a bean.
     * 
     * @param object $bean   Bean to call.
     * @param string $method Method to call.
     * 
     * @see Ding\Container.IContainer::registerShutdownMethod()
     * 
     * @return void
     */
    public function registerShutdownMethod($bean, $method)
    {
        $this->_shutdowners[] = array($bean, $method);
    }
    
    /**
     * Destructor, will call all beans destroy-methods.
     * 
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->_shutdowners as $shutdownCall) {
            $bean = $shutdownCall[0];
            $method = $shutdownCall[1];
            $bean->$method();
        }
    }
    
    /**
     * Constructor.
     * 
     * @param BeanFactory $factory Bean factory to be used.
     * 
     * @return void
     */
    protected function __construct(BeanFactory $factory)
    {
        $this->_beans = array();
        $this->_factory = $factory;
        $this->_shutdowners = array();
        $this->_beanCache = CacheLocator::getBeansCacheInstance();
        self::$_containerInstance = $this;
    }
}
