var TP = TP || {};
TP.Accountlogs = (function(){
        var page = 1;
        $(document).ready(function(){
        getAccountlogs();
        
                //点击更多
        $("#get-more").on('click',function(){
            getAccountlogs();
        });
        
        
        
    });
    
    /**
     * 获取我的订单信息
     * @returns {undefined}
     */
    function getAccountlogs(){
        layer.load();
        TP.POST('/Home/User/account_logs_ajax?p='+page, {}, _getAccountlogsCallBack);
        page++;
    }
    
    function _getAccountlogsCallBack(ret){
        layer.closeAll();
        if(ret.data.html){
            $("#account").append(ret.data.html);
        }else{
            $("#get-more").text('没有更多数据了');
        }
    }

    
})();

//var XimgScroll=new XimgScroll();
//XimgScroll.init({isAuto:true});