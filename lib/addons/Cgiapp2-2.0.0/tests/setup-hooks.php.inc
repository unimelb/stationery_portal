<?php
@include_once 'Cgiapp2.class.php';
if (!class_exists('Cgiapp2')) {
    include_once dirname(__FILE__) . '/../Cgiapp2.class.php';
}

/**
 * Class for testing Cgiapp2 hooks
 *
 * This is a class used for testing Cgiapp2's hooks. It contains some methods
 * that implement hook callbacks, and other methods that call them.
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>
 * @copyright (c) 2006 - Present Matthew Weier O'Phinney
 * <mweierophinney@gmail.com>
 * @version @release-version@
 */
class HookTest extends Cgiapp2 
{
    /**
     * Setup the basic application
     *
     * @access public
     */
    public function setup() 
    {
        $this->param('Class', __CLASS__);

        Cgiapp2::add_callback('init', array($this, 'initHook'));
        Cgiapp2::add_callback('prerun', array($this, 'prerunHook'));
        Cgiapp2::add_callback('postrun', array($this, 'postrunHook'));
        Cgiapp2::add_callback('teardown', array($this, 'teardownHook'));
        Cgiapp2::add_callback('error', array($this, 'errorHook'));
        Cgiapp2::add_callback('tmpl_path', array($this, 'tmplPathHook'));
        Cgiapp2::add_callback('tmpl_assign', array($this, 'tmplAssignHook'));
        Cgiapp2::add_callback('tmpl_fetch', array($this, 'tmplFetchHook'));
    }

    public function callHook($hook)
    {
        switch($hook)
        {
            case 'init':
                $this->call_hook('init', $this->param());
                break;
            case 'prerun':
                $this->call_hook('prerun', 'someMode');
                break;
            case 'postrun':
                $this->call_hook('postrun', 'some content');
                break;
            case 'teardown':
                $this->call_hook('teardown');
                break;
            case 'error':
                $this->call_hook('error', 'an error');
                break;
            case 'tmpl_path':
                $this->call_hook('tmpl_path', 'tmpl');
                break;
            case 'tmpl_assign':
                $this->call_hook('tmpl_assign', array('var1' => 'val1'));
                break;
            case 'tmpl_fetch':
                $this->call_hook('tmpl_fetch', 'filename.tpl');
                break;
            default:
                break;
        }
    }

    public function initHook($args, $cgiapp)
    {
        echo 'initHook triggered for ' . $cgiapp->param('class'), "\n";
        echo 'Args are:', "\n", print_r($args, 1), "\n";
    }

    public function prerunHook($rm, $cgiapp)
    {
        echo 'prerunHook triggered for ' . $cgiapp->param('class'), "\n";
        echo 'rm passed is ' . $rm, "\n";
    }

    public function postrunHook(&$body, $cgiapp)
    {
        echo 'postrunHook triggered for ' . $cgiapp->param('class'), "\n";
        echo 'Content passed is \'' . $body . '\'', "\n";
    }

    public function teardownHook($cgiapp)
    {
        echo 'teardownHook triggered for ' . $cgiapp->param('class'), "\n";
    }

    public function errorHook($cgiapp, $e)
    {
        echo 'errorHook triggered for ' . $cgiapp->param('class'), "\n";
        echo 'Error passed is ' . $e, "\n";
    }

    public function tmplPathHook($cgiapp, $tmpl_path, $tmpl_args = null)
    {
        echo 'tmplPathHook triggered for ' . $cgiapp->param('class'), "\n";
        echo 'Template path passed is ' . $tmpl_path, "\n";
    }

    public function tmplAssignHook($cgiapp)
    {
        $args = func_get_args();
        array_shift($args);
        echo 'tmplAssignHook triggered for ' . $cgiapp->param('class'), "\n";
        echo 'Args are:', "\n", print_r($args, 1), "\n";
    }

    public function tmplFetchHook($cgiapp, $file)
    {
        echo 'tmplFetchHook triggered for ' . $cgiapp->param('class'), "\n";
        echo 'Template file passed is ' . $file, "\n";
    }
}

