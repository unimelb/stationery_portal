--TEST--
Hook: tmpl_fetch
--FILE--
<?php
    require_once dirname(__FILE__) . '/setup-hooks.php.inc';
    $test = new HookTest();

    echo "Test 1: Call tmpl_fetch hook\n";
    $test->callHook('tmpl_fetch');
?>
--EXPECT--
Test 1: Call tmpl_fetch hook
tmplFetchHook triggered for HookTest
Template file passed is filename.tpl

