var TP = TP || {};
TP.Myorder = (function(){
        var page = 1;
        $(document).ready(function(){
        getMyorder();
        
       $(document).on('click','.get_money',function(){
                    TP.POST(
                            $(this).attr('data-url'),
                         {'order_sn':$(this).attr('data-tid')},
                            function(ret) {
                                if (ret.status == 1) {
                                    layer.msg(ret.info, {time: 2000, icon: 6}, function() {
                                       // $(this).removeClass('get_money');
                                       // $(this).css('background-color','#D0D0D0');
                                       location.href = "/Home/User/my_order";
                                    })
                                } else {
                                    layer.alert(ret.info, {icon: 2})
                                }
                            });
       })
        
                //点击更多
        $("#get-more").on('click',function(){
            getMyorder();
        });
        
        
        
    });
    
    /**
     * 获取我的订单信息
     * @returns {undefined}
     */
    function getMyorder(){
        layer.load();
        TP.POST('/Home/User/my_order_ajax?p='+page, {}, _getMyorderCallBack);
        page++;
    }
    
    function _getMyorderCallBack(ret){
        layer.closeAll();
        if(ret.data.html){
            $("#myorder_list").append(ret.data.html);
        }else{
            $("#get-more").text('没有更多数据了');
        }
    }

    
})();

//var XimgScroll=new XimgScroll();
//XimgScroll.init({isAuto:true});