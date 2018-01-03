var TP = TP || {};
/**
 * 倒计时
 * @param {type} options
 * @returns {undefined}
 */
$.fn.countdown = function (options) {
    var config = $.extend({
        second: 300,
        callback: function () {
            alert(1);
        }
    }, options || {});
    countdown(config.second, $(this));

    function countdown(total_micro_second, _obj) {
        dateformat(total_micro_second, _obj)
        if (total_micro_second <= 0) {
            config.callback('完成');
            return;
        }
        setTimeout(function () {
            total_micro_second -= 10;
            countdown(total_micro_second, _obj);
        }, 10);
    }

    function dateformat(micro_second, _obj) {
        var second = Math.floor(micro_second / 1000);      // 小时位
        var hr = Math.floor(second / 3600);      // 分钟位
        var min = Math.floor((second - hr * 3600) / 60);      // 秒位
        var sec = (second - hr * 3600 - min * 60);// equal to => var sec = second % 60;
        // 毫秒位，保留2位
        var micro_sec = Math.floor((micro_second % 1000) / 10);
        var html = hr + "小时" + min + "分" + sec + "秒" ;

        _obj.text(html);
    }
}

TP.GET = function(url, data, sucfunc, option){
    var url = url.indexOf('http') !== -1 ? url : __SITE__ + '/' + url;
    url = url.indexOf('?') !== -1 ? url : url + '?';
    url += '&_r=' + Math.random();

    var option = option || {};
    $.ajax({
        type : 'GET',
        url : url,
        data : data,
        dataType : 'json',
        success : function(ret){
            if(!TOPYS.RESPONSE(ret)) return false;
            sucfunc(ret);
        },
        error : function(){
            layer.msg('服务器异常，请稍候再试');
        }
    });
};

TP.POST = function(url, data, sucfunc, option){
    var url = url.indexOf('http') !== -1 ? url : __SITE__ + (url.indexOf('/') == 0? '' : '/') + url;
    url = url.indexOf('?') !== -1 ? url : url + '?';
    url += '&_r=' + Math.random();
    var option = $.extend({
        beforeSend : function(){
        }
    },option || {});

    $.ajax({
        type : 'POST',
        url : url,
        data : data,
        dataType : 'json',
        beforeSend:option.beforeSend,
        success : function(ret){
            if(ret.status == -1){
                layer.alert(ret.info, {icon: 4,})
                return ;
            }
            sucfunc(ret);
        },
        error : function(){
            layer.msg('服务器异常，请稍候再试',{
                time: 2000,
            });
        }
    });
};


/**
 * 账户激活
 * @type undefined
 */
TP.USER_ACTIVATION = function(){
    $(document).ready(function() {
        $('#user_activation').on('click',function(){
            layer.alert('开通理财账户'+__USER_ACTIVATE_AMOUNT__+'元，您确定激活理财账户？',{
                btn:['确定','取消']
            },function(){
                TP.POST('Home/User/activation',{},function(ret){
                    if(ret.status){
                        layer.msg(ret.info,{icon:1});
                    }else{
                        layer.msg(ret.info,{icon:2});
                    }
                });
            });
        })
    })
}();


TP.USER_LOGIN = function(){
    
    $(document).ready(function(){
        $("#LoginForm").on('submit',function(){
            var _form = $(this);
            var username = _form.find('input[name=username]').val(),
            password = _form.find('input[name=password]').val();
            if(username == ''){
                layer.msg('请输入用户名',{icon:0});
            }
            
            if(password == ''){
                layer.msg('请输入密码',{icon:0});
            }
            
            TP.POST($(this).prop('action'),$(this).serialize(),function(ret){
                if(ret.status){
                    layer.msg('登录成功',{icon:6,time:1500});
                    setTimeout(function(){
                        window.location.reload()
                    },1500);
                }else{
                    _form.find('button').removeClass('layui-btn-disabled').addClass('layui-btn-normal');
                    layer.msg(ret.info,{icon:2});
                }
            },{
                beforeSend:function(){
                    _form.find('button').removeClass('layui-btn-normal').addClass('layui-btn-disabled');
                }
            })
        })
    })
}();