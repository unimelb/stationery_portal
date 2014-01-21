--TEST--
Hook: tmpl_path
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call tmpl_path hook\n";
    $test->callHook('tmpl_path');
?>
--EXPECT--
Test 1: Call tmpl_path hook
tmplPathHook triggered for HookTest
Template path passed is tmpl

