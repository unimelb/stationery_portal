--TEST--
Hook: tmpl_assign
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call tmpl_assign hook\n";
    $test->callHook('tmpl_assign');
?>
--EXPECT--
Test 1: Call tmpl_assign hook
tmplAssignHook triggered for HookTest
Args are:
Array
(
    [0] => Array
        (
            [var1] => val1
        )

)

