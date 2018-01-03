<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace traits;

trait Instance
{
    protected static $instance = null;

    // 实例化（单例）
    /**
     * @param array $options
     * @return $this
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($options);
            if(method_exists(self::$instance,'_initialize')){
                self::$instance->_initialize();
            }
        }
        return self::$instance;
    }
    
    
    // 静态调用
    public static function __callStatic($method, $params)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        $call = substr($method, 1);
        if (0 === strpos($method, '_') && is_callable([self::$instance, $call])) {
            return call_user_func_array([self::$instance, $call], $params);
        } else {
            throw new \think\Exception("not exists method:" . $method);
        }
    }
}
