var TP = TP || {};
TP.Mytask = (function() {
    var page = 1;
    $(document).ready(function() {
        getMyorder();

        //切换选项卡 固定  随机
        $('.weui-navbar__item').on('click', function() {
            page = 1;
            $(this).addClass('weui-bar__item_on').siblings('.weui-bar__item_on').removeClass('weui-bar__item_on');
            $("#task_list").html('');
            var ttype = $(this).attr('data-tid');
            $("#ta_type").val(ttype);
            getMyorder(ttype);
            $("#get-more").text('更多记录');
        });


        //点击更多
        $("#get-more").on('click', function() {
            var ttype = $("#ta_type").val();
            getMyorder(ttype);
        });
    
        //倒计时
//        $('.lingqu_time').countdown({
//            second:{:(strtotime(date('Y-m-d 23:59:59')) - time()) * 1000},
//        callback:function(){
//            
//        }
//        });


    });

    /**
     * 获取我的订单信息
     * @returns {undefined}
     */
    function getMyorder(ttype) {
        layer.load();
        //var ttype = 1;
        TP.POST('/Home/Index/index_ajax?p=' + page, {'task_type': ttype}, _getMyorderCallBack);
        page++;
    }

    function _getMyorderCallBack(ret) {
        layer.closeAll();
        if (ret.data.html) {
            $("#task_list").append(ret.data.html);
        } else {
            $("#get-more").text('没有更多数据了');
        }
    }


})();

//var XimgScroll=new XimgScroll();
//XimgScroll.init({isAuto:true});