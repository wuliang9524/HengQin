<?php

namespace Logan\Hengqin\device;

use Throwable;
use Logan\Hengqin\device\Dev;
use Logan\Hengqin\exceptions\device\ParamsException;

class WorkerFeature extends Dev
{
    /**
     * 命令编号 十进制
     *
     * @var int
     */
    protected $command = 845;

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

        $hexDNo = bin2hex($this->params['deviceNo']);
        $idCode = bin2hex($this->params['idCode']);

        $xor = $this->bbcXor($hexDNo . $idCode);
        $xor = str_pad($xor, 2, '0', STR_PAD_LEFT);

        return $this->compileResHex = $hexDNo . $idCode . $xor;
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

        $data = [];

        // 工人编号
        $workerNoHex = $this->hexReverse(substr($responseStr, 0, 8));
        $data['workerNo'] = base_convert($workerNoHex, 16, 10);

        // 姓名
        $workerNameHex = substr($responseStr, 8, 60);
        $data['workerName'] = trim(pack("H*", $workerNameHex));

        // 省份证号码
        $workerIdCardHex = substr($responseStr, 68, 36);
        $data['workerIdCard'] = trim(pack("H*", $workerIdCardHex));

        // 民族
        $nation = substr($responseStr, 104, 2);
        $data['nation'] = base_convert($nation, 16, 10);

        // 性别 1->男,0->女
        $sex = substr($responseStr, 106, 4);
        $data['sex'] = trim(pack("H*", $sex));

        // 身份证地址
        $idCardAddress = substr($responseStr, 110, 280);
        $data['idCardAddress'] = trim(pack("H*", $idCardAddress));

        // 出生年月日
        $birthday = substr($responseStr, 390, 32);
        $data['birthday'] = trim(pack("H*", $birthday));

        // 发证机关
        $issueAgency = substr($responseStr, 422, 120);
        $data['issueAgency'] = trim(pack("H*", $issueAgency));

        // 有效期
        $validPeriod = substr($responseStr, 542, 128);
        $data['validPeriod'] = trim(pack("H*", $validPeriod));

        // 采集照片长度
        $GL = $this->hexReverse(substr($responseStr, 670, 8));
        $data['GL'] = base_convert($GL, 16, 10);

        // 采集照片
        $GLContent = substr($responseStr, 678, ($data['GL'] * 2));
        $data['GLContent'] = hex2bin($GLContent);
        $GLImageType = getimagesizefromstring($data['GLContent'])['mime']; //获取二进制流图片格式
        $data['GLContent'] = 'data:' . $GLImageType . ';base64,' . chunk_split(base64_encode($data['GLContent']));

        // 身份证照片长度
        $PL = $this->hexReverse(substr($responseStr, (678 + ($data['GL'] * 2)), 8));
        $data['PL'] = base_convert($PL, 16, 10);

        // 身份证照片  "image/x-ms-bmp" 格式特殊
        $PLContent = substr($responseStr, (678 + ($data['GL'] * 2) + 8), ($data['PL'] * 2));
        $data['PLContent'] = hex2bin($PLContent);
        $PLImageType = getimagesizefromstring($data['PLContent'])['mime']; //获取二进制流图片格式 "image/x-ms-bmp"
        $data['PLContent'] = 'data:' . $PLImageType . ';base64,' . chunk_split(base64_encode($data['PLContent']));

        // 红外照片长度
        $HL = $this->hexReverse(substr($responseStr, ((678 + ($data['GL'] * 2) + 8) + $data['PL'] * 2), 8));
        $data['HL'] = base_convert($HL, 16, 10);

        // 红外照片
        $HLContent = substr($responseStr, (((678 + ($data['GL'] * 2) + 8) + $data['PL'] * 2) + 8), ($data['HL'] * 2));
        $data['HLContent'] = hex2bin($HLContent);
        $HLImageType = getimagesizefromstring($data['HLContent'])['mime']; //获取二进制流图片格式
        $data['HLContent'] = 'data:' . $HLImageType . ';base64,' . chunk_split(base64_encode($data['HLContent']));

        return $data;
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
        if (empty($this->params['deviceNo'])) {
            throw new ParamsException('deviceNo param is not null', 0);
        }

        if (empty($this->params['idCode'])) {
            throw new ParamsException('idCode param is not null', 0);
        }
        return null;
    }
}
