--TEST--
Cgiapp2::new_hook()
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';

    echo "Test 1: non-string hook\n";
    if (Cgiapp2::new_hook(array())) {
        echo "Failed\n";
    } else {
        echo "Success\n";
    }

    echo "Test 2: invalid class\n";
    if (Cgiapp2::new_hook('somehook', array('someValue'))) {
        echo "Failed\n";
    } else {
        echo "Success\n";
    }

    echo "Test 3: valid hook, no class association\n";
    if (Cgiapp2::new_hook('somehook')) {
        echo "Success\n";
    } else {
        echo "Failed\n";
    }

    echo "Test 4: valid hook, class name association\n";
    if (Cgiapp2::new_hook('somehook', 'SomeClass')) {
        echo "Success\n";
    } else {
        echo "Failed\n";
    }

    echo "Test 5: valid hook, object association\n";
    $obj = new Test();
    if (Cgiapp2::new_hook('somehook', $ob)) {
        echo "Success\n";
    } else {
        echo "Failed\n";
    }
?>
--EXPECT--
Test 1: non-string hook
Success
Test 2: invalid class
Success
Test 3: valid hook, no class association
Success
Test 4: valid hook, class name association
Success
Test 5: valid hook, object association
Success

