<?php

/**
 * Created by PhpStorm.
 * User: joe
 * Date: 14-6-27
 * Time: 下午2:13
 */
class SphinxManagement
{
    public $config = array(), $offset = 0, $port = 0, $index_number = 0, $cur_agent = '', $finalagent = array(), $maxindex = 0,
        $total = 0;

    public function __construct($options = array())
    {
        $this->config = require Yii::App()->basePath . '/config/sphinx.php';
        $this->config = array_replace_recursive($this->config, $options);
        $this->getCountInfo();
    }

    /**
     * get the total pages count
     */
    private function getCountInfo()
    {
        $db = Yii::app()->db;
        $count = $db->createCommand($this->config['options']['totalquery'])->queryRow();
        $count = array_shift($count);

        $this->total = ceil($count / $this->config['options']['tablesize'] / $this->config['options']['filesize']);
        $this->maxindex = ceil($count / $this->config['options']['tablesize']);
    }

    public function generateConf()
    {
        $this->checkPath();
        for ($page = 1; $page <= $this->total; $page++) {
            $this->generateEveryConf($page);
            $this->finalagent[] = trim($this->cur_agent, ',');
        }

        $this->generateAgent();

    }

    public function checkSearchd()
    {
        $cmd = 'ls ' . $this->config['options']['path'] . 'config/ -al | awk ' . "'{print $9}'";
        exec($cmd, $output);
        foreach ($output as $out) {
            if (preg_match("/stream/i", $out)) {
                $cmd = $this->config['options']['binpath'] . 'searchd -c ' . $this->config['options']['path'] . 'config/' . $out;
            }
            var_dump($cmd);
            exec($cmd, $out);
            var_dump($out);
        }
    }


    public function checkIndexer($count = 3)
    {
        $this->generateConf();
        var_dump($this->maxindex);
        $cmd = 'ls ' . $this->config['options']['path'] . 'config/ -al | awk ' . "'{print $9}'";
        exec($cmd, $output);
        array_pop($output);
        $finaloutput = array();


        if ($count == 'all') {
            foreach ($output as $out) {
                if (preg_match("/stream/i", $out)) {
                    $finaloutput[] = $this->config['options']['binpath'] . 'indexer -c ' . $this->config['options']['path'] . 'config/' . $out . ' --all --rotate';
                }
            }
        } else {
            $gopages = array();
            for ($i = 0; $i < 2; $i++) {
                $gopages[] = $this->config['options']['tablename'] . ($this->maxindex - $i);
            }
            $output = array_slice($output, -2);
            foreach ($output as $out) {
                foreach ($gopages as $p) {
                    $finaloutput[] = $this->config['options']['binpath'] . 'indexer -c ' . $this->config['options']['path'] . 'config/' . $out . ' ' . $p . ' --rotate';
                }
            }

        }
        foreach ($finaloutput as $out) {
            var_dump($out);
            exec($out,$details);
            var_dump($details);
        }

    }


    public function generateAgent()
    {
        $this->port++;
        $filename = $this->config['options']['path'] . 'config/' . $this->config['options']['filename'] . '-agent.conf';
        $this->filenames[] = $filename;
        file_put_contents($filename, '');
        $this->generateIndexTemplate($filename);
        $this->generateIndexer($filename);
        $this->generateSearchd($filename, 'agent');
        $sourcename = 'index stream';
        $configs = array(
            'type' => 'distributed',
            'agent' => $this->finalagent,
            'agent_connect_timeout' => 1000,
            'agent_query_timeout' => 2000,
        );

        $this->generateSection($filename, $sourcename, $configs);
    }


    public function checkPath()
    {
        $this->config['searchd']['log'] = $this->config['options']['path'] . 'log/' . $this->config['options']['filename'] . '-%s.log';
        $this->config['searchd']['pid_file'] = $this->config['options']['path'] . 'log/' . $this->config['options']['filename'] . '-%s.pid';

        $this->config['index']['path'] = $this->config['options']['path'] . 'data/' . $this->config['options']['tablename'] . '-%s';

    }


    public function generateEveryConf($page)
    {
        $this->port = $this->config['options']['portstart'] + $page;
        $filename = sprintf($this->config['options']['path'] . 'config/' . $this->config['options']['filename'] . '-%d.conf', $page);
        $this->filenames[] = $filename;
        file_put_contents($filename, '');
        $this->generateIndexTemplate($filename);
        $this->generateIndexer($filename);
        $this->generateSearchd($filename, $page);
        for ($i = 1; $i <= $this->config['options']['filesize']; $i++) {
            $this->index_number++;
            $this->generateTables($filename);
        }
    }


    public function generateSection($filename, $sectionname, $sectioninfo)
    {
        file_put_contents($filename, $sectionname . "{\n", FILE_APPEND);
        foreach ($sectioninfo as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vv) {
                    file_put_contents($filename, "   " . $k . ' = ' . $vv . "\n", FILE_APPEND);
                }
            } else {
                file_put_contents($filename, "   " . $k . ' = ' . $v . "\n", FILE_APPEND);
            }
        }
        file_put_contents($filename, "}\n\n\n", FILE_APPEND);
    }


    public function generateIndexTemplate($filename)
    {
        $this->generateSection($filename, 'index template', $this->config['index-template']);
    }


    public function generateIndexer($filename)
    {
        $this->generateSection($filename, 'indexer', $this->config['indexer']);
    }

    public function generateSearchd($filename, $page)
    {
        $configs = $this->config['searchd'];
        foreach ($configs['listen'] as &$ip) {
            if (!isset($cip)) {
                $cip = $ip . ':' . $this->port;
                $this->cur_agent = $cip . ':';
            }
            if($page == 'agent'){
                $ip = $ip . ':' . $this->port . ':mysql41';
            }else{
                $ip = $ip . ':' . $this->port;
            }
        }

        $configs['log'] = sprintf($configs['log'], $page);
        $configs['pid_file'] = sprintf($configs['pid_file'], $page);
        $this->generateSection($filename, 'searchd', $configs);
    }

    public function generateTables($filename)
    {
        $tablename = $this->config['options']['tablename'] . $this->index_number;
        $configs = $this->config['source'];
        $configs2 = $this->config['index'];
        $sourcename = 'source ' . $tablename;
        $indexname = 'index ' . $tablename . ': template';

        $configs['sql_query'] .= " limit " . $this->offset . ', ' . $this->config['options']['tablesize'];
        $this->offset += $this->config['options']['tablesize'];

        $configs2['source'] = $tablename;
        $this->cur_agent .= $tablename . ',';
        $configs2['path'] = sprintf($configs2['path'], $this->index_number);
        $this->generateSection($filename, $sourcename, $configs);
        $this->generateSection($filename, $indexname, $configs2);
    }


}