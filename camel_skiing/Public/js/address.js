/**
 * 获取城市
 * @param t  省份select对象
 */
function get_city(t){
    var parent_id = $(t).val();
    if(!parent_id > 0){
        return;
    }
    $('#twon').empty().css('display','none');
    var url = '/Home/Api/getRegion/level/2/parent_id/'+ parent_id;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option value="0">选择城市</option>'+ v;          
            $('#city').empty().html(v);
            $('#work_city').empty().html(v);
        }
    });
}

/**
 * 获取地区
 * @param t  城市select对象
 */
function get_area(t){
    var parent_id = $(t).val();
    if(!parent_id > 0){
        return;
    }
    var url = '/Home/Api/getRegion/level/3/parent_id/'+ parent_id;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option>选择区域</option>'+ v;
            $('#district').empty().html(v);
        }
    });
}
// 获取最后一级乡镇
function get_twon(obj){
    var parent_id = $(obj).val();
    var url = '/Home/Api/getTwon/parent_id/'+ parent_id;
    $.ajax({
        type : "GET",
        url  : url,
        success: function(res) {
        	if(parseInt(res) == 0){
        		$('#twon').empty().css('display','none');
        	}else{
        		$('#twon').css('display','block');
        		$('#twon').empty().html(res);
        	}
        }
    });
}

