/**
 * ͼ��鿴
 */
function wnd_image_view( url, width, height )
{
    if( typeof(url) != 'string' || url == '' ){
        wnd_alert('δָ��ͼ��·��'); return;
    }else{
        var ext = url.substr( url.lastIndexOf('.') ).toLowerCase();

        if( ext != '.jpg' && ext != '.gif' ){
            wnd_alert('��Ч��ͼƬ��ʽ'); return ;
        }
    }

    var wnd = Wnds.find('wnd-image-view');

    /* �������� */
	if( !wnd ){
        width  = width  > 0 ? width  : 520;
        height = height > 0 ? height : 300;

		wnd = new Wnd('wnd-image-view', null, {'width':width, 'height':height, 'overflow':11, 'control':'ok'});
		wnd.create();

		wnd.title('���ͼƬ');
        wnd.zindex(50);
	}else{
        if( width  ) wnd.width(width);
        if( height ) wnd.height(height);
    }

	wnd.inner('<img onmousemove="Img.slideImgAuto(this,event)" src="'+ url +'" style="cursor:crosshair"/>', 'html');
    wnd.show();
}