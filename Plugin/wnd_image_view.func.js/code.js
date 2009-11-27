/**
 * 查看图片(窗口) v1.0.0
 *
 * @params str  url    图片地址
 * @params int  width  窗口宽度，默认520
 * @params int  height 窗口高度，默认300
 */
function wnd_image_view( url, width, height )
{
    /* 无效参数 */
    if( typeof(url) != 'string' || url == '' ){
        wnd_alert('未指定图片路径'); return false;
    }else{
        var ext = url.substr( url.lastIndexOf('.') ).toLowerCase();

        if( ext != '.jpg' && ext != '.gif' ){
            wnd_alert('无效的图片格式'); return false;
        }
    }

    /* 查找窗口 */
    var wnd = Wnds.find('wnd-image-view');

    /* 构建窗口 */
	if( !wnd ){
        width  = width  > 0 ? width  : 520;
        height = height > 0 ? height : 300;

		wnd = new Wnd('wnd-image-view', null, {'width':width, 'height':height, 'overflow':11, 'control':'ok'});
		wnd.create();

		wnd.title('查看图片');
        wnd.zindex(50);
	}else{
        if( width  ) wnd.width(width);
        if( height ) wnd.height(height);
    }
    
    /* 写入窗口内容 */
    if( typeof(Anime) == 'object' && typeof(Anime.slideImgAuto) == 'function' ){
	    wnd.inner('<img onmousemove="Anime.slideImgAuto(this,event)" src="'+ url +'" style="cursor:crosshair"/>', 'html');
    }else{
        wnd.overflow(1100);
        wnd.inner('<img src="'+ url +'"/>', 'html');
    }

    /* 显示窗口 */
    wnd.show();
}