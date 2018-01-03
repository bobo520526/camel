<?php

namespace Home\Controller;

use Home\Controller\BaseController;

Vendor('WxPay.WxPay#Data');
Vendor('WxPay.WxPay#Api');
Vendor('WxPay.WxPay#JsApiPay');

class IndexController extends BaseController {

    public function _initialize() {
        //parent::_initialize();
        //登录限制
        //parent::getUserInfo();
        //$user = $this->userinfo;
        //$this->userid = $user['user_id'];
        $actid = I('get.id');
        session('activity_id',$actid);
        $this->id = session('activity_id') ? session('activity_id') : 1;
        $this->userid = 1;
        $this->backurl = U('/Home/Index/detail/id/'.$this->id);
    }

    /**
     * 首页获取用户的openid
     */
    public function index() {
        #①、获取用户openid
        $this->getOpenidnow();
        redirect($this->backurl);
    }
    
    private function getOpenidnow(){
        #①、获取用户openid
        $isopenid = session('myopenid');
        if (!$isopenid) {
            $openid = I('get.openid');
            if ($openid) {
                session('myopenid', $openid);
            } else {
                redirect("http://www.8264.com/skiing_redirect.php");
            }
        }
    }

    /**
     * 滑雪报名详情
     */
    public function detail() {
        $this->getOpenidnow();
        //$id = I('id/d', 0);
        $id = $this->id;
        $where = ['id' => $id, 'is_del' => 0, 'status' => 1];
        $detail = M('activity')->where($where)->find();
        if ($detail['end_time'] < time())
            $this->error('活动已经结束');
        !$detail && $this->error('活动不存在');
        $attrinfo = M('activity_attr')->where(['act_id' => $id])->select();
        $price = [];
        foreach ($attrinfo as $k => $v) {
            $price[] = $v['attr_price'];
        }
        $pricearr['min'] = $price[array_search(min($price), $price)];
        $pricearr['max'] = $price[array_search(max($price), $price)];
        $this->assign('pricearr', $pricearr);
        $this->assign('detail', $detail);
        $this->assign('attrinfo', $attrinfo);
        $this->display();
    }

    /**
     * 获取用户openid
     * 暂时不用，已经从www.8264.com调用获取了openid
     * @return type
     */
    private function GetOpenidByJsApi() {
        #①、获取用户openid
        $tools = new \JsApiPay();
        $openId = $tools->GetOpenid();
        return $openId;
    }

    /**
     * 添加报名人数
     */
    public function payorder() {
        $data = I('get.act_attr');
        $actdata = [];
        list($actdata['act_id'], $actdata['attr_id']) = explode('-', $data);
        $backdata = $this->checkAct($actdata);
        if ($backdata['status'] == 0) {
            $this->error($backdata['info'], $this->backurl);
        }
        $this->assign('attr', $backdata['attr']);
        $this->display();
    }

    /**
     * 添加订单
     */
    public function order() {
        if (IS_AJAX) {
            /*             * *****************验证START******************** */
            //检测是否非法数据
            $data = I('post.');
            $checkdata = $this->checkAct($data);
            if ($checkdata['status'] == 0) {
                jsonReturn(0, $checkdata['info']);
            }

            if (!$this->checkUser($data['mobile']))
                jsonReturn(0, '请填写正确手机号');
            if (!$this->checkUser($data['id_card'], 1))
                jsonReturn(0, '请填写正确身份证号码');

            $order_userarr = [];
            foreach ($data['true_name'] as $k => $v) {
                $order_userarr[$k]['true_name'] = $v;
                $order_userarr[$k]['sex'] = $data['sex'][$k];
                $order_userarr[$k]['id_card'] = $data['id_card'][$k];
                $order_userarr[$k]['mobile'] = $data['mobile'][$k];
                $order_userarr[$k]['wechat'] = $data['wechat'][$k];
            }
            
            //检测数据不能有空值
            $falg = [];
            foreach ($order_userarr as $k => $v) {
                $falg[] = $this->checkUser($v, 2);
            }
            if (in_array(FALSE, $falg))
                jsonReturn(0, '所有数据必须填写');
            /*             * *****************验证END******************** */
            //事务开启
            M()->startTrans();
            $usernum = count($order_userarr);
            $oneprice = $checkdata['attr']['attr_price'];
            //新增主订单
            $allprice = $usernum * $oneprice;
            $order_data = [
                'order_sn' => getOrderSn(),
                'user_id' => $this->userid,
                'activity_id' => $checkdata['attr']['act_id'],
                'activity_attr_id' => $checkdata['attr']['attr_id'],
                'price' => $allprice,
                'user_num' => $usernum,
                'add_time' => NOW_TIME,
            ];
            $orderid = M('order')->add($order_data);
            //新增子订单
            foreach ($order_userarr as $k => $v) {
                $orderuser = [
                    'order_sn' => $order_data['order_sn'],
                    'activity_id' => $checkdata['attr']['act_id'],
                    'activity_attr_id' => $checkdata['attr']['attr_id'],
                    'true_name' => $v['true_name'],
                    'sex' => $v['sex'],
                    'mobile' => $v['mobile'],
                    'id_card' => $v['id_card'],
                    'wechat' => $v['wechat'],
                    'price' => $checkdata['attr']['attr_price'],
                    'add_time' => NOW_TIME,
                ];
                $ouid = M('order_user')->add($orderuser);
            }
            if (!empty($orderid) && !empty($ouid)) {
                M()->commit();
                $payinfo = $this->getSignPackage($order_data);
                jsonReturn(1, 'success', ['payinfo' => $payinfo, 'orderinfo' => $order_data]);
            } else {
                M()->rollback();
                jsonReturn(0, '添加失败');
            }
        }
    }

