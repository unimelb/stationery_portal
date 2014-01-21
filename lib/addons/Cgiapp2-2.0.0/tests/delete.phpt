--TEST--
Cgiapp2::delete
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    // Test no parameters
    $obj = new Test();

    echo "Test 1: No params, empty args\n";
    $data = $obj->delete();
    print_r($data); echo "\n";

    echo "Test 2: No params, single arg\n";
    $data = $obj->delete('test');
    print_r($data); echo "\n";

    echo "Test 3: No params, object arg\n";
    $data = $obj->delete($obj);
    print_r($data); echo "\n";

    echo "Test 4: Single param, empty args\n";
    $obj->param('var1', 'val1');
    $data = $obj->delete();
    print_r($data); echo "\n";

    echo "Test 5: Single param, single arg, invalid\n";
    $obj->param('var1', 'val1');
    $data = $obj->delete('test_something');
    print_r($data); echo "\n";

    echo "Test 6: Single param, single arg, object\n";
    $obj->param('var1', 'val1');
    $data = $obj->delete($obj);
    print_r($data); echo "\n";

    echo "Test 7: Single param, single arg, valid\n";
    $obj->param('var1', 'val1');
    $data = $obj->delete('var1');
    print_r($data); echo "\n";
    if (!$data) {
        print_r($obj->_PARAMS);
    }

?>
--EXPECT--
Test 1: No params, empty args
Cannot call delete without params

Test 2: No params, single arg
Cannot call delete without params

Test 3: No params, object arg
Cannot call delete without params

Test 4: Single param, empty args
Called delete without key

Test 5: Single param, single arg, invalid

Test 6: Single param, single arg, object
Called delete without key

Test 7: Single param, single arg, valid
1

