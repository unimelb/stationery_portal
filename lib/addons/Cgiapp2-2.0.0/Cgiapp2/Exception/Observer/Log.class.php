<?php
/**
 * Cgiapp2 - Framework for building reusable web-applications
 *
 * A PHP5 port of perl's CGI::Application, a framework for building reusable web
 * applications. 
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>; based on
 * CGI::Application, by Jesse Erlbaum <jesse@erlbaum.net>, et. al.
 * @copyright (c) 2004 - present, Matthew Weier O'Phinney
 * @license BSD License (http://www.opensource.org/licenses/bsd-license.php)
 * @category Tools and Utilities
 * @tutorial Cgiapp2/Cgiapp2.cls
 * @version $Id:$
 */

/**
 * Observes Cgiapp2_Exception
 */
require_once 'Cgiapp2/Exception.class.php';

/**
 * Implements Cgiapp2_Exception_Observer_Interface
 */
require_once 'Cgiapp2/Exception/Observer/Interface.class.php';

/**
 * Cgiapp2_Exception_Observer_Log 
 *
 * {@link Cgiapp2_Exception} observer. Writes exception information to a log
 * file.
 *
 * Sample usage:
 * <code>
 * require_once 'Cgiapp2/Exception/Observer/Log.class.php';
 *
 * // Set the log file to '/tmp/exceptions/log'
 * Cgiapp2_Exception_Observer_Log::setFile('/tmp/exceptions/log');
 *
 * try {
 *     throw new Cgiapp2_Exception('Log this...');
 * } catch (Cgiapp2_Exception $e) {
 *     // do something
 * }
 * </code>
 * 
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com> 
 * @copyright (c) 2006 - Present, Matthew Weier O'Phinney
 * <mweierophinney@gmail.com>
 * @version @release-version@
 */
class Cgiapp2_Exception_Observer_Log implements Cgiapp2_Exception_Observer_Interface
{
    /**
     * Log file. Defaults to '/tmp/cgiapp_exception.log' 
     * @var string
     * @access public
     */
    public $file;

    /**
     * sprintf() style format for log message. Defaults to 
     * "[%s] [%s:%c] %c: %s\n" ([date] [file:line] code: message)
     * @var string
     * @access public
     */
    public $format;

    /**
     * Singleton instance
     * @var bool|Cgiapp2_Exception_Observer_Log 
     * @static
     * @access private
     */
    private static $instance = false;

    /**
     * Constructor
     * 
     * @param mixed $file File to which to log; must be writeable
     * @param mixed $format Defaults to empty; log format (printf compatible)
     * @access public
     * @return void
     * @throws Exception if log file is not writable
     */
    private function __construct($file, $format)
    {
        $this->file   = $file;
        $this->format = $format;
    }

    /**
     * Singleton
     * 
     * @static
     * @access public
     * @param string $file Location of log
     * @param string $format sprintf() format for log
     * @return void
     * @throws Exception if unable to write to file
     */
    public static function getInstance($file = null, $format = null)
    {
        if (self::$instance) {
            return self::$instance;
        }

        if (empty($file)) {
            $file = '/tmp/cgiapp_exception.log';
        }
        if (file_exists($file) && !is_writable($file)) {
            throw new Exception(__CLASS__ . ' file \'' . $file . '\' is not writable');
        } elseif (!file_exists($file) && !is_writable(dirname($file))) {
            throw new Exception(__CLASS__ . ' file \'' . $file . '\' can not be created');
        }

        if (empty($format)) {
            $format = "[%s] [%s:%c] %c: %s\n";
        }

        self::$instance = new Cgiapp2_Exception_Observer_Log($file, $format);

        return self::$instance;
    }

    /**
     * Set the log file
     * 
     * @static
     * @access public
     * @param string $file 
     * @return bool
     */
    public static function setFile($file)
    {
        if (file_exists($file) && !is_writable($file)) {
            return false;
        } elseif (!file_exists($file) && !is_writable(dirname($file))) {
            return false;
        }

        self::getInstance()->file = $file;
        return true;
    }

    /**
     * Set the log format
     * 
     * @static
     * @access public
     * @param mixed $format 
     * @return void
     */
    public static function setFormat($format)
    {
        self::getInstance()->format = $format;
    }

    /**
     * Log an exception
     * 
     * @static
     * @access public
     * @param Cgiapp2_Exception $e
     * @return void
     * @throws Exception if unable to append file or obtain lock
     */
    public static function event(Cgiapp2_Exception $e)
    {
        $handler = self::getInstance();
        $log     = $handler->file;
        $fh      = fopen($log, 'a');
        if (false === $fh) {
            throw new Exception(__CLASS__ . ' unable to append to log file');
        }
        if (!flock($fh, LOCK_EX)) {
            throw new Exception(__CLASS__ . ' unable to lock log file');
        }

        $date = date('Y-m-d H:i:s');
        $file = $e->getFile();
        $line = $e->getLine();
        $code = $e->getCode();
        $msg  = $e->getMessage();

        $msg = sprintf($handler->format, $date, $file, $line, $code, $msg);
        fwrite($fh, $msg);
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/**
 * Observe Cgiapp2_Exception
 */
Cgiapp2_Exception::attach('Cgiapp2_Exception_Observer_Log');
