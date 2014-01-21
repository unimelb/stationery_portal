--TEST--
Cgiapp2::prerun_mode
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');
    
    // No mode passed
    echo "Test1: No mode passed\n";
    $obj = new Test();
    $mode = $obj->prerun_mode();
    echo $mode, "\n";
    unset($obj); unset($mode);

    // Non-string mode passed
    echo "Test2: Non-string mode (array) passed\n";
    $obj = new Test();
    $mode = $obj->prerun_mode(array('.'));
    echo $mode, "\n";
    unset($obj); unset($mode);

    // Non-string mode passed
    echo "Test3: Non-string mode (object) passed\n";
    $obj = new Test();
    $mode = $obj->prerun_mode($obj);
    echo $mode, "\n";
    unset($obj); unset($mode);

    // String mode passed
    echo "Test4: Valid mode passed; not in cgiapp_prerun()\n";
    $obj = new Test();
    $mode = $obj->prerun_mode('mode');
    echo $mode, "\n";
    unset($obj); unset($mode);

    // String mode passed; PRERUN_MODE_LOCKED
    echo "Test5: Valid mode passed; not in cgiapp_prerun(), in alernate method\n";
    $obj = new Test2();
    $mode = $obj->test_prerun();
    echo $mode, "\n";
    unset($obj); unset($mode);

    // String mode passed via cgiapp_prerun()
    echo "Test6: Valid mode passed via cgiapp_prerun()\n";
    $obj = new Test2();
    $mode = $obj->cgiapp_prerun();
    echo $mode, "\n";
    unset($obj); unset($mode);

?>
--EXPECT--
Test1: No mode passed

Test2: Non-string mode (array) passed

Test3: Non-string mode (object) passed

Test4: Valid mode passed; not in cgiapp_prerun()
prerun_mode() can only be called within cgiapp_prerun()

Test5: Valid mode passed; not in cgiapp_prerun(), in alernate method
prerun_mode() can only be called within cgiapp_prerun()

Test6: Valid mode passed via cgiapp_prerun()
mode

