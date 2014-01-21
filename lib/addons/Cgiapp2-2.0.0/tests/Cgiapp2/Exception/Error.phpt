--TEST--
Cgiapp2_Exception_Error
--FILE--
<?php
    @include_once 'Cgiapp2/Exception/Error.class.php';
    if (!class_exists('Cgiapp2_Exception_Error')) {
        $PATH = ini_get('include_path');
        ini_set('include_path', dirname(__FILE__) . '/../../../:' . $PATH);
        include_once 'Cgiapp2/Exception/Error.class.php';
    }

    set_error_handler(array('Cgiapp2_Exception_Error', 'handler'));

    echo "Test 1: illegal operation\n";
    try {
        $a = 2 / 0; 
        echo $a, "\n";
    } catch (Cgiapp2_Exception $e) {
        echo $e->getMessage(), "\n";
    }

    echo "Test 2: triggered error\n";
    try {
        if (true) {
            trigger_error('Triggered error', E_USER_WARNING);
        }
    } catch (Cgiapp2_Exception $e) {
        echo $e->getMessage(), "\n";
    }
?>
--EXPECT--
Test 1: illegal operation
Division by zero
Test 2: triggered error
Triggered error

