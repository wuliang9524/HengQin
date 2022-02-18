<?php

namespace Logan\Hengqin\device;

use Throwable;
use Logan\Hengqin\device\Dev;
use Logan\Hengqin\exceptions\device\ParamsException;

class HeartBeat extends Dev
{
    /**
     * 命令编号 十进制
     *
     * @var int
     */
    protected $command = 65535;

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

        return $this->compileResHex = '';
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
        return null;
    }
}
