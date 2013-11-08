<?php
/**
 * Generic interface for outputting xhtml code
 * 
 * @package interfaces
 * @copyright University of Melbourne, 2007
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 * Writer interface
 *
 * must contain a spit() method
 * 
 * Markdown support added 16/4/2008
 * @package interfaces
 */
require_once(dirname(__FILE__) . "/../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/addons/PHPMarkdownExtra1_1_7/markdown.php");
interface IWriter {
	/**
	 * generate some xhtml code
	 */
	public function spit();
}
