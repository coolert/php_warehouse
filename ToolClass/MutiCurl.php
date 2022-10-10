<?php

/**
 * PHP多线程Curl
 */
class MutiCurl
{
    //urls needs to be fetched
    public $targets = array();
    //parallel running curl threads
    public $threads = 10;
    //curl options
    public $curlOpt = array();
    //callback function
    public $callback = null;
    //debug ,will show log using echo
    public $debug = true;

    //multi curl handler
    private $mh = null;
    //curl running signal
    private $runningSig = null;


    public function __construct() {
        $this->mh = curl_multi_init();
        $this->curlOpt             = array(
            CURLOPT_HEADER         => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
        );
        $this->callback = function($html) {
            echo md5($html);
            echo "fetched";
            echo "\r\n";
        };
    }

    /**
     * Set request urls.
     *
     * @param $targets
     *
     * @return $this
     */
    public function setTargets($targets) {
        $this->targets = $targets;
        return $this;
    }

    /**
     * Set thread pool capacity.
     *
     * @param $threads
     *
     * @return $this
     */
    public function setThreads($threads) {
        $this->threads = intval($threads);
        return $this;
    }

    /**
     * Set callback which is used to solve the response.
     *
     * @param $func
     *
     * @return $this
     */
    public function setCallback($func) {
        $this->callback = $func;
        return $this;
    }

    /**
     * start running
     */
    public function run() {
        $this->initPool();
        $this->runCurl();
    }

    /*
     * run multi curl
     */
    private function runCurl() {
        do{
            //start request thread and wait for return,if there's no return in 1s,continue to add request thread
            do{
                curl_multi_exec($this->mh, $this->runningSig);
                $this->log("exec results...running sig is".$this->runningSig);
                $return = curl_multi_select($this->mh, 1.0);
                if($return > 0){
                    $this->log("there is a return...$return");
                    break;
                }
                unset($return);
            } while ($this->runningSig>0);

            //if there is return,read it
            while($returnInfo = curl_multi_info_read($this->mh)) {
                $handler = $returnInfo["handle"];
                if($returnInfo["result"] == CURLE_OK) {
                    $url = curl_getinfo($handler, CURLINFO_EFFECTIVE_URL);
                    $this->log($url."returns data");
                    $callback = $this->callback;
                    $callback(curl_multi_getcontent($handler));
                } else {
                    $url = curl_getinfo($handler, CURLINFO_EFFECTIVE_URL);
                    $this->log("$url fetch error.".curl_error($handler));
                }
                curl_multi_remove_handle($this->mh, $handler);
                curl_close($handler);
                unset($handler);
                //add new targets into curl thread
                if($this->targets) {
                    $threadsIdel = $this->threads - $this->runningSig;
                    $this->log("idel threads:".$threadsIdel);
                    if($threadsIdel < 0) continue;
                    for($i=0;$i<$threadsIdel;$i++) {
                        $t = array_pop($this->targets);
                        if(!$t) continue;
                        $task = curl_init($t);
                        curl_setopt_array($task, $this->curlOpt);
                        curl_multi_add_handle($this->mh, $task);
                        $this->log("new task adds!".$t);
                        $this->runningSig += 1;
                        unset($task);
                    }

                } else {
                    $this->log("targets all finished");
                }
            }
        }while($this->runningSig);
    }

    /*
     * init multi curl pool
     */
    private function initPool() {
        if(count($this->targets) < $this->threads) $this->threads = count($this->targets);
        //init curl handler pool ...
        for($i=1;$i<=$this->threads;$i++) {
            $target = array_pop($this->targets);
            if (!in_array($target['method'], ['GET', 'POST'])) {
                throw new Exception('Wrong request mode');
            }
            $set_opt = [
                CURLOPT_CUSTOMREQUEST => $target['method'],
                CURLOPT_URL => $target['url'],
            ];
            if (isset($target['head'])) {
                $set_opt[CURLOPT_HTTPHEADER] = $target['head'];
            }
            if ($target['method'] == 'POST') {
                if (isset($target['param'])) {
                    $set_opt[CURLOPT_POSTFIELDS] = json_encode($target['param']);
                    $set_opt[CURLOPT_POST] = 1;
                }
            }
            $task = curl_init();
            $opt = array_merge($this->curlOpt, $set_opt);
            curl_setopt_array($task, $opt);
            curl_multi_add_handle($this->mh, $task);
            $this->log("init pool thread one");
            unset($task);
        }
        $this->log("init pool done");
    }

    private function log($log) {
        if(!$this->debug) return false;
        ob_start();
        echo "---------- ".date("Y-m-d H:i",time())."-------------";
        if(is_array($log)) {
            echo json_encode($log);
        } else {
            echo $log;
        }
        $m = memory_get_usage();
        echo "memory:".intval($m/1024)."kb\r\n";
        echo "\r\n";
        flush();
        ob_end_flush();
        unset($log);
    }
    public function __destruct(){
        $this->log("curl ends.");
        curl_multi_close($this->mh);
    }
}