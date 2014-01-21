--TEST--
Cgiapp2::start_mode
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    
    // No mode passed
    echo "Test1: No mode passed\n";
    $obj = new Test();
    $mode = $obj->start_mode();
    echo $mode, "\n";
    unset($obj); unset($mode);

    // Non-string mode passed
    echo "Test2: Non-string mode (array) passed\n";
    $obj = new Test();
    $mode = $obj->start_mode(array('.'));
    echo $mode, "\n";
    unset($obj); unset($mode);

    // Non-string mode passed
    echo "Test3: Non-string mode (object) passed\n";
    $obj = new Test();
    $mode = $obj->start_mode($obj);
    echo $mode, "\n";
    unset($obj); unset($mode);

    // String mode passed
    echo "Test4: Valid mode passed\n";
    $obj = new Test();
    $mode = $obj->start_mode('mode');
    echo $mode, "\n";
    unset($obj); unset($mode);

?>
--EXPECT--
Test1: No mode passed
start
Test2: Non-string mode (array) passed
start
Test3: Non-string mode (object) passed
start
Test4: Valid mode passed
mode

