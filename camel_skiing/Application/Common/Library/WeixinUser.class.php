<?php

namespace Common\Library;
class WeixinUser
{
    /**
     * @return mixed|null|string
     */
    public static function getOpenId()
    {
        return WeiXinCommon::getOpenid();
    }

    /**
     * 通过openid获取微信用户基本信息,
     * @param $openid
     * @return array|bool|mixed|string
     */
    public static function getWeixinUserInfo($openid = null, $refresh = false)
    {
        if (is_null($openid) && ($openid = WeiXinCommon::getOpenid()) == -1) {
            return [];
        }
        $access_token = WeiXinCommon::getAccessToken($refresh);
        if (empty ($access_token)) {
            return array();
        }
        $param2 ['access_token'] = $access_token;
        $param2 ['openid'] = $openid;
        $param2 ['lang'] = 'zh_CN';

        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?' . http_build_query($param2);
        $content = file_get_contents($url);
        $content = json_decode($content, true);
        if (isset($content['errcode'])) {
            CommonTool::writeLog(RUNTIME_PATH . 'access/getUserInfoError.log', json_encode($content) . '---' . json_encode($param2));
            return [];
        }
        CommonTool::writeLog(RUNTIME_PATH . 'access/getUserInfoSuccess.log', json_encode($content));
        return $content;
    }

    /**
     * 获取jsapi_ticket
     * @return int|mixed
     */
    public static function get_jsapi_ticket($_access_token = '')
    {
        $key = 'jsapi_ticket_' . WeiXinCommon::$_config["Appid"];
        $res = S($key);
        if ($res !== false)
            return $res;
        if (!$_access_token) $_access_token = WeiXinCommon::getAccessToken();
        $_uri = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $_access_token . '&type=jsapi';
        $content = WeiXinCommon::getUriForData($_uri);
        if (@array_key_exists('ticket', $content)) {
            S($key, $content ['ticket'], $content ['expires_in'] * 0.8);
            return $content ['ticket'];
        } else {
            return 0;
        }
    }

    /**
     * 获取JsApi使用签名
     * @param string $url 网页的URL，自动处理#及其后面部分
     * @param string $timestamp 当前时间戳 (为空则自动生成)
     * @param string $noncestr 随机串 (为空则自动生成)
     * @param string $appid 用于多个appid时使用,可空
     * @return array|bool 返回签名字串
     */
    public static function getJsSign($url = '', $timestamp = 0, $noncestr = '')
    {
        if (empty($url)) {
            $url = WeiXinCommon::getCurrentUrl();
        }
        $ret = strpos($url, '#');
        if ($ret) $url = substr($url, 0, $ret);
        $url = trim($url);

        $_jsapi_ticket = static::get_jsapi_ticket();

        if (!$timestamp)
            $timestamp = time();

        if (!$noncestr)
            $noncestr = static::generateNonceStr();

        $arrdata = array("timestamp" => $timestamp, "noncestr" => $noncestr, "url" => $url, "jsapi_ticket" => $_jsapi_ticket);
        $sign = static::getSignature($arrdata);

        if (!$sign) return false;

        $signPackage = array(
            "appid" => WeiXinCommon::$_config["Appid"],
            "noncestr" => $noncestr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $sign
        );
        return $signPackage;
    }

    /**
     * 生成随机字串
     * @param number $length 长度，默认为16，最长为32字节
     * @return string
     */
    public static function generateNonceStr($length = 16)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * 获取签名
     * @param array $arrdata 签名数组
     * @param string $method 签名方法
     * @return boolean|string 签名值
     */
    public static function getSignature($arrdata, $method = "sha1")
    {
        if (!function_exists($method)) return false;
        ksort($arrdata);

        $paramstring = "";
        foreach ($arrdata as $key => $value) {
            if (strlen($paramstring) == 0)
                $paramstring .= $key . "=" . $value;
            else
                $paramstring .= "&" . $key . "=" . $value;
        }
        $Sign = $method($paramstring);
        return $Sign;
    }

