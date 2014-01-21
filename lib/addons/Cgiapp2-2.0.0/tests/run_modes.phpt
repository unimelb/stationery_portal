--TEST--
Cgiapp2::run_modes
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');
    
    // Empty list
    echo "Test 1: empty list\n";
    $obj = new Test();
    $rm = $obj->run_modes();
    print_r($rm); echo "\n";
    unset($obj); unset($rm);

    // String
    echo "Test 2: string\n";
    $obj = new Test();
    $rm = $obj->run_modes('test');
    print_r($rm); echo "\n";
    unset($obj); unset($rm);

    // Associative array
    echo "Test 3: associative array\n";
    $obj = new Test();
    $rm = $obj->run_modes(array('test1' => 'method1', 'test2' => 'method2'));
    print_r($rm); echo "\n";
    unset($obj); unset($rm);

    // Indexed array without flag
    echo "Test 4: indexed array without flag\n";
    $obj = new Test();
    $rm = $obj->run_modes(array('test1', 'test2'));
    print_r($rm); echo "\n";
    unset($obj); unset($rm);

    // Indexed array with flag; bad indices
    echo "Test 5: indexed array with flag, bad indices\n";
    $obj = new Test();
    $rm = $obj->run_modes(array('test1', 'test2'), true);
    print_r($rm); echo "\n";
    unset($obj); unset($rm);

    // Indexed array with flag; good indices
    echo "Test 6: indexed array with flag, good indices\n";
    $obj = new Test();
    $rm = $obj->run_modes(array('method1', 'method2'), true);
    print_r($rm); echo "\n";
    unset($obj); unset($rm);

?>
--EXPECT--
Test 1: empty list
Array
(
)

Test 2: string
Odd number of elements passed to run_modes().  Not a valid hash
Array
(
)

Test 3: associative array
Array
(
    [test1] => method1
    [test2] => method2
)

Test 4: indexed array without flag
Array
(
    [test1] => test2
)

Test 5: indexed array with flag, bad indices
Array
(
)

Test 6: indexed array with flag, good indices
Array
(
    [method1] => method1
    [method2] => method2
)
 
