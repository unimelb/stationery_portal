--TEST--
Cgiapp2::is_assoc_array
--FILE--
<?php
    @include_once 'Cgiapp2.class.php';
    if (!class_exists('Cgiapp2')) {
        include_once dirname(__FILE__) . '/../Cgiapp2.class.php';
    }
    $array = array('key1', 'val1', 'key2', 'val2');

    echo "Test 1: indexed array\n";
    if (Cgiapp2::is_assoc_array($array)) {
        echo "True\n";
    } else {
        echo "False\n";
    }

    echo "Test 2: associative array\n";
    $array = array('key1' => 'val1', 'key2' => 'val2');
    if (Cgiapp2::is_assoc_array($array)) {
        echo "True\n";
    } else {
        echo "False\n";
    }

    echo "Test 3: associative array with numeric indices\n";
    $array = array(0 => 'val1', 1 => 'val2');
    if (Cgiapp2::is_assoc_array($array)) {
        echo "True\n";
    } else {
        echo "False\n";
    }

    echo "Test 4: associative array with mixed indices\n";
    $array = array(0 => 'val1', 'one' => 'val2');
    if (Cgiapp2::is_assoc_array($array)) {
        echo "True\n";
    } else {
        echo "False\n";
    }

?>
--EXPECT--
Test 1: indexed array
False
Test 2: associative array
True
Test 3: associative array with numeric indices
False
Test 4: associative array with mixed indices
True

