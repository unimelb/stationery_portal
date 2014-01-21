--TEST--
Cgiapp2::mode_param
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    // Test empty
    echo "Test 1: no mode_param set; no args\n";
    $obj = new Test();
    $rm = $obj->mode_param();
    print_r($rm); echo "\n";
    unset($obj);

    // Test string
    echo "Test 2: string arg\n";
    $obj = new Test();
    $rm = $obj->mode_param('mode');
    print_r($rm); echo "\n";
    unset($obj);

    // Test array
    echo "Test 3: array arg, invalid\n";
    $obj = new Test();
    $rm = $obj->mode_param(array('mode'));
    print_r($rm); echo "\n";
    unset($obj);

    // Test associative array with unwanted params
    echo "Test 4: array arg, invalid associative array\n";
    $obj = new Test();
    $rm = $obj->mode_param(array('rma' => 'rmb'));
    print_r($rm); echo "\n";
    unset($obj);

    // Test associative array with PATH_INFO; bad PATH_INFO value
    echo "Test 5: associative array arg with PATH_INFO; bad pi value\n";
    $_SERVER['PATH_INFO'] = '/mode/param';
    $obj = new Test();
    $rm = $obj->mode_param(array('PATH_INFO' => 'string'));
    print_r($rm); echo "\n";
    unset($obj);

    // Test associative array with PATH_INFO; good PATH_INFO value
    echo "Test 6: associative array arg with PATH_INFO; good pi value\n";
    $_SERVER['PATH_INFO'] = '/mode/param';
    $obj = new Test();
    $rm = $obj->mode_param(array('PATH_INFO' => 1));
    print_r($rm); echo "\n";
    unset($obj);

    // Test associative array with PATH_INFO; good PATH_INFO value; 
    // PATH_INFO index missing; no PARAM element
    echo "Test 7: associative array arg with PATH_INFO; missing pi value, no PARAM\n";
    $_SERVER['PATH_INFO'] = '/mode/param';
    $obj = new Test();
    $rm = $obj->mode_param(array('PATH_INFO' => 3));
    print_r($rm); echo "\n";
    unset($obj);

    // Test associative array with PATH_INFO; good PATH_INFO value; 
    // PATH_INFO index missing; using PARAM element
    echo "Test 8: associative array arg with PATH_INFO; good pi value, PARAM\n";
    $_SERVER['PATH_INFO'] = '/mode/param';
    $obj = new Test();
    $rm = $obj->mode_param(array('PATH_INFO' => 3, 'PARAM' => 'getRm'));
    print_r($rm); echo "\n";
    unset($obj);

    // Test associative array with PATH_INFO; good PATH_INFO value; 
    // PATH_INFO uses second index
    echo "Test 9: associative array arg with PATH_INFO; good pi value (not 1)\n";
    $_SERVER['PATH_INFO'] = '/mode/param';
    $obj = new Test();
    $rm = $obj->mode_param(array('PATH_INFO' => 2));
    print_r($rm); echo "\n";
    unset($obj);

    echo "Test 10: string arg, valid method\n";
    $obj = new Test();
    $obj->mode_param('testModeMethod');
    $obj->run();
    unset($obj);
?>
--EXPECT--
Test 1: no mode_param set; no args
rm
Test 2: string arg
mode
Test 3: array arg, invalid
rm
Test 4: array arg, invalid associative array
rm
Test 5: associative array arg with PATH_INFO; bad pi value
rm
Test 6: associative array arg with PATH_INFO; good pi value
Array
(
    [run_mode] => mode
)

Test 7: associative array arg with PATH_INFO; missing pi value, no PARAM
rm
Test 8: associative array arg with PATH_INFO; good pi value, PARAM
getRm
Test 9: associative array arg with PATH_INFO; good pi value (not 1)
Array
(
    [run_mode] => param
)

Test 10: string arg, valid method
method2

