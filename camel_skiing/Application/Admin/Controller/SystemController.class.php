<?php

namespace Admin\Controller;

use Admin\Controller\BaseController;

class SystemController extends BaseController{
    
    public function index(){
        $this->display();
    }
    
    
    /*
    * 新增修改配置
    */
    public function handle() {
        $param = I('post.');
        $inc_type = $param['inc_type'];
        //unset($param['__hash__']);
        unset($param['inc_type']);
        tpCache($inc_type, $param);
        $this->success("操作成功", U('System/index', array('inc_type' => $inc_type)));
    }
    
    public function clearCache(){
        delFile(RUNTIME_PATH);
        $this->success('缓存清除成功',U('Index/index'));
        
    }
    
    /**
     * 菜单栏目
     */
    public function menu(){
    	$this->assign('tree',$this->tree());
    	$this->display();
    }
    
	public function create_menu(){
		$this->assign('tree',$this->tree());
		$action = I('get.action','add');
		$mod_id = I('get.mod_id',0);
		if($mod_id>0){
			$menu = D('system_module')->where("mod_id=$mod_id")->find();
			$this->assign('menu',$menu);
		}
		$this->assign('pid',$mod_id);		
		$this->assign('tree',$this->tree());
		$this->assign('action',$action);
		$this->display();
	}
    
	public function menuSave(){
		$data = I('post.');
		if($data['action'] == 'add'){
			if($data['mod_id']>0 || $data['parent_id']>0){
				$data['level'] = 2;
				$data['module'] = 'menu';				
			}else{
				$data['level'] = 1;
				$data['module'] = 'top';
			}
			unset($data['mod_id']);
			$r = D('system_module')->add($data);
		}
		if($data['action'] == 'edit'){
			$r = D('system_module')->where('mod_id='.$data['mod_id'])->save($data);
		}

		if($data['action'] == 'del'){
			$res = D('system_module')->where('parent_id='.$data['mod_id'])->select();
			if($res){
				$res = array('stat'=>'fail','msg'=>'要删除的菜单中含有子项目,请先移动或删除子项目');
				exit(json_encode($res));
			}else{
				$r = D('system_module')->where('mod_id='.$data['mod_id'])->delete();				
			}
		}
		
		if($r){	
			adminLog('管理系统菜单',__ACTION__);
			$res = array('stat'=>'ok');
		}else{
			$res = array('stat'=>'fail','msg'=>'操作失败');
		}
		exit(json_encode($res));
	}
    
	public function module(){
		$this->assign('tree',$this->tree());
		$this->display();
	}
	
        /**
         * 添加模块
         */
	public function ctl_detail(){
		$mod_id = I('get.mod_id');		
		$tree = $this->tree();
		$rs = D('system_module')->order('mod_id ASC')->select();
		if($rs){
			foreach($rs as $k=>$v){
				if($v['parent_id'] == $mod_id && $v['module'] == 'module'){
					$modules[$k] = $v;
				}
			}
			$this->assign('pid',$mod_id);
			$this->assign('modules',$modules);
		}
		$this->assign('menu_tree',$this->tree());
		$this->display();
	}

        /**
         * 模块保存
         */
	public function ctlSave(){
		$data = I('post.');
		$t = false;
		if($data['module']){
			foreach ($data['module'] as $k=>$v){
				$v['visible'] = empty($v['visible']) ? 0 : 1;
				$r = D('system_module')->where("mod_id=$k")->save($v);
				if($r) $t = $r;
			}
		}
				
		if($data['data']){
			foreach ($data['data'] as  $k=>$v){
				if($v['title'] && $v['ctl'] && $v['act']){
					$v['level'] = 3;
					$v['module'] = 'module';
					$v['orderby'] = $v['orderby'] ? $v['orderby'] : 50;
					$r = D('system_module')->add($v);
				}
			}
		}
		if($r || $t){
			$res = array('stat'=>'ok');
		}else{
			$res = array('stat'=>'fail');
		}
		exit(json_encode($res));
	}
	
        
	public function tree()
	{
		$modules = array();
		$rs = D('system_module')->where(['is_show'=>1])->order('mod_id ASC')->select();
		if(is_array($rs)){
			foreach($rs as $row){
				$modules[$row['mod_id']] = $row;
			}		
		}
		if($modules){
			$tree = array();
			foreach($modules as $k=>$v){
				if($v['module'] == 'top'){
					$tree[$k] = $v;
				}
			}
			foreach($modules as $k=>$v){
				if($v['module'] == 'menu'){
					$tree[$v['parent_id']]['menu'][$k] = $v;
				}
			}
			foreach($modules as $k=>$v){
				if($v['module'] == 'module'){
					$ppk = $modules[$v['parent_id']]['parent_id'];
					$tree[$ppk]['menu'][$v['parent_id']]['menu'][$k] = $v;
				}
			}
			return $tree;
		}
		return false;
	}

}
