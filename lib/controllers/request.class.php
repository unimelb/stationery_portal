<?php
/**
 * Request class
 *
 * @package controllers
 */

/**
 */
require_once(dirname(__FILE__) . "/../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/controllers/registry.class.php");
/**
 * Request, a simple class to encapsulate server requests
 * used by PageController
 * derived from M. Zandstra, PHP 5 Objects, Patterns, and Practice, pp.237-9
 * I've skipped the use of the Registry pattern, and the feedback mechanism
 *
 * @package controllers
 */
class Request
{
	/**
	 * @var the properties in the request
	 */
	protected $properties;
	/**
	 * @var a list of Writers (the payload)
	 */
	protected $writers;
	/**
	 * @var registry instance;
	 */
	protected $reg;
	/**
	 * constructor
	 */
	public function __construct()
	{
		$this->init();
		RequestRegistry::setRequest($this);
	}
	/**
	 * retrieves data from server request
	 */
	public function init()
	{
		$this->writers = array();
		if ($_SERVER['REQUEST_METHOD'])
		{
			$this->properties = $_REQUEST;
			return;
		}
		foreach ($_SERVER['argv'] as $arg)
		{
			if (strpos($arg, '='))
			{
				list($key, $val) = explode("=", $arg);
				$this->setProperty($key,$val);
			}
		}
	}
	/**
	 * getters and setters
	 */
	public function getProperty($key)
	{
		if (isset($this->properties[$key]))
		{
			return $this->properties[$key];
		}
		else
		{
			return false;
		}
	}
	public function getAllProperties()
	{
		return $this->properties;
	}
	public function setProperty($key,$val)
	{
		$this->properties[$key] = $val;
	}
	public function getWriters()
	{
		return $this->writers;
	}
	public function addWriter($writer)
	{
		if (method_exists($writer,"spit"))
		{
			$this->writers[] = $writer;
		}
	}
}

/**
 * Used if there is already a Request in the Registry, to add its properties and writers to the new one
 * This is experimental and doesn't work yet (24/10/2007) -PM
 */
/*
class CumulativeRequest extends Request
{
	public function init()
	{
		$this->writers = $this->getWriters();
		$this->properties = $this->getAllProperties();
		if ($_SERVER['REQUEST_METHOD'])
		{
			$this->properties = $_REQUEST;
			return;
		}
		foreach ($_SERVER['argv'] as $arg)
		{
			if (strpos($arg, '='))
			{
				list($key, $val) = explode("=", $arg);
				$this->setProperty($key,$val);
			}
		}
	}
}
*/
?>
