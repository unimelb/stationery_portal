--TEST--
Cgiapp2::carp
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    echo "Test 1: carp a string\n";
    Cgiapp2::carp("This is a warning");

    echo "Test 2: carp an array\n";
    Cgiapp2::carp(array('test'));

    echo "Test 3: carp an integer\n";
    Cgiapp2::carp(1);

?>
--EXPECT--
Test 1: carp a string
This is a warning
Test 2: carp an array
Array
(
    [0] => test
)

Test 3: carp an integer
1

