--TEST--
Hook: error
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call error hook\n";
    $test->callHook('error');
?>
--EXPECT--
Test 1: Call error hook
errorHook triggered for HookTest
Error passed is an error

