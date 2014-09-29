<?php 

class SphinxCommand extends CConsoleCommand{
	


    public function isRunning() {
        // 判断要不要运行
        $thisCmdPattern = '/^php .*?' . preg_quote(implode(' ', $_SERVER['argv']), '/') . '$/';
        exec('ps aux', $out);
        $out = array_slice($out, 1);
        $count = 0;
        foreach ($out as $line) {
            $parts = preg_split('/\s+/', $line);
            $parts = array_slice($parts, 10);
            $cmd = trim(implode(' ', $parts));

            if (preg_match($thisCmdPattern, $cmd)) {
                $count++;
            }
        }
        return $count > 1;
    }

    public function ActionCheck(){
        $obj = new SphinxManagement(array());
        $obj->checkSearchd();
    }

    public function ActionIndexer($count=3){
        $obj = new SphinxManagement(array());
        $obj->checkIndexer($count);
    }

    public function ActionGenerate(){
        if ($this->isRunning()) {
            return;
        }
        set_time_limit(0);
        ini_set("memory_limit","512M");

        $obj = new SphinxManagement(array());
        $obj->generateConf();

    }

    public function ActionKill(){
        exec("ps aux | grep stream |awk '{print $2}'",$matches);

        if(!empty($matches)){
            exec("kill -9 ".implode(" ",$matches));
        }
    }

}
	