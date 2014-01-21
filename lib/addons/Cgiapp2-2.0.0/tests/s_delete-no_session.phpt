--TEST--
Cgiapp2::s_delete
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    // Test no parameters
    $obj = new Test();

    echo "Test 0: Sessions not started\n";
    $data = $obj->s_delete();
    print_r($data); echo "\n";
?>
--EXPECT--
Test 0: Sessions not started
Session handling has not been activated

