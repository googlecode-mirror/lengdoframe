// +----------------------------------------------------------------------
// | LengdoFrame - 标题按钮函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/**
 * 基本按钮初始化
 */
function tbtn_init( caller )
{
	/* 基本动作样式 */
	caller.onmouseover = function(){ this.className = 'btn btnover'; }
	caller.onmouseout  = function(){ this.className = 'btn'; }
	caller.onmousedown = function(){ this.className = 'btn btndown'; }
	caller.onmouseup   = function(){ this.className = 'btn btnover'; }

	/* 显示移动时样式 */
	caller.onmouseover();
}
/**
 * 点击下拉按钮初始化 - 有分割线
 */
function tbtn_init_cddl( caller )
{
	/* 基本动作样式 */
	caller.onmouseover = function(){ this.className = 'btn btnover btnddll'; }
	caller.onmouseout  = function(){ this.className = 'btn'; }
	caller.onmousedown = function(){ this.className = 'btn btndown btnddll'; }
	caller.onmouseup   = function(){ this.className = 'btn btnover btnddll'; }

	/* 阻塞按钮层的click事件 */
	var click = caller.onclick;
	caller.onclick = function(ee){ click(); try{ window.event.cancelBubble = true; }catch(e){ ee.stopPropagation(); } }

	/* 显示移动时样式 */
	caller.onmouseover();
}
/**
 * 移动下拉按钮初始化
 */
function tbtn_init_mdd( caller, id )
{
	/* 下拉列表 */
	var ddl = document.getElementById(id);

	/* 基本动作样式 */
	caller.onmouseout  = function(){ this.className = 'btn'; ddl.className = 'ddl'; }
	caller.onmouseover = function(){ try{document.onclick()}catch(e){}; this.className = 'btn btnover'; ddl.className = 'ddl ddlon'; }

    /* 下拉列表点击事件 */
    ddl.onclick = function(){ caller.onmouseout(); }

	/* 显示移动时样式 */
	caller.onmouseover();
}


/**
 * 点击下拉按钮 - 显示下拉列表
 */
function tbtn_cdd( evt, id )
{
	/* document.onclick重写前执行上一个onclick */
	try{ document.onclick(); }catch(e){}

	/* 设置下拉列表样式 */
	var ddl = document.getElementById(id);
	ddl.className = 'ddl ddlon';

	/* 设置按钮对象事件 */
	var mout = ddl.parentNode.onmouseout;
	ddl.parentNode.onmouseover();
	ddl.parentNode.onmouseout = null;

	/* 阻塞此次click事件 */
	try{ window.event.cancelBubble = true; }catch(e){ evt.stopPropagation(); }

	/* 设置下拉列表点击事件阻塞 */
	ddl.onclick     = function(ee){ try{ window.event.cancelBubble = true; }catch(e){ ee.stopPropagation(); } try{document.onclick()}catch(e){}; }
	ddl.onmousedown = function(ee){ try{ window.event.cancelBubble = true; }catch(e){ ee.stopPropagation(); } }

	/* 设置下拉列表消失的事件 */
	document.onclick = function(){
        try{
            ddl.className = 'ddl';
            ddl.parentNode.className  = 'btn';
            ddl.parentNode.onmouseout = mout;
        }catch(e){}
	}
}
/**
 * 点击下拉按钮 - 左侧部分点击
 */
function tbtn_cddl_ldown( evt, lobj )
{
	/* document.onclick重写前执行上一个onclick */
	try{ document.onclick(); }catch(e){}

	/* 阻塞此次mousedown事件 */
	try{ window.event.cancelBubble = true; }catch(e){ evt.stopPropagation(); }

	/* 设置鼠标点下时的按钮层样式 */
	lobj.parentNode.className = 'btn btnover btnldown btnddll';
}
/**
 * 点击下拉按钮 - 右侧部分点击
 */
function tbtn_cddl_rdown( evt, robj )
{
	/* 阻塞此次mousedown事件 */
	try{ window.event.cancelBubble = true; }catch(e){ evt.stopPropagation(); }

	/* 设置鼠标点下时的按钮层样式 */
	robj.parentNode.className = 'btn btnover btnrdown btnddll';
}