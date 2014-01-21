<?php
    @include_once 'Cgiapp2.class.php';
    @include_once 'Cgiapp2/Plugin/Smarty.class.php';

    if (!class_exists('Cgiapp2')) {
        $PATH = ini_get('include_path');
        ini_set('include_path', dirname(__FILE__) . '/../:' . $PATH);

        include_once 'Cgiapp2.class.php';
        include_once 'Cgiapp2/Plugin/Smarty.class.php';
    }

    class SmartyTest extends Cgiapp2
    {
        function setup()
        {
        }

        function testMethod()
        {
            $this->tmpl_assign('var1', 'val1');
            return $this->load_tmpl('SmartyTest.tpl');
        }
    }


