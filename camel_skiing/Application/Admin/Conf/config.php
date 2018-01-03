<?php
return array(
    'AUTH_CODE' => 'HAODUOLA',
    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__ADMINIMG__' => __ROOT__ . '/Public/admin/img',
        '__ADMINCSS__' => __ROOT__ . '/Public/admin/css',
        '__ADMINJS__' => __ROOT__ . '/Public/admin/js',
    ),
    //默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR' =>  'Public/dispatch_jump',
    //默认成功跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => 'Public/dispatch_jump',
);