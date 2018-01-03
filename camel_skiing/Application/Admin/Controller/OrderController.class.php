<?php
namespace Admin\Controller;
use Admin\Controller\BaseController;

class OrderController extends BaseController {

    public function index() {
        if (IS_AJAX) {
            $param = json_decode(file_get_contents('php://input'), true);
            $bool = 'tor.pay_status = 1 AND tor.is_del = 0 AND a.is_del = 0 ';
            
            if ($param['search']) {
                $bool .= ' AND order_sn like "%' . $param['search'] . '%"';
            }
            
            #排序
            $new_order = $param['sort'];
            $asc_desc = $param['order'];
            $order = "{$new_order} {$asc_desc}";
            if ($new_order == "") {
                $order = 'order_sn ASC';
            }
            
            //范围查找
            if (!empty($param['start']) && !empty($param['end'])) {
                $bool .= ' AND (tor.add_time >= ' . strtotime($param['start']) . ' AND tor.add_time <= ' . strtotime($param['end'] . ' 23:59:59') . ')';
            }

            //活动主题查找
            if ($param['theme_name']) {
                $bool .= ' AND a.theme_name like "%' . $param['theme_name'] . '%"';
            }
            //比赛选项查找
            if ($param['attr_value']) {
                $bool .= ' AND t.attr_value like "%' . $param['attr_value'] . '%"';
            }
            
            if ($param['order_status']) {
                if($param['order_status'] == 1){
                    $bool .= ' AND (tor.order_status= 1 OR tor.order_status= 2 OR tor.order_status= 3 OR tor.order_status= 4)';
                }else if($param['order_status'] == 5){
                    $bool .= ' AND (tor.order_status= 5 OR tor.order_status= 6)';
                }
            }

            $result = M('order')
                    ->alias('tor')
                    ->field('tor.*,a.theme_name,t.attr_value')
                    ->join([
                        ['__ACTIVITY__ AS a ON a.id = tor.activity_id', 'INNER'],
                        ['__ACTIVITY_ATTR__ AS t ON t.attr_id = tor.activity_attr_id', 'INNER']
                    ])
                    ->limit($param['offset'], $param['limit'])
                    ->order($order)
                    ->where($bool)
                    ->selectCount();
            $this->ajaxReturn($result);
        }
        $this->display();
    }
    
    public function orderDetail(){
        $id = I('get.order_id');
        
        $orderinfo = M('order_user')
                    ->alias('ou')
                    ->field('ou.*,a.theme_name,t.attr_value')
                    ->join([
                        ['__ACTIVITY__ AS a ON a.id = ou.activity_id', 'INNER'],
                        ['__ACTIVITY_ATTR__ AS t ON t.attr_id = ou.activity_attr_id', 'INNER']
                    ])
                    ->where(['ou.order_sn'=>$id, 'ou.is_del' => 0, 'a.is_del'=>0])
                    ->select();
        $this->assign('order',$orderinfo);
        $this->display();
    }
    /**
     * 编辑页面
     */
    public function edit() {
        $id = I('get.id', 0);
        if ($id) {
            $info = M('order_user')
                    ->where(['ou_id' => $id, 'is_del' => 0])
                    ->find();
            $this->assign('info', $info);
        }
        $this->display();
    }

    /**
     * 修改用户报名信息
     */
    public function handle() {
        $post = I('post.');
        $oudata = M('order_user')->where(['ou_id' => $post['ou_id']])->find();
        if(!$oudata)
            $this->error ('该订单不存在！');
        $savedata = array(
                'true_name' => $post['true_name'],
                'mobile' => $post['mobile'],
                'wechat' => $post['wechat'],
                'id_card' => $post['id_card'],
                'sex' => $post['sex']
            );
            $ed = M('order_user')->where(['ou_id' => $post['ou_id']])->save($savedata);
            $ed && $this->success('处理成功', U('orderDetail', array('order_id' => $oudata['order_sn'])));
    }
    
/**
 * 删除用户订单详情表
 */
    public function remove() {
        $id = I('post.id');
        M('order_user')->where(['ou_id' => $id])->save(['is_del' => 1]);
        jsonReturn(1, '删除成功');
    }
    
    /**
     * 主订单删除
     */
    public function removeAll(){
        $id = I('post.id');
        $delorder = M('order')->where(['order_sn' => $id])->save(['is_del' => 1]);
        //删除主订单的同时删除子订单
        if($delorder){
            M('order_user')->where(['order_sn' => $id])->save(['is_del' => 1]);
        }
        jsonReturn(1, '删除成功');
    }

}
