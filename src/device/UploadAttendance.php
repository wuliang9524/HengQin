<?php

namespace Logan\Hengqin\device;

use Throwable;
use Logan\Hengqin\device\Dev;
use Logan\Hengqin\exceptions\device\ParamsException;

class UploadAttendance extends Dev
{
    /**
     * 命令编号 十进制
     *
     * @var int
     */
    protected $command = 848;

    /**
     * 实现具体的编译方法
     *
     * @param array $params
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function compile(array $params = [])
    {
        $params = $params ?: $this->params;

        $hexWNo   = $this->hexReverse(
            str_pad(base_convert($params['workerNo'], 10, 16), 8, '0', STR_PAD_LEFT)
        );
        $dateTime = date('YmdHis', strtotime($params['dateTime']));
        $type     = str_pad(base_convert($params['type'], 10, 16), 2, '0', STR_PAD_LEFT);

        $image    = file_get_contents($params['image']);
        $imageHex = $image ? bin2hex($image) : '';

        $imageLen = (strlen($imageHex) / 2) + (strlen($imageHex) % 2);
        $imageLen = $this->hexReverse(
            str_pad(base_convert($imageLen, 10, 16), 8, '0', STR_PAD_LEFT)
        );

        $xor = $this->bbcXor($hexWNo . $dateTime . $type . $imageLen . $imageHex);
        $xor = str_pad($xor, 2, '0', STR_PAD_LEFT); 

        return $this->compileResHex = $hexWNo . $dateTime . $type . $imageLen . $imageHex . $xor;
    }

    /**
     * 实现具体的反编译方法
     *
     * @param string $responseHex
     * @param bool $flag
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-16
     */
    public function decompile(string $responseStr, bool $flag)
    {
        // 请求结果失败
        if ($flag === false) {
            return ['message' => trim(pack('H*', $responseStr))];
        }

        return null;
    }

    /**
     * 实现参数校验方法
     *
     * @return Throwable|null
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-16
     */
    public function validate(): ?Throwable
    {
        if (empty($this->params['workerNo'])) {
            throw new ParamsException('workerNo param is not null', 0);
        }

        if (empty($this->params['dateTime'])) {
            throw new ParamsException('dateTime param is not null', 0);
        }

        if (empty($this->params['type'])) {
            throw new ParamsException('type param is not null', 0);
        }

        if (empty($this->params['image'])) {
            throw new ParamsException('image param is not null', 0);
        }
        return null;
    }
}
