<?php
/**
 * Created by PhpStorm.
 * User: devzeng
 * Date: 17-6-27
 * Time: 上午10:00
 */

namespace App\Libraries;


class Prpcrypt
{
    public $key;

    function __construct( $k )
    {
        $this->key = $k;
    }

    /**
     * 对密文进行解密
     * @param string $aesCipher 需要解密的密文
     * @param string $aesIV 解密的初始向量
     * @return string 解密得到的明文
     */
    public function decrypt( $aesCipher, $aesIV )
    {
        try {
            $decrypted = openssl_decrypt($aesCipher,'AES-128-CBC',$this->key,OPENSSL_ZERO_PADDING,$aesIV);
        } catch (\Exception $e) {
            return array(ErrorCode::$IllegalBuffer, null);
        }
        try {
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);

        } catch (\Exception $e) {
            return array(ErrorCode::$IllegalBuffer, null);
        }
        return array(0, $result);
    }
}