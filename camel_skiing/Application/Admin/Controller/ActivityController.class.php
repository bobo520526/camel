<?php

namespace Admin\Controller;

use Admin\Controller\BaseController;

class ActivityController extends BaseController {

    public function index() {
        if (IS_AJAX) {
            $param = json_decode(file_get_contents('php://input'), true);
            $bool = 'is_del = 0 ';

            if ($param['search']) {
                $bool .= ' AND theme_name like "%' . $param['search'] . '%"';
            }

            #排序
            $new_order = $param['sort'];
            $asc_desc = $param['order'];
            $order = "{$new_order} {$asc_desc}";
            if ($new_order == "") {
                $order = 'id ASC';
            }

            //范围查找【开始时间】
            if (!empty($param['start']) && !empty($param['end'])) {
                $bool .= ' AND (start_time >= ' . strtotime($param['start']) . ' AND start_time <= ' . strtotime($param['end'] . ' 23:59:59') . ')';
            }

            //活动主题查找
            if ($param['theme_name']) {
                $bool .= ' AND theme_name like "%' . $param['theme_name'] . '%"';
            }
            //活动状态查找            
            if ($param['status']) {
                $bool .= ' AND status = ' . $param['status'];
            }

            $result = M('Activity')
                    ->limit($param['offset'], $param['limit'])
                    ->order($order)
                    ->where($bool)
                    ->selectCount();
            $this->ajaxReturn($result);
        }
        $this->display();
    }

    /**
     * 发布活动
     */
    public function edit() {
        $id = I('get.id', 0);
        $pagetype = I('get.ptype',0);
        if ($id) {
            $info = M('activity')
                    ->where(['id' => $id, 'is_del' => 0])
                    ->find();
            $this->assign('info', $info);
            $attinfo = M('activity_attr')->where(['act_id' => $id])->select();
            
        }
        if($pagetype == '1'){
            $attrstr = '';
            foreach ($attinfo as $k => $v) {
                $attrstr .= $v['attr_value'].' '.$v['attr_price'].'元 , ';
            }
            $this->assign('attrstr', $attrstr);
            $this->display('detail');
        }else{
            $this->assign('attrinfo', $attinfo);
            $this->display();
        }
    }

    public function editHandle() {
        $post = I('post.');
        $att_val = array_filter($post['attr_value']);
        $att_price = $post['attr_price'];
        if (empty($att_val[0]) || empty($att_price[0]))
            $this->error('活动选项不能为空！');
        $data = [
            'theme_name' => $post['theme_name'],
            'site' => $post['site'],
            'img' => $post['img'],
            'start_time' => strtotime($post['start_time']),
            'end_time' => strtotime($post['end_time']),
            'cutoff_time' => strtotime($post['cutoff_time']),
            'create_time' => NOW_TIME,
            'content' => $post['content'],
            'link_mobile' => $post['link_mobile'],
            'status' => 1,
            'num' => $post['num'],
        ];
        if ($post['id']) {
            //已经开始的活动不能修改
            $activityinfo = M('activity')->where(['id' => $post['id']])->find();
            if($activityinfo['start_time'] < time())
                $this->error('活动已经开始不能修改', U('index'));
            M('activity')->where(['id' => $post['id']])->save($data);
            M('activity_attr')->where(['act_id' => $post['id']])->delete();
            $acid = $post['id'];
        } else {
            $acid = M('activity')->add($data);
        }
        foreach ($att_val as $k => $v) {
            $newarr['attr_value'] = $v;
            $newarr['attr_price'] = $att_price[$k];
            $newarr['act_id'] = $acid;
            M('activity_attr')->add($newarr);
        }
        $this->success('添加成功', U('index'));
    }

    /**
     * 活动删除
     */
    public function removeAll() {
        $id = I('post.id');
        $activityinfo = M('activity')->where(['id' => $id])->find();
            if($activityinfo['end_time'] > time())
                jsonReturn(0, '活动还没结束不能删除');
        $del = M('activity')->where(['id' => $id])->save(['is_del' => 1]);
        //删除主活动的同时删除活动属性
        if ($del) {
            M('activity_attr')->where(['act_id' => $id])->save(['is_del' => 1]);
        }
        jsonReturn(1, '删除成功');
    }
    
    /**
     * 关闭活动
     */
    public function close(){
        $id = I('post.id');
        M('activity')->where(['id' => $id])->save(['status' => 2]);
        jsonReturn(1, '删除成功');
    }

}
