<?php
/**
 * Created by PhpStorm.
 * User: devzeng
 * Date: 17-6-27
 * Time: 上午9:56
 */
namespace App\Libraries;


class ErrorCode
{
    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;
}