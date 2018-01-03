<?php

namespace Common\Library;
/**
 * 微信共用
 * Class Common
 * @package WexinLib
 */
class WeiXinCommon
{
    //微信服务号
    public static $_config = [
        #公众号
        "Appid" => "wxe837fbe00fd72769",
        "AppSecret" => "0048af7f485858a8f0868c4adbbc63ae",
        "token" => "weiphp",
    ];

    /**
     * 获取当前用户的OpenId
     * @param null $openid
     * @return mixed|null|string
     */
    public static function getOpenid($openid = NULL)
    {
        $token = static::$_config["token"];
        if ($openid !== NULL && $openid != '-1') {
            session('openid_' . $token, $openid);
        } elseif (!empty ($_REQUEST['openid']) && $_REQUEST['openid'] != '-1' && $_REQUEST['openid'] != '-2') {
            session('openid_' . $token, $_REQUEST['openid']);
        }
        $openid = session('openid_' . $token);
        $isWeixinBrowser = static::isWeixinBrowser();
        
        
        if ((empty ($openid) || $openid == '-1') && $isWeixinBrowser && $_REQUEST['openid'] != '-2' && IS_GET && !IS_AJAX) {
            $callback = static::getCurrentUrl();
            static::OAuthWeixin($callback, $token);
        }
        if (empty ($openid)) {
            return '-1'; //openid获取失败error
        }
        return $openid;
    }

    /**
     * 判断是否是在微信浏览器里
     * @param int $from
     * @return bool
     */
    public static function isWeixinBrowser($from = 0)
    {
        $agent = $_SERVER ['HTTP_USER_AGENT'];
        if (!strpos($agent, "icroMessenger")) {
            return false;
        }
        return true;
    }

    /**
     * 获取会员openid
     * @param $callback
     * @param string $token
     */
    public static function OAuthWeixin($callback)
    {
        $callback = urldecode($callback);
        $isWeixinBrowser = static::isWeixinBrowser();
        $info = static::$_config;
        if (strpos($callback, '?') === false) {
            $callback .= '?';
        } else {
            $callback .= '&';
        }
        if (!$isWeixinBrowser) {
            redirect($callback . 'openid=-2');
        }

        $param ['appid'] = $info ['Appid'];
        
        if (!isset ($_GET ['getOpenId'])) {
            $param ['redirect_uri'] = $callback . 'getOpenId=1';
            $param ['response_type'] = 'code';
            $param ['scope'] = 'snsapi_base';
            $param ['state'] = 123;
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($param) . '#wechat_redirect';
            redirect($url);
        } elseif ($_GET ['state']) {
            $param ['code'] = I('code');
            $param ['grant_type'] = 'authorization_code';
            $param ['secret'] = $info ['AppSecret'];
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query($param);
            $content = static::getUriForData($url);
            redirect($callback . 'openid=' . $content ['openid']);
        }
    }

    /**
     * 获取access_token
     * 获取access_token，自动带缓存功能
     * @param $appid
     * @return int|mixed
     */
    
    public static function getAccessToken($_refresh = false)
    {
        $_where = [
            'app_id' => static::$_config["Appid"],
            'app_secret' => static::$_config["AppSecret"],
          //  'token' => static::$_config["token"],
        ];
        $_field = ['access_token_time', 'access_token'];
        $_result = M('wechat_config')->field($_field)->where($_where)->find();
        if ($_result && $_result['access_token_time'] < time()) { //文件修改的时间
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $_where['app_id'] . '&secret=' . $_where['app_secret'];
            $tempArr = static::getUriForData($url);
            if (@array_key_exists('access_token', $tempArr)) {
                @CommonTool::writeLog(RUNTIME_PATH . 'access/getAccessTokenSucess' . date('Ymd') . '.log', json_encode($tempArr));
                M('wechat_config')->where($_where)->save(['access_token_time' => ($tempArr['expires_in'] - 3600 + time()), 'access_token' => $tempArr ['access_token']]);
                return $tempArr ['access_token'];
            } else {
                CommonTool::writeLog(RUNTIME_PATH . 'access/getAccessTokenError' . date('Ymd') . '.log', json_encode($tempArr));
                return 0;
            }
        } else {
            return $_result['access_token'];
        }
    }

    // php获取当前访问的完整url地址
    public static function getCurrentUrl()
    {
        $url = 'http://';
        if (isset ($_SERVER ['HTTPS']) && $_SERVER ['HTTPS'] == 'on') {
            $url = 'https://';
        }
        if ($_SERVER ['SERVER_PORT'] != '80') {
            $url .= $_SERVER ['HTTP_HOST'] . ':' . $_SERVER ['SERVER_PORT'] . $_SERVER ['REQUEST_URI'];
        } else {
            $url .= $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
        }
        // 兼容后面的参数组装
        if (stripos($url, '?') === false) {
            $url .= '?t=' . time();
        }
        return $url;
    }

    /*
    * 获取支付的appid的openid
    */
    public static function getPaymentOpenid($appId = "", $serect = "")
    {
        if (empty ($appId) || empty ($serect)) {
            static::getOpenid();
            exit(0);
        }
        $callback = static::getCurrentUrl();
        if ((defined('IN_WEIXIN') && IN_WEIXIN) || isset ($_GET ['is_stree']))
            return false;

        $callback = urldecode($callback);
        $isWeixinBrowser = static::isWeixinBrowser();
        if (!$isWeixinBrowser) {
            return -1;
        }
        if (strpos($callback, '?') === false) {
            $callback .= '?';
        } else {
            $callback .= '&';
        }
        $param ['appid'] = $appId;
        
        if (!isset ($_GET ['getOpenId'])) {
            $param ['redirect_uri'] = $callback . 'getOpenId=1';
            $param ['response_type'] = 'code';
            $param ['scope'] = 'snsapi_base';
            $param ['state'] = 123;
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($param) . '#wechat_redirect';
            redirect($url);
        } else if ($_GET ['state']) {
            $param ['secret'] = $serect;
            $param ['code'] = I('code');
            $param ['grant_type'] = 'authorization_code';
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query($param);
            $content = static::getUriForData($url);
            return $content ['openid'];
        }
    }

    /**
     * 用get方式获取数据
     * @param $uri
     * @return array
     */
    public static function getUriForData($uri = '')
    {
        if (!$uri) return false;
        $content = file_get_contents($uri);
        return json_decode($content, true);
    }

    /**
     * 用post方式获取数据
     * @param string $url
     * @param array $data
     * @return bool|mixed
     */
    public static function postUriForData($url = '', $data = [], $_flag = false)
    {
        if (!$url) return false;
        $res = static::curlPost($url, $_flag ? static::jsonEncodeCn($data) : $data);
        return json_decode($res, true);
    }

    public static function jsonEncodeCn($data)
    {
        $data = json_encode($data);
        return preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $data);
    }

    public static function curlPost($url = '', $data = [])
    {
        $header [] = "content-type: application/x-www-form-urlencoded; charset=UTF-8";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //static::json_encode_cn($data)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }
}