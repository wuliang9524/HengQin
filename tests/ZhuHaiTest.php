<?php

declare(strict_types=1);

use Logan\Hengqin\Client;
use PHPUnit\Framework\TestCase;

class ZhuHaiTest extends TestCase
{
    protected $host = 'http://47.107.124.3:8010/';
    protected $config = [
        'key'   => 'E45CD6C7-A186-49AA-A247-2A828E3424CF',
        'token' => '',
    ];
    protected $proCode = '2B76A87BB6CD4CA0805544EDED8F2E66'; // 207

    public function testQueryGroup()
    {
        $instance = new Client($this->host, $this->config['key'], $this->config['token']);
        $res = $instance->queryGroup($this->proCode, '')->send();
    }

    public function testQueryBaseDic()
    {
        $instance = new Client($this->host, $this->config['key'], $this->config['token']);
        $res = $instance->queryBaseDic(17)->send();
        var_dump($res);
    }
}
