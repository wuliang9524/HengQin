<?php

declare(strict_types=1);

use Logan\Hengqin\Client;
use Logan\Hengqin\Device;
use PHPUnit\Framework\TestCase;

class GaoMingTest extends TestCase
{
    protected $host = 'http://119.23.147.62:8059/';
    protected $config = [
        'key'   => '48762FE1-2506-4AB7-89D9-3220BF9AFFD5',
        'token' => 'B252A9C4-3E83-4F52-92F9-B9492F5A816C',
    ];
    protected $proCode = '6B7BE50ED44A4FD2AAE241615F4CB775'; // 1139

    public function testQueryGroup()
    {
        $instance = new Client($this->host, $this->config['key'], $this->config['token']);
        $res = $instance->queryGroup($this->proCode, '')->send();
    }

    public function testAddGroup()
    {
        $instance = new Client($this->host, $this->config['key'], $this->config['token']);
        $res = $instance->setDateTime(strtotime('2022-03-03 11:14:39'))->addGroup([
            'projectCode' => "6B7BE50ED44A4FD2AAE241615F4CB775",
            'corpCode'    => "9144060019353290XX",
            'corpName'    => "佛山市房建集团有限公司",
            'teamName'    => "铁工班",
            'entryTime'   => "2021-04-30"
        ])->send();
    }

    public function testQueryBaseDic()
    {
        $instance = new Client($this->host, $this->config['key'], $this->config['token']);
        $res = $instance->queryBaseDic(1)->send();
        var_dump($res);
    }

    public function testAddAttendance()
    {
        $host = '119.23.147.62';
        $port = 9619;
        $factoryNo = "8F1120536942D226AB164FCFEEC0E98F";
        $deviceNo = "D9E3960548E6C4C47D10061179FB79F8";
        $idCode = '450881199409277758';
        $dateTime = '2022-02-16 08:25:45';
        $type = 6;
        $image = 'http://file.global8.cn/User/620c448e940c9.jpeg';

        $instance = new Device($host, $port);
        $res = $instance->addAttendance($factoryNo, $deviceNo, $idCode, $dateTime, $image);
        var_dump($res);
    }
}
