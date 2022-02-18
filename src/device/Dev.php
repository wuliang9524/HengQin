<?php

namespace Logan\Hengqin\device;

use Throwable;
use Socket\Raw\Socket;
use Logan\Hengqin\exceptions\device\PreReadException;

abstract class Dev
{
    /**
     * 版本号 Hex
     *
     * @var int
     */
    protected $version = '01';

    /**
     * 命令编号 十进制
     *
     * @var int
     */
    protected $command;

    /**
     * 会话 ID
     *
     * @var string
     */
    protected $sessionId;

    /**
     * 参数编译后的十六进制字符串
     *
     * @var string
     */
    protected $compileResHex = '';

    /**
     * 每个接口的具体参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 组装好的发送包
     * Hex 字符串
     *
     * @var string
     */
    protected $reqHexStr = '';

    /**
     * 发送请求结果
     *
     * @var [type]
     */
    protected $sendRes;

    /**
     * Response
     *
     * @var [type]
     */
    protected $response;

    /**
     * 反编译后结果
     *
     * @var string
     */
    protected $deCompileRes;

    /**
     * 实例
     *
     * @var Socket\Raw\Socket
     */
    protected $socket = NULL;

    /**
     * 构造方法
     *
     * @param Socket $socket
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * 编译载荷
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    abstract public function compile(array $params);

    /**
     * 反编译载荷
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    abstract public function decompile(string $responseStr, bool $flag);

    /**
     * 验证参数
     *
     * @return Throwable
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    abstract public function validate(): ?Throwable;

    /**
     * 组装发送前的数据包
     *
     * @param array $params     接口每个接口具体的参数数据
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function pack(array $params = [])
    {
        $this->params = $params;
        $this->sessionId = $this->setSessionId();

        // 验证参数
        $this->validate();

        // 内容长度
        $compileRexHex = $this->compile($params);
        $paramsLength  = $this->paramsLength($compileRexHex, 8);

        // 命令
        $command = str_pad(base_convert($this->command, 10, 16), 4, '0', STR_PAD_LEFT);

        $flag = '00';

        // 组装包
        // 数字类型都为低位在前
        // 开始标记 + 内容的长度 + 分包顺序索引 + 分包总数 + 版本 + 命令 + 会话标识 + 内容 + 状态 + 结束标记
        $this->reqHexStr = '01'
            . $this->hexReverse($paramsLength)
            . '00000000'
            . '00000000'
            . $this->version
            . $this->hexReverse($command)
            . $this->sessionId
            . $compileRexHex
            . '00'
            . '01';

        return $this;
    }

    /**
     * 解析返回后的数据包
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-16
     */
    private function unPack()
    {
        if (!$this->response) return false;

        $data = [];

        // 检查开始标志
        $head = substr($this->response, 0, 2);
        if ($head !== "01") return false;

        // 帧长度
        $lengthHex = $this->hexReverse(substr($this->response, 2, 8));
        $data['length']  = base_convert($lengthHex, 16, 10);

        // 分包顺序索引
        $partIndex = $this->hexReverse(substr($this->response, 10, 8));
        $data['partIndex'] = base_convert($partIndex, 16, 10);

        // 分包总数
        $partCount = $this->hexReverse(substr($this->response, 18, 8));
        $data['partCount'] = base_convert($partCount, 16, 10);

        // 版本号
        $version = substr($this->response, 26, 2);
        $data['version'] = base_convert($version, 16, 10);

        // 命令
        $command = $this->hexReverse(substr($this->response, 28, 4));
        $data['command'] = base_convert($command, 16, 10);

        // 会话标识
        $sessionId = substr($this->response, 32, 32);
        $data['sessionId'] = $sessionId;

        // 状态
        $flag = substr($this->response, (64 + $data['length'] * 2), 2);
        $data['flag'] = ($flag === '00' ? true : false);

        // 内容
        $content = substr($this->response, 64, $data['length'] * 2);     // 长度(字节数) * 2 (十六位进制数)
        $data['content'] = $this->decompile($content, $data['flag']);

        // 帧尾结束标志
        $end = substr($this->response, ((64 + $data['length'] * 2) + 2), 2);
        if ($end !== '01') return false;

        return $data;
    }

