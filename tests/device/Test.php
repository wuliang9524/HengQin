<?php

declare(strict_types=1);

use Logan\Hengqin\device\HeartBeat;
use Logan\Hengqin\device\Login;
use Logan\Hengqin\device\UploadAttendance;
use Logan\Hengqin\device\WorkerFeature;
use PHPUnit\Framework\TestCase;
use Socket\Raw\Factory;

class Test extends TestCase
{
    public function testbin2hex()
    {
        $str1 = 'D9E3960548E6C4C47D10061179FB79F8';
        $str2 = '8F1120536942D226AB164FCFEEC0E98F';
        echo bin2hex($str1) . bin2hex($str2);
        die;
        $res = '';
        for ($i = 0, $len = strlen($str1); $i < $len; $i++) {
            $str = $str1[$i];
            $res .= bin2hex($str);
        }

        echo $res;
        die;
        echo bin2hex(pack('H*', $str1) ^ pack('H*', $str2));
        $a = 0x01;
        var_dump(pack('H*', $a));
    }

    public function testxor()
    {
        $hex = '843';
        var_dump(base_convert($hex, 10, 16));
    }

    public function testLogin()
    {
        $factoryNo = "8F1120536942D226AB164FCFEEC0E98F";
        $deviceNo = "D9E3960548E6C4C47D10061179FB79F8";
        $factory = new Factory();
        $socket = $factory->createClient('119.23.147.62:9619');
        // $instance = new Login($socket);
        $instance = new WorkerFeature($socket);
        // $instance = new HeartBeat($socket);
        // $instance = new UploadAttendance($socket);
        $res = $instance
            ->pack(['deviceNo' => $deviceNo, 'idCode' => '450881199409277758'])
            ->sendRequest()
            ->getResponse();
        var_dump($res);
    }

    public function testUploadAttendance()
    {
        $factoryNo = "8F1120536942D226AB164FCFEEC0E98F";
        $deviceNo = "D9E3960548E6C4C47D10061179FB79F8";
        $idCode = '450881199409277758';
        $factory = new Factory();
        $socket = $factory->createClient('119.23.147.62:9619');
        $instance = new Login($socket);
        $res = $instance
            ->pack([
                'factoryNo' => $factoryNo,
                'deviceNo'  => $deviceNo,
            ])
            ->sendRequest()
            ->getResponse();
        if ($res['flag'] === true) {
            $instance = new WorkerFeature($socket);
            $res = $instance
                ->pack([
                    'deviceNo' => $deviceNo,
                    'idCode'   => $idCode
                ])
                ->sendRequest()
                ->getResponse();
            if ($res['flag'] === true) {
                $instance = new UploadAttendance($socket);
                $res = $instance
                    ->pack([
                        'workerNo' => $res['content']['workerNo'],
                        'dateTime' => '2022-02-16 08:25:45',
                        'type'     => 6,
                        'image'    => 'http://file.global8.cn/User/620c448e940c9.jpeg'
                    ])
                    ->sendRequest()
                    ->getResponse();
            }
        }
    }
}
