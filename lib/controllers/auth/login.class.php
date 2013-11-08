<?php
/**
 * Login class to authenticate users
 *
 * @package controllers
 * @subpackage authentication
 * @copyright University of Melbourne, 2007
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 */
require_once(dirname(__FILE__) . "/../../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/interfaces/observable.interface.php");

/**
 * Login, a class to authenticate users
 * implements the observer pattern
 *
 * @package controllers
 * @subpackage authentication
 */
abstract class Login implements IObservable
{
	/**
	 * @var array observers
	 */
	protected $observers;
	/**
	 * @var boolean authenticated
	 */
	protected $authenticated;

	/**
	 * constructor to create default values
	 */
	public function __construct()
	{
		$this->observers = array();
		$this->authenticated = false;
	}

	/**
	 * attach an observer
	 * @param IOberver Observer object
	 */
	public function attach(IObserver $observer)
	{
		$this->observers[] = $observer;
	}
	/**
	 * detach an observer
	 * @param IOberver Observer object
	 */
	public function detach(IObserver $observer)
	{
		$this->observers = array_diff($this->observers, array($observer));
	}
	/**
	 * notify the observers of a change
	 */
	public function notify()
	{
		foreach ($this->observers as $obs)
		{
			$obs->update($this);
		}
	}
	/**
	 * authenticate the user using their username and password
	 */
	abstract public function authenticate($user, $pass);

	/**
	 * return the value of authenticated
	 */
	public function isAuthenticated()
	{
		return $this->authenticated;
	}
}
?>
