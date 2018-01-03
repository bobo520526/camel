<?php
namespace Home\Controller;
use Common\Library\WeixinUser;
use Think\Controller;
class BaseController extends Controller {
    
     // 微信
    use \traits\Wechat;
    
    public $userinfo;
    
    public function _initialize(){
 
        if(!isWeixinBrowser()){
            header('Content-type:text/html;charset=utf-8');
            echo "<script>alert('请用微信扫描')</script>";
            exit(0);
        }
        $_loginInfo = $this->isLogin(); 
        if (isset($_loginInfo['uid']) && $_loginInfo['uid'] > 0) {
            $this->_userInfo = $_loginInfo;
        } else {
            $this->clearLogout();
            $this->_userInfo = $this->_autoLogin();
        }
        $this->userid = $this->_userInfo['uid'];
        
    }
    
    
    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    private function autoLogin($uid)
    {
        $_userinfo = M('user')->field('open_id,user_id')->where(['user_id'=>$uid])->find();
        return $this->_login($_userinfo);
    }
    
    private function _login($_userinfo)
    {
        if (!$_userinfo) return [];
        cookie($this->_cookieName, $_userinfo,C('ONORDER_TIME') * 3600);
        return static::isLogin();
    }   
    
        /**
     * @param bool $_depthCheck
     * @return array
     */
    public function isLogin($_depthCheck = false, $refresh = true)
    {
        $userCookie = cookie($this->_cookieName); 
        if (!$userCookie) {
            return ["uid" => 0, 'openid' => '0'];
        } else {
            return ["uid" => $userCookie['user_id'], 'openid' => $userCookie['open_id']];
        }
    }
   
    
    
    
    
    
    protected function _autoLogin()
    {
       $openId = WeixinUser::getOpenId();
        //模拟登录
        if (is_int($openId) && $openId == -1) {
            exit(0);
        }
        $_result = [];
        if ($openId) $_result = M('user')->field('open_id,user_id')->where(['open_id'=>$openId])->find();
        if ($_result) {
            return $this->_login($_result);
        } else {
            $weiXinInfo = $this->_getWeiXinInfo($openId);
            if (!$weiXinInfo['subscribe']) { //还没有关注我们
            }
            if (!empty($weiXinInfo)) {
                return $this->weiXinLogin($openId,$weiXinInfo);
            } else {
                if (I('get.refresh') < 3) {
                    redirect(U(''));
                } else {
                    $this->redirect('Share/dimension');
                    $this->display('Public/error');
                    exit(0);
                }
            }
        }
    }
    
    /**
     * 微信
     * @return array
     */
    private function _getWeiXinInfo($openId)
    {
        return WeixinUser::getWeixinUserinfo($openId);
    }
    
    /**
     * @param $openId
     * @return array|mixed
     */
    public function weiXinLogin($openId,$_weixinData)
    {
        $_userinfo = M('user')->where(['open_id'=>$openId])->find();
        if (!$_userinfo) {
            return $this->weiXinRegister($_weixinData);
        } else {
            return $this->autoLogin($_userinfo["user_id"]);
        }
    }
    
    /**
     * 微信注册用户
     * @param $username
     * @param $password
     * @param $repassword
     */
    public function weiXinRegister($weiXinInfo)
    {
         $_result_info = M('user')->where(['open_id'=>$weiXinInfo["openid"]])->find();
        
        if ($_result_info) { //如果数据存在则直接登录
            return $this->weiXinLogin($_result_info["openid"], $weiXinInfo);
        }
        //数据不存在则添加
        $UserInfo = [];
        $UserInfo['username'] = $weiXinInfo["nickname"];
        $UserInfo['open_id'] = $weiXinInfo["openid"];
        $UserInfo['head_pic'] = $weiXinInfo["headimgurl"] ? : '/Public/images/che2.jpg';
        $UserInfo['reg_time'] = NOW_TIME;
       
        /* 添加用户 */
        if (($uid = M('user')->add($UserInfo))) {
            $lastid = M('user')->getLastInsID();
            if ($uid > 0) {
                $pid = I('get.scene');
                if(M('user')->where(['user_id'=>$pid])->find()){ 
                    $pdata=array(
                        'pid'=>$pid,
                        'user_id'=>$lastid,
                    );
                    M('user_relation')->add($pdata);
                }
            
                
                return $this->autoLogin($uid);
            }
        }
        return [];
    }
    
    public function clearLogout()
    {
        cookie($this->_cookieName, null);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * 登录限制
     */
    public function getUserInfo(){
        if($this->userinfo){
            if($this->userinfo['status'] == 0){
                $this->userLogout();
                $this->error('您账号已被封号');
            }
            
        }else{
            if(IS_AJAX){
                jsonReturn(-1,'请登录');
            }else{
                $this->error('请登录',U('Home/index/index'));
            }
        }
    }
    
    
    /**
     * 用户注销
     */
    public function userLogout(){
        session('user',null);
    }
}