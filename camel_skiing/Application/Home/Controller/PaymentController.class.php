<?php
namespace Home\Controller;
Vendor('WxPay.WxPay#Data');
Vendor('WxPay.WxPay#Notify');
Vendor('WxPay.log');
Vendor('WxPay.WxPay#Api');

$f = dirname(dirname(__FILE__));
//初始化日志
$logHandler= new \CLogFileHandler($f."/logs/".date('Y-m-d').'.log');
$log = \Log::Init($logHandler, 15);
class PaymentController extends \WxPayNotify {
   
    	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($input);
                file_put_contents('sure_weixinPayOrder_log.txt',  var_export(json_encode($result),true),FILE_APPEND);
		\Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
   
    	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{     
		\Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}         
                $appid = $data['appid']; //公众账号ID
                $order_sn = $out_trade_no = $data['out_trade_no']; //商户系统的订单号，与请求一致。
                $attach = $data['attach']; //商家数据包，原样返回                
                file_put_contents('weixin_SuccessPay_log.txt',  var_export($data,true),FILE_APPEND);
		//20171123 JSAPI支付情况 去掉订单号后面的十位时间戳
		if(strlen($order_sn) > 18){
			$order_sn = substr($order_sn,0,-10);
		}
                update_pay_status($order_sn, $data); // 修改订单支付状态
		
		return true;
	}
              
}
