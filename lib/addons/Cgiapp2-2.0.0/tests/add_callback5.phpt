--TEST--
Cgiapp2::add_callback() - valid hook, valid object callback, no class passed
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    echo "Test 1: valid hook, valid object callback, no class provided\n";
    $obj = new Test();
    if (Cgiapp2::add_callback('init', array($obj, 'initHook'))) {
        echo "Success\n";
    } else {
        echo "Failed\n";
    }
?>
--EXPECT--
Test 1: valid hook, valid object callback, no class provided
Success