    /**
     * 发送请求
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-16
     */
    public function sendRequest()
    {
        $pack          = pack('H*', $this->reqHexStr);
        $this->sendRes = $this->socket->write($pack);
        return $this;
    }

    /**
     * 获取返回信息
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-16
     */
    public function getResponse()
    {
        // 读返回流
        $response = $this->readResponse();

        // 解析返回后的数据包
        return $this->deCompileRes = $this->unPack();
    }

    /**
     * 读取 TCP 返回的二进制流
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-16
     */
    private function readResponse()
    {
        // 预读取
        $preRead = $this->socket->read(5);
        $unpack  = unpack('H*', $preRead);
        if ($unpack === false) {
            throw new PreReadException('socket pre read fail');
        }

        $preRes = strtoupper($unpack[1]);

        // 检查开始标志
        $head = substr($preRes, 0, 2);
        if ($head !== '01') {
            return $this->response = false;
        }

        // 内容长度
        $len = substr($preRes, 2, 8);
        $len = base_convert($this->hexReverse($len), 16, 10);

        // 再读取最后全部内容
        $overLen = 4 + 4 + 1 + 2 + 16 + $len + 1 + 1;
        $overRead = '';
        while ($overLen > 0) {
            $read = $this->socket->read($overLen);
            $overRead .= $read;
            $overLen -= strlen($read);
        }

        $unpack = unpack('H*', $overRead);

        if ($unpack === false) {
            throw new PreReadException('socket pre read fail');
        }
        $overRes = strtoupper($unpack[1]);

        return $this->response = $preRes . $overRes;
    }

    /**
     * 获取载荷的内容长度
     *
     * @param string $compileHex    编译载荷后的 Hex 字符串
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function paramsLength(string $compileHex = '', int $len = 8)
    {
        $compileHex = $compileHex ?: $this->compileResHex;
        $lenHex     = base_convert((strlen($compileHex) / 2), 10, 16);
        return str_pad($lenHex, $len, '0', STR_PAD_LEFT);
    }

    /**
     * BBC 异或校验 BCC(Block Check Character/信息组校验码)
     * 具体算法是：将每一个字节的数据（一般是两个16进制的字符）进行异或后即得到校验码。
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function bbcXor(string $string)
    {
        return $this->hexOrArr($this->strSplit($string));
    }

    /**
     * 字符串根据字节长度拆分为数组
     *
     * @param string $str   拆分的字符串
     * @param int $len      字节长度
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function strSplit(string $str, int $len = 2)
    {
        $arr = str_split($str, $len);

        return $arr;
    }

    /**
     * hex 数据 BBC 异或校验 (多个 hex 数据进行校验)
     *
     * @param array $data
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function hexOrArr(array $data)
    {
        $res = $data[0];
        for ($i = 0; $i < count($data) - 1; $i++) {
            $res = $this->hexOr($res, $data[$i + 1]);
        }
        return $res;
    }

    /**
     * hex 数据 BBC 异或校验 (两两比较)
     *
     * @param [type] $byte1
     * @param [type] $byte2
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-15
     */
    public function hexOr($byte1, $byte2)
    {
        $res   = '';
        $byte1 = str_pad(base_convert($byte1, 16, 2), '8', '0', STR_PAD_LEFT);
        $byte2 = str_pad(base_convert($byte2, 16, 2), '8', '0', STR_PAD_LEFT);

        $len = strlen($byte1);
        for ($i = 0; $i < $len; $i++) {
            $res .= $byte1[$i] == $byte2[$i] ? '0' : '1';
        }

        return strtoupper(base_convert($res, 2, 16));
    }

    /**
     * 生成会话 ID
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-16
     */
    public function setSessionId()
    {
        return $sessionId = strtoupper(md5(uniqid(mt_rand(), true)));
    }

    /**
     * 十六进制字节转换字节序
     *
     * @param [string] $hex
     * @return string
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2021-01-21
     */
    protected function hexReverse($hex)
    {
        $len = strlen($hex);
        if ($len % 2) {
            $hex = '0' . $hex;
        }
        $hexArr = str_split($hex, 2);
        $hexArr = array_reverse((array)$hexArr);
        return implode('', $hexArr);
    }
}
