<?php
/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
function adminLog($log_info){
    $add['log_time'] = time();
    $add['admin_id'] = session('admin_id');
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = __ACTION__;
    M('admin_log')->add($add);
}


function getAdminInfo($admin_id){
	return D('admin')->where("admin_id=$admin_id")->find();
}

 
/**
 * 面包屑导航  用于后台管理
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_admin()
{        
    $navigate = include APP_PATH.'Admin/Conf/navigate.php';    
    $location = strtolower('Admin/'.CONTROLLER_NAME);
    $arr = array(
        '后台首页'=>'javascript:void();',
        $navigate[$location]['name']=>'javascript:void();',
        $navigate[$location]['action'][ACTION_NAME]=>'javascript:void();',
    );
    return $arr;
}

/**
 * 导出excel
 * @param $strTable	表格内容
 * @param $filename 文件名
 */
function downloadExcel($strTable,$filename)
{
	header("Content-type: application/vnd.ms-excel");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=".$filename."_".date('Y-m-d').".xls");
	header('Expires:0');
	header('Pragma:public');
	echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$strTable.'</html>';
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 根据id获取地区名字
 * @param $regionId id
 */
function getRegionName($regionId){
    $data = M('region')->where(array('id'=>$regionId))->field('name')->find();
    return $data['name'];
}

function encrypt($str){
	return md5(C("AUTH_CODE").$str);
}

// 定义一个函数getIP() 客户端IP，
if(!function_exists('getIP')){
    function getIP(){            
        if (getenv("HTTP_CLIENT_IP"))
             $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
                $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("REMOTE_ADDR"))
             $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow";
        return $ip;
    }
}
// 服务器端IP
if(!function_exists('serverIP')){
    function serverIP(){   
     return gethostbyname($_SERVER["SERVER_NAME"]);   
    }
}

/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名 
 */
function convert_arr_key($arr, $key_name)
{
	$arr2 = array();
	foreach($arr as $key => $val){
		$arr2[$val[$key_name]] = $val;        
	}
	return $arr2;
}


function time2second($time){
//    $seconds = (int)$seconds;
//    if( $seconds<60 ){
//            $format_time = gmstrftime('%S秒', $seconds);
//    }elseif($seconds>60 && $seconds<3600){
//            $format_time = gmstrftime('%M分%S秒', $seconds);
//    }elseif($seconds<86400){//如果不到一天
//            $format_time = gmstrftime('%H时%M分%S秒', $seconds);
//    }else{
//            $time = explode(' ', gmstrftime('%j %H %M %S', $seconds));//Array ( [0] => 04 [1] => 14 [2] => 14 [3] => 35 ) 
//            $format_time = ($time[0]-1).'天'.$time[1].'时'.$time[2].'分'.$time[3].'秒';
//    }
//    return iconv('GBK','utf8',$format_time);
    
    if (is_numeric($time)) {
        $value = array(
            "years" => 0, "days" => 0, "hours" => 0,
            "minutes" => 0, "seconds" => 0,
        );
        if ($time >= 31556926) {
            $value["years"] = floor($time / 31556926);
            $time = ($time % 31556926);
        }
        if ($time >= 86400) {
            $value["days"] = floor($time / 86400);
            $time = ($time % 86400);
        }
        if ($time >= 3600) {
            $value["hours"] = floor($time / 3600);
            $time = ($time % 3600);
        }
        if ($time >= 60) {
            $value["minutes"] = floor($time / 60);
            $time = ($time % 60);
        }
        $value["seconds"] = floor($time);
        //return (array) $value;
        $t = ($value["years"] ? $value["years"] . "年": '' ) .
                ($value["days"] ? $value["days"] . "天" : '' ) .
                ($value["hours"] ? $value["hours"] . "时" : '' ) .
                ($value["minutes"] ? $value["minutes"] . "分" : '' ) .
                ($value["seconds"] ? $value["seconds"] . "秒" : '' );
                
        Return $t;
    } else {
        return '';
    }
}




/** 
 * 求一个数的平方 
 * @param $n 
 */  
function sqr($n){  
    return $n*$n;  
}  
  
/** 
* 生产min和max之间的随机数，但是概率不是平均的，从min到max方向概率逐渐加大。 
* 先平方，然后产生一个平方值范围内的随机数，再开方，这样就产生了一种“膨胀”再“收缩”的效果。 
*/    
function xRandom($bonus_min,$bonus_max){  
    $sqr = intval(sqr($bonus_max-$bonus_min));  
    $rand_num = rand(0, ($sqr-1));  
    return intval(sqrt($rand_num));  
}  
  
 /** 
 *   
 * @param $bonus_total 红包总额 
 * @param $bonus_count 红包个数 
 * @param $bonus_max 每个小红包的最大额 
 * @param $bonus_min 每个小红包的最小额 
 * @return 存放生成的每个小红包的值的一维数组 
 */    
function getBonus($bonus_total, $bonus_count, $bonus_max, $bonus_min) {    
    $result = array();    
  
    $average = $bonus_total / $bonus_count;    
  
    $a = $average - $bonus_min;    
    $b = $bonus_max - $bonus_min;    
  
    //    
    //这样的随机数的概率实际改变了，产生大数的可能性要比产生小数的概率要小。    
    //这样就实现了大部分红包的值在平均数附近。大红包和小红包比较少。    
    $range1 = sqr($average - $bonus_min);    
    $range2 = sqr($bonus_max - $average);    
  
    for ($i = 0; $i < $bonus_count; $i++) {    
        //因为小红包的数量通常是要比大红包的数量要多的，因为这里的概率要调换过来。    
        //当随机数>平均值，则产生小红包    
        //当随机数<平均值，则产生大红包    
        if (rand($bonus_min, $bonus_max) > $average) {    
            // 在平均线上减钱    
            $temp = $bonus_min + xRandom($bonus_min, $average);    
            $result[$i] = $temp;    
            $bonus_total -= $temp;    
        } else {    
            // 在平均线上加钱    
            $temp = $bonus_max - xRandom($average, $bonus_max);    
            $result[$i] = $temp;    
            $bonus_total -= $temp;    
        }    
    }    
    // 如果还有余钱，则尝试加到小红包里，如果加不进去，则尝试下一个。    
    while ($bonus_total > 0) {    
        for ($i = 0; $i < $bonus_count; $i++) {    
            if ($bonus_total > 0 && $result[$i] < $bonus_max) {    
                $result[$i]++;    
                $bonus_total--;    
            }    
        }    
    }    
    // 如果钱是负数了，还得从已生成的小红包中抽取回来    
    while ($bonus_total < 0) {    
        for ($i = 0; $i < $bonus_count; $i++) {    
            if ($bonus_total < 0 && $result[$i] > $bonus_min) {    
                $result[$i]--;    
                $bonus_total++;    
            }    
        }    
    }    
    return $result;    
}  
