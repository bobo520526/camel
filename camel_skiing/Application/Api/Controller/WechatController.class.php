<?php

/**
 * 20161108
 */

namespace Api\Controller;

use Think\Controller;

/**
 * Description of EmpiricService
 *
 * @author zhouy
 */
class WechatController extends Controller
{
    /**
     * 微信请求地址
     */
    public function index(){
        echo I('get.echostr');exit();
    }
}
