--TEST--
Cgiapp2_Exception_Observer_Log
--SKIPIF--
<?php if (!is_writeable('./')) echo 'skip'; ?>
--FILE--
<?php
    @include_once 'Cgiapp2/Exception/Observer/Log.class.php';
    if (!class_exists('Cgiapp2_Exception_Observer_Log')) {
        $PATH = ini_get('include_path');
        ini_set('include_path', dirname(__FILE__) . '/../../../../:' . $PATH);
        include_once 'Cgiapp2/Exception/Observer/Log.class.php';
    }

    Cgiapp2_Exception_Observer_Log::setFile('./test.log');
    
    echo "Test 1: Trigger an exception\n";
    try {
        throw new Cgiapp2_Exception('Triggered Exception');
    } catch (Cgiapp2_Exception $e) {
        $log = file_get_contents('./test.log');
        if (strstr($log, 'Triggered Exception')) {
            echo "Success\n";
        } else {
            echo "Failure\n";
        }
    }

    include_once 'Cgiapp2/Exception/Error.class.php';
    set_error_handler(array('Cgiapp2_Exception_Error', 'handler'));

    echo "Test 2: Trigger an error\n";
    try {
        trigger_error('Triggered warning', E_USER_WARNING);
    } catch (Cgiapp2_Exception $e) {
        $log = file_get_contents('./test.log');
        if (strstr($log, 'Triggered warning')) {
            echo "Success\n";
        } else {
            echo "Failure\n";
        }
    }

    unlink('./test.log');
?>
--EXPECT--
Test 1: Trigger an exception
Success
Test 2: Trigger an error
Success

