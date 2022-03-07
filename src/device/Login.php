<?php

namespace Logan\Hengqin\device;

use Throwable;
use Logan\Hengqin\device\Dev;
use Logan\Hengqin\exceptions\device\ParamsException;

class Login extends Dev
{
    /**
     * 命令编号 十进制
     *
     * @var int
     */
    protected $command = 843;

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

        $hexFNo = bin2hex($this->params['factoryNo']);
        $hexDNo = bin2hex($this->params['deviceNo']);

        $xor = $this->bbcXor($hexFNo . $hexDNo);
        $xor = str_pad($xor, 2, '0', STR_PAD_LEFT);

        return $this->compileResHex = $hexFNo . $hexDNo . $xor;
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
        return NULL;
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
        if (empty($this->params['factoryNo'])) {
            throw new ParamsException('factoryNo param is not null', 0);
        }

        if (empty($this->params['deviceNo'])) {
            throw new ParamsException('deviceNo param is not null', 0);
        }
        return null;
    }
}
