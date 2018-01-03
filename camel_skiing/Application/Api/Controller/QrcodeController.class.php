<?php

/**
 * 20161108
 */

namespace Api\Controller;
use Think\Controller;
use Common\Library\QRcode;
use Think\Exception;

/**
 * Description of EmpiricService
 *
 * @author zhouy
 */
class QrcodeController extends Controller
{
    /**
     * 为用户分配二维码
     * @param $uid
     * @param int $type 
     */
    public function createQrcode($uid, $type = 2)
    {
        $data = [
            'uid' => $uid,
            'type' => $type,
            'action_param' => U('/Home/Account/reg', ['scene' => $uid],true,true)
        ];
        /******************生成创建二维码***********************/
        //路径
        $path = 'Uploads/qrcode/'.$uid .'/'. date('Y/m/d/') ;
        //创建文件夹
        !is_dir($path) && mkdir($path,0777,true);
        //名称
        $filename = randCode( 16 ) . '.png';
        //生成二维码
        $errorCorrectionLevel = 'L';//容错级别 
        $matrixPointSize = 20;//生成图片大小 
        QRcode::png($data['action_param'], $path.$filename, $errorCorrectionLevel, $matrixPointSize);
        $data['path'] = '/'.$path.$filename;
        return $data['path'];
    }
}
