<?php
    if (!@include_once dirname(__FILE__) . '/../Cgiapp2.class.php') {
        if (!@include_once 'Cgiapp2.class.php') {
            die('Could not find class Cgiapp2');
        }
    }
    if (!@include_once dirname(__FILE__) . '/../Cgiapp2/Plugin/Xslt.class.php') {
        if (!@include_once 'Cgiapp2/Plugin/Xslt.class.php') {
            die('Could not find class Cgiapp2_Plugin_Xslt');
        }
    }

    class XsltTest extends Cgiapp2
    {
        function setup()
        {
        }

        function testMethod()
        {
            $xml =<<<EOX
<?xml version="1.0" standalone="yes"?>
<root>
    <item>Test item</item>
</root>
EOX;
            $this->tmpl_assign('var1', 'val1');
            return $this->load_tmpl(array($xml, 'test.xslt'));
        }
    }


