--TEST--
Cgiapp2::tmpl_path
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    set_error_handler('testErrorHandler');

    // Test paths
    $path1 = '.';               // Current directory, relative path
    $path2 = dirname(__FILE__); // Current directory, full path
    $path3 = null;              // null path; should return error
    $path4 = array('path');     // array; should return error

    // Params
    $smarty_params1 = array(
        'caching' => 1
    ); // Valid params list
    $smarty_params2 = array(
        'val1'
    ); // Invalid params list

    // TESTS
    // current directory, relative path
    echo "Test 1: current directory, relative path\n";
    $obj = new Test();
    $tmpl_path1 = $obj->tmpl_path($path1);
    print_r($tmpl_path1); echo "\n";
    $tmpl_path2 = $obj->tmpl_path();
    if ($tmpl_path1 === $tmpl_path2) {
        echo "Template path initialized\n";
    }
    unset($obj);
    
    // current directory, absolute path
    echo "Test 2: current directory, absolute path\n";
    $obj = new Test();
    $tmpl_path1 = $obj->tmpl_path($path2);
    $tmpl_path2 = $obj->tmpl_path();
    if ($tmpl_path1 === $tmpl_path2) {
        echo "Template path initialized\n";
    }
    unset($obj);

    // null path
    echo "Test 3: null path\n";
    $obj = new Test();
    $tmpl_path1 = $obj->tmpl_path($path3);
    print_r($tmpl_path1); echo "\n";
    if ($obj->tmpl_path()) {
        echo "Template path initialized\n";
    }
    unset($obj);

    // array as path
    echo "Test 4: array as path\n";
    $obj = new Test();
    $tmpl_path1 = $obj->tmpl_path($path4);
    print_r($tmpl_path1); echo "\n";
    if ($obj->tmpl_path()) {
        echo "Template path initialized\n";
    }
    unset($obj);
?>
--EXPECT--
Test 1: current directory, relative path
No tmpl_path hook set
.
Template path initialized
Test 2: current directory, absolute path
No tmpl_path hook set
Template path initialized
Test 3: null path
.
Template path initialized
Test 4: array as path
.
Template path initialized

