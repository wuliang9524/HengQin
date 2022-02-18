<?php

namespace Logan\Hengqin;

use GuzzleHttp\Middleware;
use GuzzleHttp\Client as HttpClient;
use Logan\Hengqin\exceptions\InitRuntimeException;

class Client
{
    /**
     * 接口地址(带端口号)
     *
     * @var string
     */
    protected $host;

    /**
     * 请求接口使用的 key
     *
     * @var string
     */
    protected $key;

    /**
     * 请求接口使用的 token
     *
     * @var string
     */
    protected $token;

    /**
     * 当前请求接口的时间日期
     *
     * @var string Y-m-d H:i:s
     */
    protected $dateTime;

    /**
     * 当前请求接口的签名
     *
     * @var string
     */
    protected $sign;

    /**
     * 协议版本
     *
     * @var string
     */
    protected $version = '1.0';

    /**
     * 请求参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 签名时的 body
     *
     * @var array
     */
    protected $body = '';

    /**
     * 请求接口 URI
     *
     * @var array
     */
    protected $uri = '';

    /**
     * GuzzleHttp 实例
     *
     * @var GuzzleHttp\Client
     */
    protected $httpClient = null;

    public function __construct(
        string $host,
        string $key,
        string $token = '',
        string $version = '1.0'
    ) {
        $host = rtrim($host, '/');

        if (empty($key)) {
            throw new InitRuntimeException("key is not null", 0);
        }

        $this->host       = $host;
        $this->key        = $key;
        $this->token      = $token;
        $this->version    = $version;
        $this->httpClient = new HttpClient();

        // 设置请求时间
        $this->setDateTime();
    }

    /**
     * 设置请求参数
     *
     * @param array $params 各接口请求的独自参数
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    private function setParams(array $params)
    {
        $this->params = $params;

        // 生成签名
        $this->setSign();
        return $this;
    }

    /**
     * 获取请求参数
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 设置请求时间
     *
     * @param int $timestamp    时间戳,默认当前时间戳
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-01-26
     */
    public function setDateTime(int $timestamp = 0)
    {
        if ($timestamp === 0) {
            $timestamp = time();
        }
        $this->dateTime = date('Y-m-d H:i:s', $timestamp);
        return $this;
    }

