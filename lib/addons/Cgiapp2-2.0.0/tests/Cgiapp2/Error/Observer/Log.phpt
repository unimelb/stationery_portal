--TEST--
Cgiapp2_Error_Observer_Log
--SKIPIF--
<?php if (!is_writeable('./')) echo 'skip'; ?>
--FILE--
<?php
    @include_once 'Cgiapp2.class.php';
    if (!class_exists('Cgiapp2')) {
        $PATH = ini_get('include_path');
        ini_set('include_path', dirname(__FILE__) . '/../../../../:' . $PATH);
    }

    include_once 'Cgiapp2/Error.class.php';
    include_once 'Cgiapp2/Error/Observer/Log.class.php';
    Cgiapp2_Error_Observer_Log::setFile('./test.log');
    set_error_handler(array('Cgiapp2_Error', 'handler'));

    echo "Test 1: Trigger an error\n";
    trigger_error('Triggered warning', E_USER_WARNING);
    $log = file_get_contents('./test.log');
    if (strstr($log, 'Triggered warning')) {
        echo "Success\n";
    } else {
        echo "Failure\n";
        echo $log, "\n";
    }

    unlink('./test.log');
?>
--EXPECT--
Test 1: Trigger an error
Success

