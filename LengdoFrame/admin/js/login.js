// +----------------------------------------------------------------------
// | LengdoFrame - 后台登陆函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/**
 * 提交登陆
 */
function deal_login_fill()
{
    /* 回调函数 */
    function callback( result, text ){
        if( result.error == 0 ){
            /* 显示页面加载中 */
            deal_webpage_load();

            /* 页面跳转 */
            window.location.href = 'index.php';
        }else{
            wnd_alert(result.message, {'overlay':0});
        }
    }

    /* 异步提交(异步等待) */
    Ajax.call('index.php?act=loginsubmit', deal_form_params('fm-login-fill'), callback, 'POST', 'JSON');
}