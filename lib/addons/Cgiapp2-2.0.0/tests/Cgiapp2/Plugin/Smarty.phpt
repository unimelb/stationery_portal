--TEST--
Cgiapp2_Plugin_Smarty
--SKIPIF--
<?php
    @include_once 'Smarty.class.php';
    if (!class_exists('Smarty')) {
       echo 'skip';
       die();
    }

    @include_once dirname(__FILE__) . '/../../setup-Smarty.php.inc';
    if (!class_exists('SmartyTest')) {
       echo 'skip';
       die();
    }
?>
--FILE--
<?php
    @include_once dirname(__FILE__) . '/../../setup-Smarty.php.inc';
    if (!class_exists('SmartyTest')) {
        echo "Unable to find SmartyTest\n";
        echo 'include_path == ' . ini_get('include_path') . "\n";
        exit();
    }

    $webapp = new SmartyTest(array(
        'TMPL_PATH' => dirname(__FILE__) . '/tmpl',
        'TMPL_ARGS' => array('caching' => 0)
    ));

    $content = $webapp->testMethod();
    if (strstr($content, 'val1')) {
        echo "Success\n";
    } else {
        echo "Failed\n";
        echo "Returned content: \n";
    }
?>
--EXPECT--
Success

