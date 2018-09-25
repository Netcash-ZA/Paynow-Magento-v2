<?php
namespace Paynow\Paynow\Model;

/**
 * Config Factory Class
 * Class ConfigFactory
 * @package Paynow\Paynow\Model
 */
class ConfigFactory
{
	/**
	 * Object Manager instance
	 *
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	protected $_objectManager = null;
	/**
	 * Instance name to create
	 *
	 * @var string
	 */
	protected $_instanceName = null;
	/**
	 * Factory constructor
	 *
	 * @param \Magento\Framework\ObjectManagerInterface $objectManager
	 * @param string $instanceName
	 */
	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		$instanceName = '\\Paynow\\Paynow\\Model\\Config'
	) {
		$this->_objectManager = $objectManager;
		$this->_instanceName = $instanceName;
	}
	/**
	 * Create class instance with specified parameters
	 *
	 * @param array $data
	 * @return \Paynow\Paynow\Model\Config
	 */
	public function create(array $data = array())
	{
		return $this->_objectManager->create($this->_instanceName, $data);
	}
}
