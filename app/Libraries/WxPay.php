<?php
/**
 * Created by PhpStorm.
 * User: devzeng
 * Date: 17-7-3
 * Time: 下午2:25
 */

namespace App\Libraries;


use Mockery\Exception;

class WxPay
{
    private $appid;
    private $mch_id;
    private $key;
    private $openid;
    private $prepay_id;

    public function __construct($appid,$mch_id,$key,$openid='')
    {
        $this->appid = $appid;
        $this->mch_id = $mch_id;
        $this->openid = $openid;
        $this->key = $key;
    }

    public function pay($out_trade_no,$body,$total_fee)
    {
        $return = $this->weixinapp($out_trade_no,$body,$total_fee);
        return $return;
    }

    private function unifiedOrder($out_trade_no,$body,$total_fee)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $parameters = [
            'appid' => $this->appid,
            'mch_id' => $this->mch_id,
            'nonce_str' =>$this->createNoncestr(),
            'body' => $body,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $total_fee,
            'notify_url' => config('wxxcx.notify_url'),
            'openid' => $this->openid,
            'trade_type' => 'JSAPI'
//            'spbill_create_ip' =>
        ];
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $unifiedOrder = $this->xmlToArray($this->postXmlCurl($xmlData, $url, 60));
//        dd($unifiedOrder);
        return $unifiedOrder;
    }

    private static function postXmlCurl($xml, $url, $second = 30,$useCert = false,$sslCert = '',$sslKey = '',$caInfo='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT ,$second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $sslCert);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $sslKey);
            curl_setopt($ch,CURLOPT_CAINFO,$caInfo);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        set_time_limit(0);

        $data = curl_exec($ch);
        if ($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new Exception(500,"curl出错，错误码:$error");
        }
    }

    public function arrayToXml($arr)
    {
        $xml = "<root>";
        foreach ($arr as $key =>$value){
            if (is_array($value)){
                $xml .= "<" . $key . ">" .$this->arrayToXml($value). "</" . $key . ">";
            }else {
                $xml .= "<" . $key . ">" . $value . "</" . $key . ">";
            }
        }
        $xml .= "</root>";
        return $xml;
    }

    public static function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }

    private function weixinapp($out_trade_no,$body,$total_fee)
    {
        $unifiedOrder = $this->unifiedOrder($out_trade_no,$body,$total_fee);
        $this->prepay_id = $unifiedOrder['prepay_id'];
        $parameters = [
            'appId' => $this->appid,
            'timeStamp' => ''. time() . '',
            'nonceStr' => $this->createNoncestr(),
            'package' => 'prepay_id=' . $unifiedOrder['prepay_id'],
            'signType' => 'MD5'
        ];
        $parameters['paySign'] = $this->getSign($parameters);
        return $parameters;
    }

    public function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function getSign($Obj) {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    public function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
    public function refund($transaction_id,$out_refund_no,$total_fee,$refund_fee,$op_user_id,$sslCert,$sslKey,$caInfo)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $parameters = [
            'appid' => $this->appid,
            'mch_id' => $this->mch_id,
            'nonce_str' =>$this->createNoncestr(),
            'transaction_id' => $transaction_id,
            'out_refund_no' => $out_refund_no,
            'total_fee' => $total_fee,
            'refund_fee' => $refund_fee,
            'op_user_id' => $op_user_id
        ];
        $parameters['sign'] = $this->getSign($parameters);
        $xmldata = $this->arrayToXml($parameters);
        $refundData = $this->xmlToArray($this->postXmlCurl($xmldata, $url, 60,TRUE,$sslCert,$sslKey,$caInfo));
        return $refundData;
    }
    public function getPrepayId()
    {
        return $this->prepay_id;
    }

}