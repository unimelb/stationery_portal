<?php
/**
 * Registry classes
 *
 * @package controllers
 * @copyright University of Melbourne, 2007
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 */

/**
 */
require_once(dirname(__FILE__) . "/../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/controllers/request.class.php");
/**
 * Registry class
 * derived from M. Zandstra, PHP 5 Objects, Patterns, and Practice, pp.227
 *
 * @package controllers
 */
abstract class Registry
{
	public function __construct(){}
	abstract protected function get($key);
	abstract protected function set($key,$val);
}

/**
 * RequestRegistry for storing requests
 * used by PageController
 *
 * @package controllers
 */
class RequestRegistry extends Registry
{
	private $values = array();
	private static $instance;
	/**
	 * Registry is a Singleton which just stores a Request object
	 */
	static function instance()
	{
		if (! self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
			
	protected function get($key)
	{
		return $this->values[$key];
	}
	
	protected function set($key,$val)
	{
		$this->values[$key] = $val;
	}
	
	static function getRequest()
	{
		return self::instance()->get('request');
	}
	
	static function setRequest(Request $request)
	{
		return self::instance()->set('request',$request);
	}
}
?>
