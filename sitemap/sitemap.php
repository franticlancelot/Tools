<?php

/**
 * Created by PhpStorm.
 * User: joe
 * Date: 14-6-27
 * Time: 下午2:30
 */

return array(
    'folder' => '/home/kolalo/public_html/software/smxml/',
    'webdir' => 'smxml/',
    'pagesize' => 25000,
    'filename' => 'detail',
    'count_sql' => 'select count(1) from stream',
    'data_sql' => "select * from stream ",
    "to_generate" => 1,
    "buffer_size" => '2000',
    'priority' => 0.5,
    'hostname' => 'software.kolalo.com',
    'uri' => 'new/%s',
);
