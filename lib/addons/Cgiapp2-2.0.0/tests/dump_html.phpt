--TEST--
Cgiapp2::dump_html()
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';
    $obj = new Test();

    echo $obj->dump_html();

?>
--EXPECT--
<p>
Current Run-mode: '<b></b>'<br />
Query Parameters:</p>
<ul>
</ul>

