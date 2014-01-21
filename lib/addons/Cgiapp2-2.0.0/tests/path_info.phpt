--TEST--
Cgiapp2::path_info
--FILE--
<?php
    include_once dirname(__FILE__) . '/setup.php.inc';

    $_SERVER['REQUEST_URI']     = '/controller/action/article/123/page/2';
    $_SERVER['SCRIPT_FILENAME'] = '/tests/path_info.phpt';
    $_SERVER['DOCUMENT_ROOT']   = '/var/www/html';

    $path_info = array(
        0   => 'controller',
        1   => 'action',
        2   => 'article',
        3   => '123',
        4   => 'page',
        5   => '2',
        'controller' => 'action',
        'action'     => 'article',
        'article'    => '123',
        '123'        => 'page',
        'page'       => '2'
    );

    $returned = Cgiapp2::path_info();
    if ($path_info !== $returned) {
        echo  'not ok Paths do not match: ' . "\n";
        echo '    ' . serialize($path_info) . "\n";
        echo '    ' . serialize($returned)  . "\n";
    }

    if ('article' != Cgiapp2::path_info(2)) {
        echo 'not ok Unexpected value for index 2: ' . Cgiapp2::path_info(2) .  "\n";
    }

    if (123 != Cgiapp2::path_info('article')) {
        echo 'not ok Unexpected value for key article: ' .  Cgiapp2::path_info('article') .  "\n";
    }
?>
--EXPECT--