    /**
     * 支付成功
     */
    public function paySuccess() {
        //获取活动信息
        $data = I('get.');
        $where = ['id' => $data['act_id'], 'is_del' => 0, 'status' => 1];
        $actinfo = M('activity')->where($where)->find();
        if (!$actinfo) {
            $msg = ['status' => 0, 'info' => '活动不存在'];
            return $msg;
        }
        //获取订单信息
        $order = M('order')->where(['order_sn' => $data['order_sn'], 'pay_status' => 1])->find();
        if (!$order)
            $this->error('订单不存在或者未支付');
        $orderinfo = M('order_user')
                ->alias('ou')
                ->field('ou.price,ou.true_name,act.attr_value')
                ->join('__ACTIVITY_ATTR__ as act ON act.attr_id = ou.activity_attr_id', 'LEFT')
                ->where(['order_sn' => $order['order_sn']])
                ->select();
        $this->assign('detail', $actinfo);
        $this->assign('orderinfo', $orderinfo);
        $this->display('paysuccess');
    }

    /**
     * 检测活动是否存在
     * @param type $data
     * @return string|int
     */
    private function checkAct($data, $type = 0) {
        if ($data['attr_id'] == 0) {
            $msg = ['status' => 0, 'info' => '请选择比赛选项'];
            return $msg;
        }
        $where = ['id' => $data['act_id'], 'is_del' => 0, 'status' => 1];
        $back['act'] = M('activity')->where($where)->find();
        if (!$back['act']) {
            $msg = ['status' => 0, 'info' => '活动不存在'];
            return $msg;
        }
        $back['attr'] = M('activity_attr')->where(['act_id' => $data['act_id'], 'attr_id' => $data['attr_id']])->find();
        if (!$back['attr']) {
            $msg = ['status' => 0, 'info' => '活动与比赛选项不匹配不存在'];
            return $msg;
        }
        $back['status'] = 1;
        return $back;
    }

    /**
     * 检测所有手机号以及身份证号码 数据是否为空
     * @param type $mobilearr
     * @return boolean
     */
    private function checkUser($arr, $type = 0) {
        $m = TRUE;
        foreach ($arr as $k => $v) {
            if ($type == 1) {
                $m = checkIdCard($v);
            } else if ($type == 2) {
                $m = checkDataEmpty($v);
            } else {
                $m = check_mobile($v);
            }
            if (!$m) {
                return false;
            }
        }
        return $m;
    }

    /**
     * 公众号支付 js调起获取数据
     * @param type $order
     * @return type
     */
    public function getSignPackage($order) {
        #①、获取用户openid
        $tools = new \JsApiPay();
//      $openId = $tools->GetOpenid();在详情页获取了
        $openId = session('myopenid');
        file_put_contents('openiiii.txt', var_export($openId, TRUE));
        //$openId = 'o5VWIwvn-ngvYmxOriF3kZFgQ7TQ';
        #②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("支付订单：" . $order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'] . time());
        $input->SetTotal_fee($order['price'] * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url('http://skiing.8848.com/Home/Payment/Handle');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order2 = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order2);
        return $jsApiParameters;
    }

}
