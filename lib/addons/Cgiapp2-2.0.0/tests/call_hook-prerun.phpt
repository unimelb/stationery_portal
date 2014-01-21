--TEST--
Hook: prerun
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call prerun hook\n";
    $test->callHook('prerun');
?>
--EXPECT--
Test 1: Call prerun hook
prerunHook triggered for HookTest
rm passed is someMode

