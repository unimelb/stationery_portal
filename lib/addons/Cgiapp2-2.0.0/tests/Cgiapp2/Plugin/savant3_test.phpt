--TEST--
Cgiapp2_Plugin_Savant3
--SKIPIF--
<?php
    @include_once 'Savant3.php';
    if (!class_exists('Savant3')) {
       echo 'skip';
       die();
    }

    @include_once dirname(__FILE__) . '/../../setup-Savant3.php.inc';
    if (!class_exists('Savant3Test')) {
       echo 'skip';
       die();
    }
?>
--FILE--
<?php
    include_once dirname(__FILE__) . '/../../setup-Savant3.php.inc';

    $webapp = new Savant3Test(array(
        'TMPL_PATH' => dirname(__FILE__) . '/savant_tmpl'
    ));

    $content = $webapp->testMethod();
    if (strstr($content, 'val1')) {
        echo "Success\n";
    } else {
        echo "Failed\n";
        echo "Returned content: \n";
    }
?>
--EXPECT--
Success

