--TEST--
Cgiapp2::header_props
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';

    // This is an associative array; use for first test case
    $array1 = array('key1' => 'val1', 'key2' => 'val2');

    // This is a regular indexed array; use for second test case
    $array2 = array('key1', 'val1', 'key2', 'val2');

    // Use this array for adding new properties
    $array3 = array('key3' => 'val3', 'key2' => 'val4');

    // Use these values to test for bad data sent to method
    $array4 = 'notanarray';
    $array5 = array('key1', 'val1', 'key2', 'val2', 'key3');

    // Test associative array passing
    echo "Test 1: pass associative array\n";
    $obj = new Test();
    $data = $obj->header_props($array1);
    print_r($data);

    // Test indexed array passing
    echo "Test 2: pass indexed array\n";
    $obj = new Test();
    $data = $obj->header_props($array2);
    print_r($data);

    // Test adding an array to the system
    echo "Test 3: add an array\n";
    $data = $obj->header_props($array3);
    print_r($data);

    // TEST BAD DATA
    set_error_handler('testErrorHandler');

    // single element, non-array
    echo "Test 4: pass a single, non-array element\n";
    unset($obj);
    $obj = new Test();
    $data = $obj->header_props($array4);
    echo "\n";

    // array with odd number of elements
    echo "Test 5: pass an array with odd number of elements\n";
    $obj = new Test();
    $data = $obj->header_props($array5);
    echo "\n";
?>
--EXPECT--
Test 1: pass associative array
Array
(
    [key1] => val1
    [key2] => val2
)
Test 2: pass indexed array
Array
(
    [key1] => val1
    [key2] => val2
)
Test 3: add an array
Array
(
    [key1] => val1
    [key2] => val4
    [key3] => val3
)
Test 4: pass a single, non-array element
Bad data passed to header_props()

Test 5: pass an array with odd number of elements
Bad data passed to header_props()

