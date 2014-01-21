--TEST--
Cgiapp2::query
--SKIPIF--
<?php if (php_sapi_name() == 'cli') echo 'skip'; ?>
--GET--
var1=val1&var2=val2
--POST--
var3=val3&var2=val4&var4=val5
--FILE--
<?php
    @include_once 'Cgiapp2.class.php';
    if (!class_exists('Cgiapp2')) {
        include_once dirname(__FILE__) . '/../Cgiapp2.class.php';
    }
   
    // Test retrieving
    echo "Test 1: Retrieve query\n";
    $q =& Cgiapp2::query();
    print_r($q); echo "\n";

    // Test setting
    echo "Test 2: Set value in query and re-fetch\n";
    $q['var2'] = 'val2';
    $query =& Cgiapp2::query();
    print_r($query); echo "\n";
?>
--EXPECT--
Test 1: Retrieve query
Array
(
    [var1] => val1
    [var2] => val4
    [var3] => val3
    [var4] => val5
)

Test 2: Set value in query and re-fetch
Array
(
    [var1] => val1
    [var2] => val2
    [var3] => val3
    [var4] => val5
)

