/**
 * 查看图片
 */
function wnd_image_view( url, width, height )
{
    if( typeof(url) != 'string' || url == '' ){
        wnd_alert('未指定图片路径'); return;
    }else{
        var ext = url.substr( url.lastIndexOf('.') ).toLowerCase();

        if( ext != '.jpg' && ext != '.gif' ){
            wnd_alert('无效的图片格式'); return ;
        }
    }

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

	wnd.inner('<img onmousemove="Anime.slideImgAuto(this,event)" src="'+ url +'" style="cursor:crosshair"/>', 'html');
    wnd.show();
}