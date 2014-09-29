<?php

/**
 * Created by PhpStorm.
 * User: joe
 * Date: 14-6-27
 * Time: 下午2:13
 */
class SitemapManagement
{
    public $config = array(), $startpage = 1, $startnum = 0, $total = 0;

    public function __construct($options = array())
    {
        $this->config = require Yii::App()->basePath . '/config/sitemap.php';
        $this->config = array_replace_recursive($this->config, $options);
    }

    private function checkIndexPage()
    {
        //check info from sitemap-index
        $basic_detail_path = $this->config['folder'] . $this->config['filename'];
        if (file_exists($this->config['folder'] . 'sitemap-index.xml')) { //check the last page is full Or not
            $indexdata = file_get_contents($this->config['folder'] . 'sitemap-index.xml');
            preg_match_all("#<sitemap>#is", $indexdata, $match);
            $this->startpage = count($match[0]);
            if (empty($this->startpage)) $this->startpage = 1;
            $detailpath = $this->checkPage($basic_detail_path, $this->startpage);
            if (file_exists($detailpath)) {
                preg_match_all("#<url>#is", file_get_contents($detailpath), $match2);
                $cnt = count($match2[0]);
                if ($cnt == $this->config['pagesize']) {
                    $this->startpage++;
                    $this->startnum = ($this->startpage - 1) * $this->config['pagesize'];
                } else {
                    $this->startnum = ($this->startpage - 1) * $this->config['pagesize'] + $cnt;
                }
            } else {
                $this->startnum = ($this->startpage - 1) * $this->config['pagesize'];
            }

        } else {
            file_put_contents($this->config['folder'] . 'sitemap-index.xml', '');
        }

        $total = Yii::app()->db->createCommand($this->config['count_sql'])->queryRow();
        $this->total = array_shift($total);

        var_dump($this->total);
        var_dump($this->startnum);
        var_dump($this->startpage);
    }

    private function checkPage($path, $page)
    {
	        if ($page == 1) {
            return $path . '.xml';
        } else {
            return $path . '-' . $page . '.xml';
        }
    }


    public function start()
    {
        $this->checkIndexPage();
        for ($i = 0; $i < $this->config['to_generate']; $i++) {
            $a = $this->createDetailPage();
            if (!$a) break;
            $this->startpage++;
        }

    }

    public function createDetailPage()
    {
	var_dump("start ".$this->startnum.'------'.'total '.$this->total);
        if ($this->startnum >= $this->total) {
            return false;
        }
        $db = Yii::app()->db;
        $curlpath = $this->config['folder'] . $this->config['filename'];
        $curlpath = $this->checkPage($curlpath, $this->startpage);
        $pagelimits = $this->startpage * $this->config['pagesize'];
        if ($this->startnum != ($this->startpage - 1) * $this->config['pagesize'] && file_exists($curlpath)) { // if write from half way
            $cmd = 'sed -i "s/<\/urlset>//g" ' . $curlpath;
            exec($cmd);
        } else {
            file_put_contents($curlpath, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            file_put_contents($curlpath, '<?xml-stylesheet type="text/xsl" href="/'.$this->config['webdir'].'gss.xsl" ?>' . "\n", FILE_APPEND);
            file_put_contents($curlpath, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n", FILE_APPEND);
        }
        while ($this->startnum < $pagelimits) {
            $cur_limit = ($this->startnum + $this->config['buffer_size']) <= $pagelimits ? $this->config['buffer_size'] : ($pagelimits - $this->startnum);
            $data = $db->createCommand($this->config['data_sql'] . ' limit ' . $this->startnum . ', ' . $cur_limit)->queryAll();
            if (empty($data)) break;
            foreach ($data as &$d) {
                file_put_contents($curlpath, '<url><loc>' . $this->getUrl($d) . '</loc><lastmod>' . date("Y-m-d") . '</lastmod><changefreq>daily</changefreq><priority>' . $this->config['priority'] . '</priority></url>' . "\n", FILE_APPEND);
            }
            unset($d);
            $this->startnum += $cur_limit;
        }
            file_put_contents($curlpath, '</urlset>', FILE_APPEND);
        $this->updateIndexPage();
                                                                                                                        return true;
    }

    private function  getUrl($d)
    {
 $id = $d['id'];
            $link = 'http://'.$this->config['hostname'].'/'.sprintf($this->config['uri'],$id);
        return $link;
    }


    public function updateIndexPage()
    {
        $newurl = 'http://' . $this->config['hostname'] . '/' . $this->config['webdir'] . $this->config['filename'];
        $newurl = $this->checkPage($newurl, $this->startpage);
        $filepath = $this->config['folder'] . 'sitemap-index.xml';
        $olddata = file_get_contents($filepath);
        //get old data
        preg_match_all("#<loc>(.*?)</loc>\s*<lastmod>(.*?)</lastmod>#is", $olddata, $matches);
        $tmp = array();
        foreach ($matches[1] as $k => $link) {
            $tmp[$link] = $matches[2][$k];
        }


        $tmp[$newurl] = date(DATE_ATOM);
        file_put_contents($filepath, '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="/'.$this->config['webdir'].'/gss.xsl" ?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");

        foreach ($tmp as $link => $date) {
            file_put_contents($filepath, '<sitemap><loc>' . $link . '</loc><lastmod>' . $date . '</lastmod></sitemap>' . "\n", FILE_APPEND);
        }
        file_put_contents($filepath, '</sitemapindex>', FILE_APPEND);
    }


}


