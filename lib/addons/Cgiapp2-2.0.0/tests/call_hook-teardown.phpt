--TEST--
Hook: teardown
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call teardown hook\n";
    $test->callHook('teardown');
?>
--EXPECT--
Test 1: Call teardown hook
teardownHook triggered for HookTest

