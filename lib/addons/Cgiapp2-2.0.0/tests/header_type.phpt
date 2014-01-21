--TEST--
Cgiapp2::header_type
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    $idx   = 0;
    $types = array('header', 'redirect', 'none', 'bogus');
    foreach ($types as $type) {
        $idx++;
        echo "Test $idx: header $type\n";
        $obj = new Test();
        $data = $obj->header_type($type);
        print_r($data);
        echo "\n";
        unset($obj);
    }

    echo "Test 5: Pass an array\n";
    $obj = new Test();
    $data = $obj->header_type($types);
    print_r($data);
    echo "\n";
    unset($obj);
?>
--EXPECT--
Test 1: header header
header
Test 2: header redirect
redirect
Test 3: header none
none
Test 4: header bogus
Invalid header_type 'bogus'
header
Test 5: Pass an array
header

