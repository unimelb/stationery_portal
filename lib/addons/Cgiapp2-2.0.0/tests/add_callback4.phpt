--TEST--
Cgiapp2::add_callback() - valid static callback, no class passed
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    echo "Test 1: valid hook, valid static callback, no class provided\n";
    if (Cgiapp2::add_callback('init', array('Test', 'initHookStatic'))) {
        echo "Success\n";
    } else {
        echo "Failed\n";
    }
?>
--EXPECT--
Test 1: valid hook, valid static callback, no class provided
Success

