--TEST--
Cgiapp2::param
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    // Test no parameters
    $obj = new Test();

    echo "Test 1: No params, empty args\n";
    $params = $obj->param();
    print_r($params); echo "\n";

    echo "Test 2: No params, single arg\n";
    $data = $obj->param('test');
    print_r($data); echo "\n";

    echo "Test 3: No params, object arg\n";
    $data = $obj->param($obj);
    print_r($data); echo "\n";

    echo "Test 4: No params, odd-elemented array arg\n";
    $data = $obj->param(array('val1', 'val2', 'val3'));
    print_r($data); echo "\n";

    echo "Test 5: No params, two args, first non-string\n";
    $data = $obj->param($obj, 'val2');
    print_r($data); echo "\n";

    echo "Test 6: No params, three args\n";
    $data = $obj->param('var1', 'val1', 'val2');
    print_r($data); echo "\n";

    echo "Test 7: No params, two args, valid\n";
    $data = $obj->param('var1', 'val1');
    print_r($data); echo "\n";

    echo "Test 8: 1 param, assoc array arg, valid\n";
    $data = $obj->param(array('var2' => 'val2', 'var1' => '1lav'));
    print_r($data); echo "\n";

    echo "Test 9: 2 params, no args\n";
    $data = $obj->param();
    print_r($data); echo "\n";

    echo "Test 10: 2 params, array arg, valid\n";
    $data = $obj->param(array('var3', 'val3'));
    print_r($data); echo "\n";

    echo "Test 11: 3 params, no args\n";
    $data = $obj->param();
    print_r($data); echo "\n";

    echo "Test 12: 3 params, single string arg, valid\n";
    $data = $obj->param('var1');
    print_r($data); echo "\n";

    echo "Test 13: 3 params, single string arg, invalid\n";
    $data = $obj->param('var5');
    print_r($data); echo "\n";

?>
--EXPECT--
Test 1: No params, empty args
Array
(
)

Test 2: No params, single arg

Test 3: No params, object arg
Bad arguments (object) sent to param()

Test 4: No params, odd-elemented array arg
Bad arguments (array) sent to param()

Test 5: No params, two args, first non-string
Bad key (non-string) sent to param()

Test 6: No params, three args
Too many arguments sent to param()

Test 7: No params, two args, valid
1
Test 8: 1 param, assoc array arg, valid
1
Test 9: 2 params, no args
Array
(
    [VAR1] => 1lav
    [VAR2] => val2
)

Test 10: 2 params, array arg, valid
1
Test 11: 3 params, no args
Array
(
    [VAR1] => 1lav
    [VAR2] => val2
    [VAR3] => val3
)

Test 12: 3 params, single string arg, valid
1lav
Test 13: 3 params, single string arg, invalid


