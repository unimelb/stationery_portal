--TEST--
Cgiapp2::error_mode
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    
    // No mode passed
    echo "Test1: No mode passed\n";
    $obj = new Test();
    $mode = $obj->error_mode();
    echo $mode, "\n";
    unset($obj); unset($mode);

    // Non-string mode passed
    echo "Test2: Non-string mode (array) passed\n";
    $obj = new Test();
    $mode = $obj->error_mode(array('.'));
    echo $mode, "\n";
    unset($obj); unset($mode);

    // Non-string mode passed
    echo "Test3: Non-string mode (object) passed\n";
    $obj = new Test();
    $mode = $obj->error_mode($obj);
    echo $mode, "\n";
    unset($obj); unset($mode);

    // String mode passed
    echo "Test4: String mode passed; not a method\n";
    $obj = new Test();
    $mode = $obj->error_mode('somemethod');
    echo $mode, "\n";
    unset($obj); unset($mode);

    // String mode passed
    echo "Test5: String mode passed; valid method\n";
    $obj = new Test();
    $mode = $obj->error_mode('method1');
    echo $mode, "\n";
    unset($obj); unset($mode);

?>
--EXPECT--
Test1: No mode passed

Test2: Non-string mode (array) passed

Test3: Non-string mode (object) passed

Test4: String mode passed; not a method

Test5: String mode passed; valid method
method1

