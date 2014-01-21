--TEST--
Cgiapp2::call_hook()
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    $obj = new Test();
    echo "Test 1: invalid hook type\n";
    if ($obj->call_hook(array())) {
        echo "Failed\n";
    } else {
        echo "Success\n";
    }

    echo "Test 2: unknown hook type\n";
    if ($obj->call_hook('bogus')) {
        echo "Failed\n";
    } else {
        echo "Success\n";
    }
?>
--EXPECT--
Test 1: invalid hook type
Invalid hook type
Success
Test 2: unknown hook type
Unknown hook (bogus)
Success

