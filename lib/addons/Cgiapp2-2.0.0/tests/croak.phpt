--TEST--
Cgiapp2::croak
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    echo "Test 1: croak a string\n";
    Cgiapp2::croak("This is a fatal error");

    echo "Test 2: croak an array\n";
    Cgiapp2::croak(array('test'));

    echo "Test 3: croak an integer\n";
    Cgiapp2::croak(1);

?>
--EXPECT--
Test 1: croak a string
This is a fatal error
Test 2: croak an array
Array
(
    [0] => test
)

Test 3: croak an integer
1

