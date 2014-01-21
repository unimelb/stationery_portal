--TEST--
Cgiapp2::dump()
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    $obj = new Test();

    echo $obj->dump();
?>
--EXPECT--
Current Run-mode: ''

Query Parameters:
Array
(
)
