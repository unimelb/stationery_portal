--TEST--
Cgiapp2::array_to_hash
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';

    $array1 = array('key1', 'val1', 'key2', 'val2');
    $array2 = array('key1', 'val1', 'key2', 'val2', 'var3');

    echo "Test 1: Create associative array from even-numbered array\n";
    $newarray = Cgiapp2::array_to_hash($array1);
    print_r($newarray);
    unset($newarray);

    echo "Test 2: Create associative array from odd-numbered array\n";
    $newarray = Cgiapp2::array_to_hash($array2);
    print_r($newarray);
    unset($newarray);

    echo "Test 3: Create associative array from null data\n";
    $newarray = Cgiapp2::array_to_hash();
    print_r($newarray);
    unset($newarray);

    echo "Test 4: Create associative array from object data\n";
    $obj = new Test();
    $newarray = Cgiapp2::array_to_hash($obj);
    print_r($newarray);
    unset($newarray);

?>
--EXPECT--
Test 1: Create associative array from even-numbered array
Array
(
    [key1] => val1
    [key2] => val2
)
Test 2: Create associative array from odd-numbered array
Test 3: Create associative array from null data
Test 4: Create associative array from object data