    /**
     * @param $scene_str
     * @param string $_access_token
     * @return bool|mixed
     */
    public static function get_qrcode($scene_str, $_access_token = '', $_type = 1)
    {
        if (!$_access_token) $_access_token = WeiXinCommon::getAccessToken();
        $_uri = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $_access_token;
        $_data = [];
        if ($_type == 1) {
            $_data = [
                'action_name' => 'QR_LIMIT_STR_SCENE',
                'action_info' => ['scene' => ['scene_str' => $scene_str]]
            ];
        } else if ($_type == 2) {
            $_data = [
                'action_name' => 'QR_SCENE',
                'expire_seconds' => 2592000,
                'action_info' => [
                    'scene' => [
                        'scene_id' => $scene_str,
                    ],
                ]
            ];
        } else {
            CommonTool::writeLog(RUNTIME_PATH . 'access/getQrcodeError_dataExist' . date('Ymd') . '.log', 1);
        }

        $_content = WeiXinCommon::postUriForData($_uri, $_data, true);
        if (isset($_content["errcode"]) || isset($_content["errmsg"])) { //获取出错
            CommonTool::writeLog(RUNTIME_PATH . 'access/getQrcodeError' . date('Ymd') . '.log', json_encode($_content));
            return [];
        } else {
            CommonTool::writeLog(RUNTIME_PATH . 'access/getQrcodeSuccess' . date('Ymd') . '.log', json_encode($_content));
            return [
                "ticket" => $_content["ticket"],
                "expire_seconds" => $_content["expire_seconds"],
                "url" => $_content["url"],
            ];
        }
    }

    /**
     * 图文消息
     * @param $openid
     * @param $article_array
     * @return bool
     */
    public static function serviceNews($openid, $article_array)
    {
        if (!is_array(current($article_array))) $article_array = array($article_array);

        $articles = '';
        foreach ($article_array as $val) {
            $articles .= ' {
					 "title":"' . $val['title'] . '",
					 "description":"' . $val['description'] . '",
					 "url":"' . $val['url'] . '",
					 "picurl":"' . $val['picurl'] . '"
				 },';

        }
        $articles = substr($articles, 0, -1);
        $tpl = '{
			"touser":"' . $openid . '",
			"msgtype":"news",
			"news":{
				"articles": [
					' . $articles . '
				 ]
			}
		}';

        $token = WeiXinCommon::getAccessToken();
        $ret = WeiXinCommon::curlPost('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $token, $tpl);
        $_data = json_decode($ret, true);
        return $_data;
    }

    /**
     *
     *  $remark = "\n订单下单成功,请到后台查看!";
     * $msg = array(
     * 'first' => array(
     * 'value' => "您的订单已提交成功！",
     * "color" => "#4a5077"
     * ),
     * 'keyword1' => array(
     * 'title' => '店铺',
     * 'value' => '陈汉的店铺',
     * "color" => "#4a5077"
     * ),
     * 'keyword2' => array(
     * 'title' => '下单时间',
     * 'value' => date('Y-m-d H:i:s'),
     * "color" => "#4a5077"
     * ),
     * 'keyword3' => array(
     * 'title' => '商品',
     * 'value' => '规格:2015,单价:1数量:20RMB,总价:100',
     * "color" => "#4a5077"
     * ),
     * 'keyword4' => array(
     * 'title' => '金额',
     * 'value' => '￥100元(含运费6元)',
     * "color" => "#4a5077"
     * ),
     * 'remark' => array(
     * 'value' => $remark,
     * "color" => "#4a5077"
     * )
     * );
     * 向客户发送信息
     * @param $openid
     * @param $content
     * @return mixed
     */
    public static function serviceText($openid, $msg, $url = '')
    {
        $content = "";
        if (is_array($msg)) {
            foreach ($msg as $key => $value) {
                if (!empty($value['title'])) {
                    $content .= $value['title'] . ":" . $value['value'] . "\n";
                } else {
                    $content .= $value['value'] . "\n";
                    if ($key == 0) {
                        $content .= "\n";
                    }
                }
            }
        } else {
            $content = $msg;
        }
        if (!empty($url)) {
            $content .= "<a href='{$url}'>点击查看详情</a>";
        }
        $data = urldecode(json_encode(array(
            "touser" => $openid,
            "msgtype" => "text",
            "text" => array(
                'content' => urlencode($content)
            )
        )));

         $token= WeiXinCommon::getAccessToken() ;
     //   $token = 'zNVUnMPbis1O19IEd-O6uVozNANDPEW2SQnENT0A4I7misxlYyXRLHW84rC9isUEFi555d1wyir55oHpVGJCKxTbq5d9bXn0gNdr8nnyYXD2puIcLgqUan1xEH8-8ywySFAbAIAZMH'; // WeiXinCommon::getAccessToken() ;
        $ret = WeiXinCommon::curlPost('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $token, $data);
        $_data = json_decode($ret, true);
        return $_data;
    }
}