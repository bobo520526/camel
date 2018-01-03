<?php

namespace Admin\Controller;

use Admin\Controller\BaseController;

class UploadController extends BaseController {

    public function image() {
        $upload = new \Think\Upload(); // 实例化上传类
        $path = I('get.path', 'temp');
        $upload->maxSize = 3145728; // 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
        $upload->savePath = $path . '/'; // 设置附件上传（子）目录
        $upload->subName = array('date', 'Y/m-d');
        // 上传文件 
        $info = $upload->upload();

        if (!$info) {// 上传错误提示错误信息
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => $upload->getError()
            ));
        } else {// 上传成功
            foreach ($info as $item) {
                $this->ajaxReturn(array(
                    'status' => 1,
                    'info' => '上传成功',
                    'data' => $item
                ));
            }
        }
    }

    public function crop() {
        $image = new \Think\Image();
        $pic = $_POST['pic'];
        if (!file_exists('.' . $pic)) {
            jsonReturn(0, '图片不存在');
        }
        list($name, $suffix) = explode('.', $pic, 2);

        $size = $_POST['size'];
        if (!$size['w'] || !$size['h']) {
            jsonReturn(0, '请选择截取区域');
        }
        //将图片裁剪为400x400并保存为corp.jpg
        $image->open(ltrim($pic, '/'));

        $save_path = '.' . $name . '_thumb.' . $suffix;
        $image->crop($size['w'], $size['h'], $size['x'], $size['y'])->save($save_path);
        if (!file_exists($save_path)) {
            jsonReturn(0, '截取失败');
        } else {
            //{"status":"0","info":"\u88c1\u526a\u6210\u529f","data":{"thumb":"\/attachments\/1610\/10\/57fb65c641032.jpg"}}
            $this->ajaxReturn(array(
                'status' => 1,
                'info' => '裁剪成功',
                'data' => ['thumb' => '/' . ltrim($save_path, './')]
            ));
        }
    }

    /*
     * $img_path 被压缩的图片的路径
     * $thumb_w 压缩的宽
     * $save_path 压缩后图片的存储路径
     * $is_del 是否删除原文件，默认删除
     */

    public function thumb_img($thumb_w = 700) {
        $image = new \Think\Image();
        $img_path = $_FILES['pic']['tmp_name'];
        $image->open($img_path);
        $width = $image->width(); // 返回图片的宽度
        if ($width > $thumb_w) {
            $width = $width / $thumb_w; //取得图片的长宽比
            $height = $image->height();
            $thumb_h = ceil($height / $width);
        }

        list($name, $suffix) = explode('.', $_FILES['pic']['name'], 2);
        $name = time();
        $path = I('get.path', 'temp');
        $savePath = $path . '/' . date('Y') . '/' . date('m-d'); // 设置附件上传（子）目录
        $save_path = 'Uploads/' . $savePath . '/' . $name . '.' . $suffix;
        //如果文件路径不存在则创建
        $save_path_info = pathinfo($save_path);
        if (!is_dir($save_path_info['dirname']))
            mkdir($save_path_info['dirname'], 0777, TRUE);
        $info = $image->thumb($thumb_w, $thumb_h)->save($save_path);
        if (!file_exists($save_path)) {// 上传错误提示错误信息
            jsonReturn(0, '上传失败');
        } else {// 上传成功
            $this->ajaxReturn(array(
                'status' => 1,
                'info' => '成功',
                'data' => '/' . $save_path
            ));
        }
    }

}
