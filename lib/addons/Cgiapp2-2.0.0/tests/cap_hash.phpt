--TEST--
Cgiapp2::cap_hash
--FILE--
<?php
    @include_once 'Cgiapp2.class.php';
    if (!class_exists('Cgiapp2')) {
        include_once dirname(__FILE__) . '/../Cgiapp2.class.php';
    }
    $array1 = array(
        'var1' => 'val1',
        'VAR2' => 'val2',
        'vAr3' => 'val3'
    );
    $array2 = array('var1', 'VAR2', 'vAr3');

    echo "Test 1: cap_hash an associative array\n";
    $newarray = Cgiapp2::cap_hash($array1);
    print_r($newarray);
    unset($newarray);

    echo "Test 2: cap_hash an array\n";
    $newarray = Cgiapp2::cap_hash($array2);
    print_r($newarray);
    unset($newarray);

    echo "Test 3: cap_hash a null\n";
    $newarray = Cgiapp2::cap_hash();
    print_r($newarray);
    unset($newarray);

    echo "Test 4: cap_hash a string\n";
    $newarray = Cgiapp2::cap_hash('test');
    print_r($newarray);
    unset($newarray);

?>
--EXPECT--
Test 1: cap_hash an associative array
Array
(
    [VAR1] => val1
    [VAR2] => val2
    [VAR3] => val3
)
Test 2: cap_hash an array
Test 3: cap_hash a null
Test 4: cap_hash a string

