--TEST--
Cgiapp2::get_current_runmode
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    
    // No mode set
    echo "Test1: No mode set\n";
    $obj = new Test();
    $mode = $obj->get_current_runmode();
    echo $mode, "\n";
    unset($obj); unset($mode);

    // Mode set
    echo "Test2: Mode set\n";
    $obj = new Test2();
    $obj->set_current_runmode();
    $mode = $obj->get_current_runmode();
    echo $mode, "\n";
    unset($obj); unset($mode);
?>
--EXPECT--
Test1: No mode set

Test2: Mode set
mode1

