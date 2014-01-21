--TEST--
Cgiapp2::s_delete
--FILE--
<?php
    session_start();
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    // Test no parameters
    $obj = new Test();

    echo "Test 1: No params, empty args\n";
    $data = $obj->s_delete();
    print_r($data); echo "\n";

    echo "Test 2: No params, single arg\n";
    $data = $obj->s_delete('test');
    print_r($data); echo "\n";

    echo "Test 3: No params, object arg\n";
    $data = $obj->s_delete($obj);
    print_r($data); echo "\n";

    echo "Test 4: Single param, empty args\n";
    $obj->s_param('var1', 'val1');
    $data = $obj->s_delete();
    print_r($data); echo "\n";

    echo "Test 5: Single param, single arg, invalid\n";
    $obj->s_param('var1', 'val1');
    $data = $obj->s_delete('test_something');
    print_r($data); echo "\n";

    echo "Test 6: Single param, single arg, object\n";
    $obj->s_param('var1', 'val1');
    $data = $obj->s_delete($obj);
    print_r($data); echo "\n";

    echo "Test 7: Single param, single arg, valid\n";
    $obj->s_param('var1', 'val1');
    $data = $obj->s_delete('var1');
    print_r($data); echo "\n";
    if (!$data) {
        print_r($obj->_PARAMS);
    }

?>
--EXPECT--
Test 1: No params, empty args
Bad key passed to s_delete

Test 2: No params, single arg

Test 3: No params, object arg
Bad key passed to s_delete

Test 4: Single param, empty args
Bad key passed to s_delete

Test 5: Single param, single arg, invalid

Test 6: Single param, single arg, object
Bad key passed to s_delete

Test 7: Single param, single arg, valid
1

