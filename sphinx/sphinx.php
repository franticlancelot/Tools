<?php
/**
 * Created by PhpStorm.
 * User: joe
 * Date: 14-6-27
 * Time: 下午2:30
 */

return array(
    'index-template' =>
            array(
                'docinfo'  => 'extern',
                'mlock'    =>  0,
                'morphology' =>  'none',
                '# stopwords'  =>  '/opt/stopwords.txt',
                'min_word_len' => 1,
                'charset_type' => 'utf-8',
                'ngram_len'    => 1,
                'ngram_chars'  => 'U+3000..U+2FA1F',
                'html_strip'   => 1,
                '# html_index_attrs' => 'img=alt,title; a=title;'
            ),

    'indexer'  =>
            array(
                'mem_limit' => '300M',
            ),

    'searchd'  =>
            array(
                'listen'  => array('127.0.0.1'),
                'dist_threads' => 8,  //cpu cores
                'workers'   => 'fork',  //prefork , threads
                'thread_stack' => '256K',
                'log'     =>  '',
                'binlog_path' => '# disable logging',
                'binlog_max_log_size' => "16M",
                'read_timeout' => 5,
                'max_children' => 0,
                'pid_file' => '',
                'max_matches' => 1000,
                'seamless_rotate' => 1,
                'preopen_indexes' => 0,
                'unlink_old' => 1,
                'mysql_version_string' => '5.0.37',
                'prefork_rotation_throttle' => 50
            ),

    'source' =>
            array(
                'type' => 'mysql',
                'sql_host' => '192.168.1.50',
                'sql_user' => 'joe',
                'sql_pass' => 'joe123',
                'sql_db'   => 'worldcup',
                'sql_query_pre' => array('SET NAMES UTF8'),
                'sql_query' => 'SELECT rowid,content,if(content_image is null,0,1) as has_media, created_time FROM stream',
                'sql_attr_uint'=> array("has_media", "created_time"),
            ),

    'index' =>
            array(
                'source' => '',
                'path'   => '',
            ),


    'options' =>
            array(
                'tablename' => 'stream',
                'filename'  => 'sphinx_stream',
                'totalquery' => 'select count(1) from stream',
                'tablesize' => 500000,
                'filesize'  => 10, //tables
                'path' => '/home/joe/webroot/worldcup-2014/runtime/sphinx/',
                'portstart' => 20666,
                'binpath' => '/opt/sphinx-2.0.9/bin/'
            ),
);