--TEST--
Cgiapp2::add_callback() - bad hooks and callbacks
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    echo "Test 1: invalid hook\n";
    if (Cgiapp2::add_callback('bogus', 'bogus')) {
        echo "Failed\n";
    } else {
        echo "Success\n";
    }

    echo "Test 2: valid hook, invalid callback\n";
    if (Cgiapp2::add_callback('teardown', false)) {
        echo "Failed\n";
    } else {
        echo "Success\n";
    }

    echo "Test 3: valid hook, valid callback, no class\n";
    if (Cgiapp2::add_callback('teardown', 'bogus')) {
        echo "Failed\n";
    } else {
        echo "Success\n";
    }
?>
--EXPECT--
Test 1: invalid hook
Unknown hook (bogus)
Success
Test 2: valid hook, invalid callback
Invalid callback
Success
Test 3: valid hook, valid callback, no class
Invalid callback
Success