    /**
     * 获取请求时间
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-01-26
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * 生成签名
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-01-27
     */
    public function setSign()
    {
        if (empty($this->params)) {
            $body = '{}';
        }

        $body = json_encode(
            $this->params,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $this->body = $body;

        $arr = [
            'api_key'     => $this->key,
            'api_version' => $this->version,
            'body'        => $body,
            'timestamp'   => $this->dateTime,
        ];
        ksort($arr);

        $string = '';
        foreach ($arr as $k => $v) {
            $string .= $k . $v;
        }

        if ($this->token) {
            $string = $this->token . $string . $this->token;
        } else {
            $string = $this->key . $string . $this->key;
        }

        $this->sign = strtoupper(md5($string));
        return $this;
    }

    /**
     * 获取签名值
     *
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-01-27
     */
    public function getSign()
    {
        return $this->sign;
    }


    /**
     * 查询班组
     *
     * @param string $code  项目编码
     * @param string $name  班组名称
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function queryGroup(string $code, string $name)
    {
        $this->uri = $this->host . '/UploadSmz/GetTeamInfo';
        $this->setParams([
            'ProjectCode' => $code,
            'TeamName'    => $name,
        ]);
        return $this;
    }

    /**
     * 新增班组
     *
     * @param array $groupInfo
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function addGroup(array $groupInfo)
    {
        $this->uri = $this->host . '/UploadSmz/GetTeamInfo';
        $this->setParams($groupInfo);
        return $this;
    }

    /**
     * 修改班组信息
     *
     * @param string $code  班组编号
     * @param array $teamInfo   班组信息
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-08
     */
    public function updateGroup(string $code, array $groupInfo)
    {
        $this->uri = $this->host . '/UploadSmz/UpdateTeamInfo';

        $groupInfo = $groupInfo + ['teamCode' => $code];
        $this->setParams($groupInfo);
        return $this;
    }

    /**
     * 上传劳动合同
     *
     * @param string $proCode   项目编号
     * @param string $code      工人所属企业统一社会信用编码
     * @param string $name      工人所属企业名称
     * @param int $idCodeType   证件类型
     * @param string $idCode    证件号码
     * @param int $conType      合同期限类型
     * @param string $startDate 生效日期，yyyy-MM-dd
     * @param string $endDate   失效日期，yyyy-MM-dd
     * @param string $imgName   合同附件名称
     * @param string $imgBase64 附件Base64字符串，不超过 1M
     * @param [type] $unit      计量单位
     * @param [type] $unitPrice 计量单价
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-14
     */
    public function uploadContract(
        string $proCode,
        string $code,
        string $name,
        int $idCodeType,
        string $idCode,
        int $conType,
        string $startDate,
        string $endDate,
        string $imgName,
        string $imgBase64,
        int $unit = NULL,
        float $unitPrice = NULL
    ) {
        $this->uri = $this->host . '/UploadSmz/UploadContract';
        $this->setParams([
            'projectCode'  => $proCode,
            'contractList' => [
                [
                    'corpCode'           => $code,
                    'corpName'           => $name,
                    'idCardType'         => $idCodeType,
                    'idCardNumber'       => $idCode,
                    'contractPeriodType' => $conType,
                    'startDate'          => $startDate,
                    'endDate'            => $endDate,
                    'unit'               => $unit,
                    'unitPrice'          => $unitPrice,
                    'attachments'        => [
                        [
                            'name' => $imgName,
                            'data' => $imgBase64
                        ]
                    ],
                ]
            ]
        ]);
        return $this;
    }

    /**
     * 上传采集人员特征信息
     *
     * @param string $name          姓名
     * @param string $idCode        身份证号码
     * @param string $nation        民族
     * @param string $native        籍贯
     * @param int $sex              性别
     * @param string $address       身份证地址
     * @param string $birthday      出生年月日  yyyy-MM-dd
     * @param string $idFaceImg     身份证头像照片  base64 格式(不带头部) 不超过 50KB 
     * @param string $issue         发证机关
     * @param string $validDate     证件有效期 格式如：2018.01.01-2028.01.01 或 2018.01.01-长期
     * @param string $faceLibImg    采集照片 base64 格式(不带头部) 不超过 50KB 
     * @param string $faceLibRedImg 红外照片 base64 格式(不带头部) 不超过 50KB 
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-14
     */
    public function uploadFaceLibImg(
        string $name,
        string $idCode,
        string $nation,
        string $native,
        int $sex,
        string $address,
        string $birthday,
        string $idFaceImg,
        string $issue,
        string $validDate,
        string $faceLibImg,
        string $faceLibRedImg
    ) {
        $this->uri = $this->host . '/UploadSmz/UploadWorkerFeature';
        $this->setParams([
            'Name'             => $name,
            'CerfNum'          => $idCode,
            'Nation'           => $nation,
            'Native'           => $native,
            'Sex'              => $sex,
            'IdCardAddress'    => $address,
            'Birthday'         => $birthday,
            'CollectPhoto'     => $faceLibImg,
            'IdCardPhoto'      => $idFaceImg,
            'IssuingAuthority' => $issue,
            'ValidityPeriodBe' => $validDate,
            'InfraredPhoto'    => $faceLibRedImg
        ]);
        return $this;
    }

    /**
     * 添加工人信息
     *
     * @param string $groupCode 班组编号
     * @param string $proCode   项目编号
     * @param string $code      企业信用代码
     * @param string $name      企业名称
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function addWorkerInfo(
        string $groupCode,
        string $proCode,
        string $code,
        string $name,
        array $workersInfo
    ) {
        $this->uri = $this->host . '/UploadSmz/UploadRosterInfo';
        $this->setParams([
            'projectCode' => $proCode,
            'corpCode'    => $code,
            'corpName'    => $name,
            'teamCode'    => $groupCode,
            'workerList'  => $workersInfo,
        ]);
        return $this;
    }

    /**
     * 编辑工人信息
     *
     * @param array $workerInfo    工人详细信息数组
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-09
     */
    public function updateWorkerInfo(array $workerInfo)
    {
        $this->uri = $this->host . '/UploadSmz/UpdateRosterInfo';
        $this->setParams($workerInfo);
        return $this;
    }

    /**
     * 添加项目工人
     * 工人进场
     *
     * @param int $inCodeType   进场类型字典编号
     * @param string $groupCode 班组编号
     * @param string $proCode   项目编号
     * @param string $code      企业信用代码
     * @param string $name      企业名称
     * @param int $idCodeType   证件类型字典编号
     * @param string $idCode    证件号码
     * @param string $date      进场日期
     * @param string $imgName   凭证扫描件名称
     * @param string $imgBase64 凭证扫描件
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function addProjectWorker(
        int $inCodeType,
        string $groupCode,
        string $proCode,
        string $code,
        string $name,
        int $idCodeType,
        string $idCode,
        string $date,
        string $imgName = '',
        string $imgBase64 = ''
    ) {
        $this->uri = $this->host . '/UploadSmz/UploadEntryExitInfo';
        $this->setParams([
            'projectCode' => $proCode,
            'corpCode'    => $code,
            'corpName'    => $name,
            'teamCode'    => $groupCode,
            'workerList'  => [
                [
                    'idCardType'   => $idCodeType,
                    'idCardNumber' => $idCode,
                    'date'         => $date,
                    'type'         => $inCodeType,
                    'voucher'      => $imgBase64 ?: NULL,
                    'fileName'     => $imgName ?: NULL,
                ]
            ],
        ]);
        return $this;
    }

    /**
     * 删除项目工人
     * 工人退场
     *
     * @param int $inCodeType   进场类型字典编号
     * @param string $groupCode 班组编号
     * @param string $proCode   项目编号
     * @param string $code      企业信用代码
     * @param string $name      企业名称
     * @param int $idCodeType   证件类型字典编号
     * @param string $idCode    证件号码
     * @param string $date      进场日期
     * @param string $imgName   凭证扫描件名称
     * @param string $imgBase64 凭证扫描件
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function exitProjectWorker(
        int $inCodeType,
        string $groupCode,
        string $proCode,
        string $code,
        string $name,
        int $idCodeType,
        string $idCode,
        string $date,
        string $imgName = '',
        string $imgBase64 = ''
    ) {
        $this->uri = $this->host . '/UploadSmz/UploadEntryExitInfo';
        $this->setParams([
            'projectCode' => $proCode,
            'corpCode'    => $code,
            'corpName'    => $name,
            'teamCode'    => $groupCode,
            'workerList'  => [
                [
                    'idCardType'   => $idCodeType,
                    'idCardNumber' => $idCode,
                    'date'         => $date,
                    'type'         => $inCodeType,
                    'voucher'      => $imgBase64 ?: NULL,
                    'fileName'     => $imgName ?: NULL,
                ]
            ],
        ]);
        return $this;
    }

    /**
     * 上传设备绑定信息
     *
     * @param string $code      设备序列号(32位GUID格式)
     * @param int $direct       考勤方向 1=>进,2=>出,3=>普通考勤
     * @param string $position  设备安装位置
     * @param bool $isBind      是否为绑定设备 true->绑定设备,false->拆卸设备 一台设备只能同时绑定一个项目
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-14
     */
    public function addDevice(
        string $code,
        int $direct,
        string $position,
        bool $isBind
    ) {
        $this->uri = $this->host . '/UploadSmz/UploadDeviceInfo';
        $this->setParams([
            'SerialNo'        => $code,
            'Direct'          => $direct,
            'InstallPosition' => $position,
            'BindState'       => $isBind ? 1 : 2
        ]);
        return $this;
    }

    /**
     * 获取基础数据类型数据字典
     *
     * @param int $type 基础数据类型
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function queryBaseDic(int $type)
    {
        $this->uri = $this->host . '/UploadSmz/GetBaseDataDictionary';
        $this->setParams([
            'type' => $type
        ]);
        return $this;
    }


    /**
     * 发起接口请求
     *
     * @param string $method
     * @param bool $isDebug
     * @return void
     * @author LONG <1121116451@qq.com>
     * @version version
     * @date 2022-02-11
     */
    public function send(string $method = 'POST')
    {
        $response = $this->httpClient->request($method, $this->uri, [
            'query' => [
                'api_key'     => $this->key,
                'api_version' => $this->version,
                'timestamp'   => $this->dateTime,
                'signature'   => $this->sign,
            ],
            'body'  => $this->body
        ])
            ->getBody()
            ->getContents();

        return json_decode($response, true);
    }
}
