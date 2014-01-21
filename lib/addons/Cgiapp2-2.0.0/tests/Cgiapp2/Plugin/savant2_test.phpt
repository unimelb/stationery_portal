--TEST--
Cgiapp2_Plugin_Savant2
--SKIPIF--
<?php
    @include_once 'Savant2.php';
    if (!class_exists('Savant2')) {
       echo 'skip';
       die();
    }

    @include_once dirname(__FILE__) . '/../../setup-Savant2.php.inc';
    if (!class_exists('Savant2Test')) {
       echo 'skip';
       die();
    }
?>
--FILE--
<?php
    include_once dirname(__FILE__) . '/../../setup-Savant2.php.inc';

    $webapp = new Savant2Test(array(
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

