<?php

namespace KHR\Curl;

use KHR\Curl\structure\loop\Pool as LoopPool;
use KHR\Curl\structure\loop\Request as LoopRequest;
use Psr\Http\Message\RequestInterface;

class Pool {

    public $id;

    public $size = 10;

    public $limit = null;

    public $options = [];

    public $loop;

    private $loop_pool;

    private $requests = [];

    public function __construct(Loop $loop = null) {
        $this->loop = isset($loop) ? $loop : new Loop();
        $this->id = spl_object_hash($this);

        $this->loop_pool =  new LoopPool();
        $this->loop_pool->id = &$this->id;
        $this->loop_pool->getRequests = function(){
            return $this->getRequests();
        };
        $this->loop_pool->hasRequests = function(){
            return $this->hasRequests();
        };
    }

    public function addRequest(RequestInterface $request) {
        $options = [];

        $options[CURLOPT_URL] = (string) $request->getUri();
        if ($request->getBody()) {
            $body = $request->getBody();
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = $body instanceof Stream ? $body->getPost() : $body->getContents();

            switch(strtolower($request->getMethod())) {
                case 'get':
                    break;
                case 'post':
                    $options[CURLOPT_POST] = 1;
                    break;
                case 'put':
                    $options[CURLOPT_PUT] = 1;
                    break;
                default:
                    $options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
            }
        }

        $req = new LoopRequest();
        $req->id = spl_object_hash($req);
        $req->pool_id = $this->id;
        $req->options = $this->options + $options;
        $req->request = $request;

        $this->requests[$req->id] = $req;


        $this->loop->addPool($this->loop_pool);
    }

    public function getEach() {
        while($result = $this->next()) {
            yield $result;
        }
    }

    private function getRequests() {
        return [[], 0];

    }

    private function hasRequests() {
        return false;
    }

    private function next() {

    }
}