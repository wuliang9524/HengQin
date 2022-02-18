<?php

namespace Logan\Hengqin;

use Throwable;
use Socket\Raw\Socket;
use Socket\Raw\Factory;
use Logan\Hengqin\device\Login;
use Logan\Hengqin\device\WorkerFeature;
use Logan\Hengqin\device\UploadAttendance;
use Logan\Hengqin\exceptions\LoginFailException;
use Logan\Hengqin\exceptions\InitRuntimeException;
use Logan\Hengqin\exceptions\UploadAttendanceFailException;
use Logan\Hengqin\exceptions\WorkerFeatureFailException;

class Device
{
    /**
     * TCP 服务 服务器 IP
     *
     * @var string
     */
    protected $host;

    /**
     * TCP 服务端口
     *
     * @var string
     */
    protected $port;

    /**
     * 版本
     *
     * @var string
     */
    protected $version = '01';

    /**
     * socket 实例
     *
     * @var Socket\Raw\Socket
     */
    protected $socket = null;

    /**
     * 构造方法
     *
     * @param string $host  服务器 IP
     * @param int $port     端口
     * @param string $version   版本
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-18
     */
    public function __construct(string $host, int $port, string $version = '01')
    {
        $socket = (new Factory())
            ->createClient($host . ':' . $port);

        if (empty($host)) {
            throw new InitRuntimeException("host is not null", 0);
        }
        if (empty($port)) {
            throw new InitRuntimeException("port is not null", 0);
        }
        if (empty($socket)) {
            throw new InitRuntimeException("create tcp client fail", 0);
        }

        $this->host    = $host;
        $this->port    = $port;
        $this->version = $version;
        $this->socket  = $socket;
    }

    /**
     * 上报工人考勤
     *
     * @param string $fctCode   厂家编码
     * @param string $devCode   设备编码
     * @param string $idCode    人员身份证编号
     * @param string $dateTime  打卡时间 Y-m-d H:i:s
     * @param string $image     打卡照片地址 eg: http://file.global8.cn/User/620c448e940c9.jpeg 尽量保持 10kb，不能超过 50kb
     * @param int $type         打卡方式 6->人脸方式
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-18
     */
    public function addAttendance(
        string $fctCode,
        string $devCode,
        string $idCode,
        string $dateTime,
        string $image,
        int $type = 6
    ) {
        try {
            // 登录设备系统
            $instance = new Login($this->socket);
            $res = $instance
                ->pack([
                    'factoryNo' => $fctCode,
                    'deviceNo'  => $devCode,
                ])
                ->sendRequest()
                ->getResponse();
            if ($res['flag'] !== true) {
                throw new LoginFailException("machine login fail: " . $res['content']['message']);
            }

            // 获取人员编号
            $instance = new WorkerFeature($this->socket);
            $res = $instance
                ->pack([
                    'deviceNo' => $devCode,
                    'idCode'   => $idCode
                ])
                ->sendRequest()
                ->getResponse();
            if ($res['flag'] !== true) {
                throw new WorkerFeatureFailException("get worker feature fail: " . $res['content']['message']);
            }
            $workerNo = $res['content']['workerNo'] ?? '';
            if (!$workerNo) {
                throw new WorkerFeatureFailException("lost worker num response");
            }

            // 上传考勤
            $instance = new UploadAttendance($this->socket);
            $res = $instance
                ->pack([
                    'workerNo' => $workerNo,
                    'dateTime' => $dateTime,
                    'type'     => $type,
                    'image'    => $image
                ])
                ->sendRequest()
                ->getResponse();

            if ($res['flag'] !== true) {
                throw new UploadAttendanceFailException("upload attendance fail: " . $res['content']['message']);
            }

            return ['result' => true, 'message' => ''];
        } catch (Throwable $th) {
            return ['result' => false, 'message' => $th->getMessage()];
        }
    }
}
