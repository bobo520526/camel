<?php
namespace Admin\Controller;

use Admin\Controller\BaseController;

class IndexController extends BaseController {
    
    
    public function home(){
        //今日活动发布数
        $acresult = M('activity')->where(['create_time'=>['EGT',strtotime(date('Y-m-d'))]])->count();
        $this->assign('todayactcount',$acresult);
        //报名中活动
        $ing = M('activity')->where(['status' => 1])->count();
        $this->assign('doingcount',$ing);
        //发布活动总数
        $aacresult = M('activity')->count();
        $this->assign('actcount',$aacresult);
        
        //总报名金额
        $allmoney = M('order')->sum('price');
        $this->assign('allmoney',$allmoney);
        //今日订单数
        $orderresult = M('order')->where(['add_time'=>['EGT',strtotime(date('Y-m-d'))]])->count();
        $this->assign('todayordercount',$orderresult);
        //总订单数
        $ordercount = M('order')->count();
        $this->assign('ordercount',$ordercount);
        
        //金额出场数量        
        $result = M('order')->field('*,FROM_UNIXTIME(add_time, "%Y-%m-%d") as date')->where("FROM_UNIXTIME(add_time, '%Y-%m-%d') > ".date('Y-m-d',strtotime('-30 day')))->order('add_time asc')->select();
        $count = count($result);
        $result = array_group($result,'date');
        foreach($result as $key => $vo){
            $_key = date('d',strtotime($key));
            $arr[$_key] = array_column($vo,'price');
            $sum[$_key] = array_sum($arr[$_key]);
        }
        $this->assign('bilitu',$sum);
        $this->assign('bilitu_count',$count);
        $this->display();
    }
    
    //主题框架
    public function index(){
        $admininfo = $this->getAdminInfo();
        $act_list = session('act_list');
        $menu_list = $this->getRoleMenu($act_list);
        $this->assign('menu_list',$menu_list);
        $this->assign('admin_info',$admininfo);
        $this->display();
    }
    
    
    public function getRoleMenu($act_list)
    {
    	$modules = $roleMenu = array();
    	$rs = M('system_module')->where('level>1 AND visible=1')->order('orderby asc,mod_id ASC')->select();

    	if($act_list=='all'){
    		foreach($rs as $row){
    			if($row['level'] == 3){
    				$row['url'] = U("Admin/".$row['ctl']."/".$row['act']."");
    				$modules[$row['parent_id']][] = $row;//子菜单分组
    			}
    			if($row['level'] == 2){
    				$pmenu[$row['mod_id']] = $row;//二级父菜单
    			}
    		}
    	}else{
    		$act_list = explode(',', $act_list);
    		foreach($rs as $row){
    			if(in_array($row['mod_id'],$act_list)){
    				$row['url'] = U("Admin/".trim($row['ctl'])."/".$row['act']."");
    				$modules[$row['parent_id']][] = $row;//子菜单分组
    			}
    			if($row['level'] == 2){
    				$pmenu[$row['mod_id']] = $row;//二级父菜单
    			}
    		}
    	}
    	$keys = array_keys($modules);//导航菜单
    	foreach ($pmenu as $k=>$val){
    		if(in_array($k, $keys)){
    			$val['submenu'] = $modules[$k];//子菜单
    			$roleMenu[] = $val;
    		}
    	}

    	return $roleMenu;
    }
    
        /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){  
            $table = I('table'); // 表名
            $id_name = I('id_name'); // 表主键id名
            $id_value = I('id_value'); // 表主键id值
            $field  = I('field'); // 修改哪个字段
            $value  = I('value'); // 修改字段值                        
            M($table)->where("$id_name = $id_value")->save(array($field=>$value)); // 根据条件保存修改的数据
    }
    
}