--TEST--
Hook: init
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call init hook\n";
    $test->callHook('init');
?>
--EXPECT--
Test 1: Call init hook
initHook triggered for HookTest
Args are:
Array
(
    [CLASS] => HookTest
)

