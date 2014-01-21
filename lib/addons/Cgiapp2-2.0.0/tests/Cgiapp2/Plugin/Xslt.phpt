--TEST--
Cgiapp2_Plugin_Xslt
--SKIPIF--
<?php
    if (!class_exists('XSLTProcessor')) {
       echo 'skip PHP not compiled --with-xsl';
       die();
    }
?>
--FILE--
<?php
    @include_once dirname(__FILE__) . '/../../setup-Xslt.php.inc';
    if (!class_exists('XsltTest')) {
        echo "Unable to find XsltTest\n";
        echo 'include_path == ' . get_include_path() . "\n";
        exit();
    }

    $webapp = new XsltTest(array(
        'TMPL_PATH' => dirname(__FILE__) . '/xslt',
    ));

    $content = $webapp->testMethod();
    $error = false;
    if (!strstr($content, 'val1')) {
        echo "not ok Missing value of var1\n";
        $error = true;
    }
    if (!strstr($content, 'test item')) {
        echo "not ok XML item not transformed\n";
        $error = true;
    }

    if ($error) {
        echo $content;
    }
?>
--EXPECT--
