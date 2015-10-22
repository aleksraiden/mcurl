<?php

namespace KHR\Curl;

use KHR\Curl\structure\loop\Pool;
use KHR\Curl\structure\loop\Request;
use Psr\Http\Message\RequestInterface;

class Loop {

    /**
     * @var Pool[]
     */
    private $pools = [];

    /**
     * @var Request[]
     */
    private $requests = [];

    private $mh;

    public function __construct() {
        $this->mh = curl_multi_init();
    }

    public function addPool(Pool $pool) {
        if (!isset($this->pools[$pool->id])) {
            $this->pools[$pool->id] = $pool;
        }
        return $this->run();
    }

    public function run() {
        $min_timeout = null;
        $unset_pools = [];

        foreach($this->pools as $pool_id => $pool) {
            if (!$pool->hasRequests()) {
                $unset_pools[] = $pool_id;
                continue;
            }

            /**
             * @var $requests RequestInterface
             * @var $timeout float
             */
            list($requests, $timeout) = $pool->getRequests();
            foreach($requests as $request) {
                $this->addRequest($request, $pool_id);
            }

            if ($timeout > 0 && (!isset($min_timeout) || $min_timeout > $timeout)) {
                $min_timeout = $timeout;
            }
        }

        foreach($unset_pools as $pool_id) {
            unset($this->pools[$pool_id]);
        }

        if (isset($min_timeout)) {
            $this->sleep($min_timeout);
        }
    }

    private function addRequest($request, $pool_id) {
        $req = new RequestLoop();
        $req->id = spl_object_hash($req);
        $req->request = $request;
        $req->pool_id = $pool_id;
        $this->requests[$req->id] = $req;
    }

    private function sleep($timeout) {
        usleep($timeout);
    }

}