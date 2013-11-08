<?php
/**
 * Generic interface for observer classes
 * 
 * @package interfaces
 * @copyright University of Melbourne, 2007
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 * Observer interface
 *
 * see M. Zandstra, PHP 5 Objects, Patterns, and Practice, p.204-206
 * @package interfaces
 */
interface IObserver {
	/**
	 * update an observable
	 */
	public function update(IObservable $observable);
}
