// +----------------------------------------------------------------------
// | LengdoFrame - 图片动画效果对象
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


var Img = {
    /**
     * 滑动背景图片(根据鼠标相对于容器位置比例)
     *
     * @params obj  obj  背景的容器对象
     * @params obj  e    对象的事件源
     */
    slideBgAuto : function( obj, e ){
        /* 事件源兼容 */
        e = window.event || e;

        /* 获取背景图片的宽度和高度 */
        var img = document.createElement("IMG");
        img.src = obj.style.backgroundImage.substring(4,obj.style.backgroundImage.lastIndexOf(')'));

        var img_w = img.width;
        var img_h = img.height;

        /* 获取容器的宽度和高度 */
        var obj_w = obj.offsetWidth-1;
        var obj_h = obj.offsetHeight-1;

        /* 获取当前鼠标位置 */
        var x = typeof(e.offsetX) == 'number' ? e.offsetX : (e.layerX - obj.offsetLeft);
        var y = typeof(e.offsetY) == 'number' ? e.offsetY : (e.layerY - obj.offsetTop);

        /* 设置容器背景图位置 */
        obj.style.backgroundPosition = (-parseInt(x*(img_w-obj_w)/obj_w)) +'px '+ (-parseInt(y*(img_h-obj_h)/obj_h)) + 'px';
    },

	/**
	 * 自动滑动图片(根据鼠标相对于容器位置比例)
	 *
     * @params obj  obj  背景的容器对象
     * @params obj  e    对象的事件源	 
	 */
	 slideImgAuto : function( img, e ){
	 	/* 基础样式 */
	 	if( img.parentNode.style.position != 'relative' ) img.parentNode.style.position = 'relative';
	 	if( img.style.position != 'relative' ) img.style.position = 'relative';

        /* 事件源兼容 */
        e = window.event || e;

        /* 获取图片的宽度和高度 */
        var img_w = img.width;
        var img_h = img.height;

        /* 获取容器的宽度和高度 */
        var obj_w = img.parentNode.offsetWidth-1;
        var obj_h = img.parentNode.offsetHeight-1;

	    /* 获取当前鼠标位置 */
		var l = isNaN(parseInt(img.style.left)) ? 0 : parseInt(img.style.left);
		var t = isNaN(parseInt(img.style.top))  ? 0 : parseInt(img.style.top);
		
        var x = (typeof(e.offsetX) == 'number' ? e.offsetX : e.layerX) + l;
        var y = (typeof(e.offsetY) == 'number' ? e.offsetY : e.layerY) + t;

		img.style.top  = -parseInt(y*(img_h-obj_h)/obj_h) + 'px';
		img.style.left = -parseInt(x*(img_w-obj_w)/obj_w) + 'px';
	 }
}