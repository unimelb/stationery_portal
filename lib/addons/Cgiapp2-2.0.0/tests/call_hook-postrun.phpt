--TEST--
Hook: postrun
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call postrun hook\n";
    $test->callHook('postrun');
?>
--EXPECT--
Test 1: Call postrun hook
postrunHook triggered for HookTest
Content passed is 'some content'

