<?php
namespace Common\Library\weixinPay;

use Think\Model\RelationModel;
use Common\Library\weixinPay\lib\WxPayConfig;
use Common\Library\weixinPay\lib\JsApiPay;
/**
 * 支付 逻辑定义
 * Class 
 * @package Home\Payment
 */

class weixin extends RelationModel
{        
    /**
     * 析构流函数
     */
    public function  __construct() {   
        //parent::__construct();
                
//        require_once("lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件         
//        require_once("example/WxPay.NativePay.php");
//        require_once("example/WxPay.JsApiPay.php");

        $config_value = M('wechat_config')->where(['id'=>1])->find(); // 获取微信配置
        WxPayConfig::$appid = $config_value['app_id']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
        WxPayConfig::$mchid = $config_value['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        WxPayConfig::$key = $config_value['mch_key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        WxPayConfig::$appsecret = $config_value['app_secret']; // 公众帐号secert（仅JSAPI支付的时候需要配置)，                                      
    }    
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config_value    支付方式信息
     */
    function get_code($order, $config_value)
    {       
            $notify_url = SITE_URL.'/index.php/Home/Payment/notifyUrl/pay_code/weixin'; // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            //$notify_url = C('site_url').U('Home/Payment/notifyUrl',array('pay_code'=>'weixin')); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            //$notify_url = C('site_url')."/index.php?m=Home&c=Payment&a=notifyUrl&pay_code=weixin";
            $input = new WxPayUnifiedOrder();
            $input->SetBody("报名活动"); // 商品描述
            $input->SetAttach("weixin"); // 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            $input->SetOut_trade_no($order['order_sn']); // 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
            $input->SetTotal_fee($order['order_amount']*100); // 订单总金额，单位为分，详见支付金额
            $input->SetNotify_url($notify_url); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            $input->SetTrade_type("NATIVE"); // 交易类型   取值如下：JSAPI，NATIVE，APP，详细说明见参数规定    NATIVE--原生扫码支付
            $input->SetProduct_id("123456789"); // 商品ID trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
            $notify = new NativePay();
            $result = $notify->GetPayUrl($input); // 获取生成二维码的地址
            $url2 = $result["code_url"];
            return '<img alt="模式二扫码支付" src="/index.php?m=Home&c=Index&a=qr_code&data='.urlencode($url2).'" style="width:110px;height:110px;"/>';        
    }    
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {                        
        require_once("example/notify.php");  
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);       
    }
    
    /**
     * 页面跳转响应操作给支付接口方调用
     */
    function respond2()
    {
        // 微信扫码支付这里没有页面返回
    }

    function getJSAPI($order){
        $go_url = U('Home/Index/paySuccess', array('order_sn' => $order['order_sn'], 'act_id' => $order['activity_id']));
        $back_url = U('Home/Index/payorder',array('act_id'=>$order['activity_id'],'attr_id' => $order['activity_attr_id']));
        //①、获取用户openid
        $tools = new JsApiPay();
        //$openId = $tools->GetOpenid();
        $openId = 'o5VWIwvn-ngvYmxOriF3kZFgQ7TQ';
        //②、统一下单
        //import('WxPay.Data');
        //vendor('WxPay.Api','.php');
        $input = new \Common\Library\weixinPay\lib\WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'].time());
        $input->SetTotal_fee($order['price']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("tp_wx_pay");
        $input->SetNotify_url(SITE_URL.'/index.php/Home/Index/notifyUrl/pay_code/weixin');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order2 = WxPayApi::unifiedOrder($input);
        //echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        //printf_info($order);exit;  
        $jsApiParameters = $tools->GetJsApiParameters($order2);
        return $jsApiParameters;

    }

}