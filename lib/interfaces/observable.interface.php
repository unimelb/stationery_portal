<?php
/**
 * Generic interface for observable classes
 * 
 * @package interfaces
 * @copyright University of Melbourne, 2007
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 * Observable interface
 *
 * see M. Zandstra, PHP 5 Objects, Patterns, and Practice, p.204
 * 
 * @package interfaces
 */
interface IObservable {
	/**
	 * attach an observer
	 */
	public function attach(IObserver $observer);
	/**
	 * detach an observer
	 */
	public function detach(IObserver $observer);
	/**
	 * notify an observer
	 */
	public function notify();
}
