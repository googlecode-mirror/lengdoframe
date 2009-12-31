// +----------------------------------------------------------------------
// | LengdoFrame - 整站公用函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 常规组件函数 - 组合框 - 时间组合框
/* ------------------------------------------------------ */

/**
 * 时间组合框 - 需安装 JsCal2 组件
 *
 * @params obj  caller   调用者对象
 * @params obj  configs  时间组件的配置
 */
function deal_timecbox_show( caller, configs )
{
    /* 必要组件检测 */
    if( typeof(Calendar) != 'function' ){
        wnd_alert('Please Install "JsCal2" Plugin !'); return false;
    }

    /* 初始化配置 */
    configs = typeof(configs) == 'object' ? configs : {};

    /* 初始化配置 - 时间格式 */
    configs.format = typeof(configs.format) == 'string' ? configs.format : '%Y-%m-%d'; // [%Y-%m-%d %H:%M] 显示 [年-月-日 时-分]

    /* 初始化全局时间选择器对象 */
    if( !window.TIMECBOX_TIME_SELECTER ){
        window.TIMECBOX_TIME_SELECTER = new Calendar({'onSelect':function(){this.hide();},'align':'Br'});
    }

    /* 显示时分 */
    if( configs.format.indexOf(' %H:%M') >= 0 ){
        window.TIMECBOX_TIME_SELECTER.args.showTime = true;
        window.TIMECBOX_TIME_SELECTER.redraw();
    }

    /* 绑定显示 */
    window.TIMECBOX_TIME_SELECTER.manageFields(caller, caller.previousSibling, configs.format);
}


/* ------------------------------------------------------ */
// - 常规组件函数 - 组合框 - 文件组合框
/* ------------------------------------------------------ */

/**
 * 文件组合框 - 上传文件更改
 *
 * @params obj  caller  调用者对象
 * @params str  type    文件类型
 */
function deal_filecbox_change( caller, type )
{
    /* 未选择上传文件 */
    if( !caller.value ) return;

    /* 扩展名检查 */
    var ext = caller.value.substr( caller.value.lastIndexOf('.') ).toLowerCase();

    switch( type ){
        case 'img':
            if( ext != '.jpg' && ext != '.gif' ){
                caller.value = ''; wnd_alert('无效的图片格式！'); return ;
            }
        break;

        case 'sql':
            if( ext != '.sql' ){
                caller.value = ''; wnd_alert('无效的SQL文件格式！'); return ;
            }
        break;
    }

    /* 文本框显示 */
    var textbox = caller.parentNode;

    while( textbox = textbox.previousSibling ){
        if( textbox.tagName && textbox.tagName.toLowerCase() == 'input' && textbox.type == 'text' ){
            break;
        }
    }

    textbox.value = caller.value;
}

/**
 * 文件组合框 - 清除要上传的文件
 */
function deal_filecbox_clear( caller )
{
    /* 向上 - 清除文本框 */
    var textbox = caller;
    while( textbox = textbox.previousSibling ){
        if( textbox.tagName && textbox.type == 'text' ){
            textbox.value = ''; break;
        }
    }

    /* 向下 - 寻找文件域 */
    var overlay = textbox;
    while( overlay = overlay.nextSibling ){
        if( overlay.className && overlay.className.toLowerCase() == 'overlay' ){
            break;
        }
    }

    /* 向下 - 清除文件域 - 采用对象替换清除 */
    for( var i=0,len=overlay.childNodes.length; i < len; i++ ){
        if( overlay.childNodes[i].tagName && overlay.childNodes[i].type == 'file' ){
            var file = document.createElement('INPUT');

            file.type      = 'file';
            file.name      = overlay.childNodes[i].name;
            file.size      = 1;
            file.title     = overlay.childNodes[i].title;
            file.onchange  = overlay.childNodes[i].onchange;
            file.className = overlay.childNodes[i].className;

            overlay.childNodes[i].parentNode.replaceChild(file,overlay.childNodes[i]); break;
        }
    }
}

/**
 * 文件组合框 - 上传文件
 */
function deal_filecbox_upload( obj, msg )
{
    /* 获取表单域所在的表单 */
    form = obj;
    while( form.tagName.toLowerCase() != 'form' ){
        form = form.parentNode;
    }

    /* 获取文件表单域 */
    file = obj;
    while( file.className != 'overlay' ){
        file = file.previousSibling;
    }
    file = file.childNodes[0];

    if( !file.value ){
        wnd_alert('请选择上传文件！'); return false;
    }

    msg ? wnd_confirm(msg,{'ok':callback}) : callback();

    function callback(){
        form.onsubmit(); form.submit();
    }
}

/**
 * 文件组合框 - 已上传的文件的删除操作
 *
 * @params obj  caller   调用者对象
 * @params str  url      提交的URL
 * @params obj  configs  配置集
 *         str           configs.msg       提交前的消息提示
 *         bol           configs.merge     提交成功后合并操作框，增宽文本框。默认 true
 *         fun           configs.complete  提交成功后的回调函数
 */
function deal_filecbox_uploaded_del( caller, url, configs )
{
    /* 初始化配置集 */
    configs = typeof(configs) == 'object' && configs ? configs : {};
    configs.msg = configs.msg ? configs.msg : '确认删除文件？';

    /* 确认OK - 回调函数 */
    function confirm_callback(){
        /* 异步提交(异步等待) */
        Ajax.call(url, '', ajax_callback, 'GET', 'JSON');

        function ajax_callback( result, text ){
            if( result.message ){ 
                wnd_alert(result.message); 
            }

            if( result.error == 0 ){
                /* 隐藏上传文件的操作层 */
                caller.parentNode.style.display = 'none';

                /* 合并操作框，增宽文本框 */
                if( configs.merge !== false ){
                    /* 向上 - 查找文本框 */
                    var textbox = caller.parentNode;
                    while( textbox = textbox.previousSibling ){
                        /* 无效文本框，继续查找 */
                        if( !textbox ) continue;
                        if( !textbox.type ) continue;
                        if(  textbox.type.toLowerCase() != 'text' ) continue;

                        /* 找到文本框，跳出循环 */
                        break;
                    }
                    
                    /* 增宽文本框 */
                    textbox.style.width = parseInt(textbox.style.width) + 53 +'px';
                }

                /* 提交成功后的函数回调 */
                if( typeof(configs.complete) == 'function' ) configs.complete();
            }
        }
    }

    /* 删除提示 */
    wnd_confirm(configs.msg, {'ok':confirm_callback});
}


/* ------------------------------------------------------ */
// - 常规组件函数 - 组合框 - 数字步长组合框
/* ------------------------------------------------------ */

/**
 * 数字步长组合框 - 数字增减
 *
 * @params obj  caller  调用者对象
 * @params num  step    步长值
 * @params obj  config  
 *         mix  config.limit  上下限(步长大于0时为上限，小于0时为下限)
 *         int  config.fixed  小数点后精度长度
 */
function deal_numscbox( caller, step, config )
{
    /* 初始化参数 */
    step = typeof(step) == 'number' && isFinite(step) ? step : 0;
    config = config && typeof(config) == 'object' ? config : {};

    /* 初始化文本框对象和增减后的数字变量 */
	var tb = caller.parentNode.parentNode.cells[0].childNodes[0];
    var nm = deal_numscbox_calc( parseFloat(tb.value), step );

    /* 赋值 */
    if( typeof(nm) == 'number' && isFinite(nm) ){
        if( step > 0 && nm > config.limit ) return false;
        if( step < 0 && nm < config.limit ) return false;

        tb.value = config.fixed ? nm.toFixed(config.fixed) : nm;
    }else{
        return false;
    }
}

/**
 * 数字步长组合框 - 浮点数精确计算
 */
function deal_numscbox_calc( num, step )
{
    var ln, ls, m;

    try{ ln = num.toString().split(".")[1].length;  }catch(e){ ln = 0 }    
    try{ ls = step.toString().split(".")[1].length; }catch(e){ ls = 0 }

    m = Math.pow( 10, Math.max(ln,ls) );

    return ( parseInt(num*m)+parseInt(step*m) )/m;
}


/* ------------------------------------------------------ */
// - 常规组件函数 - 组合框 - 按钮效果
/* ------------------------------------------------------ */

/**
 * 组合框按钮效果
 */
function deal_combobox_mouseover( obj )
{
    var cls = obj.className;

    obj.onmouseover = function(){
        this.className = cls +' '+ cls +'over';
    }

    obj.onmousedown = function(){
        this.className = cls +' '+ cls +'down';
    }

    obj.onmouseup = function(){
        this.className = cls +' '+ cls +'over';
    }

    obj.onmouseout = function(){
        this.className = cls;
    }

    obj.onfocus = function(){
        this.blur();
    }

    obj.onmouseover();
}


/* ------------------------------------------------------ */
// - 常规组件函数 - Tag Title
/* ------------------------------------------------------ */

/**
 * 显示 Tag Title 层
 */
function tagtitle( e, title, config )
{
    /* 无效参数 */
    if( title.replace(/^\s*|\s*$/g, '') == '' ) return false;

    /* 初始化事件对象和事件源对象 */
    var event = e || window.event;
    var caller = window.ActiveXObject ? event.srcElement : event.target;

    /* 初始化配置参数 */
    config = typeof(config) == 'object' && config ? config : {};

    /* 初始化标签TITLE层 */
    var div = tagtitle_init(config);

    /* 获取鼠标的坐标并定位 */
    var mouse = tagtitle_mouse(e);

    div.style.top = mouse.y + (window.ActiveXObject ? document.documentElement.scrollTop-document.documentElement.clientTop : 0) + 23 + 'px';
    div.style.left = mouse.x - (window.ActiveXObject ? 2 : 0) + 'px';

    /* 设置标签TITLE层内容 */
    div.innerHTML = title;

    /* 显示标签TITLE层 */
    div.style.display = '';

    /* 设置标签TITLE层消失事件 */
    if( !caller.onmouseout ){
        caller.onmouseout = function(){
            div.style.display = 'none';
        }
    }
}

/**
 * 初始化 Tag Title 层
 */
function tagtitle_init( config )
{
    var div = document.getElementById('tagtitle-div');

    /* 创建层 */
    if( !div ){
        /* 构造层 */
        div = document.createElement('DIV');
        
        /* 追加层到BODY */
        document.body.appendChild(div);
    }

    /* 设置层属性 */
    div.id = 'tagtitle-div';
    div.className = 'tagtitle-div' + (config.className?(' '+config.className):'');
    div.style.width = isNaN(config.width) ? 'auto' : (parseInt(config.width)+'px');

    /* 返回层 */
    return div;
}

/**
 * 返回鼠标的坐标
 */
function tagtitle_mouse( e )
{
    return {
        'x': e.pageX ? e.pageX : e.clientX, 
        'y': e.pageY ? e.pageY : e.clientY
    };
}


/* ------------------------------------------------------ */
// - 常规组件函数 - Tabbar
/* ------------------------------------------------------ */

/**
 * Tabbar切换函数
 *
 * @params str  tabitems_id  Tabbar选项集ID
 * @params str  tabbodys_id  Tabbar内容集ID
 * @params obj  event        兼容事件对象
 * @params int  index        Tabbar选项的索引号
 */
function tabbar( tabitems_id, tabbodys_id, event, index )
{
    /* 发生事件源的Tabitem对象 */
    var obj = tabbar_tabitem_evtsrc(tabitems_id, event, index);

    /* 无效的事件源Tabitem对象 */
    if( obj.className == 'on' || obj.tagName.toLowerCase() != 'span' ) return false;

    var tabitem = document.getElementById(tabitems_id).childNodes[0];
    var tabbody = document.getElementById(tabbodys_id).childNodes[0];

    do{
        /* 无效Tabbar项 */
        if( !tabitem.tagName || tabitem.tagName.toLowerCase() != 'span' ) continue;

        /* 过滤空白节点 */
        while( tabbody && (!tabbody.tagName || tabbody.tagName.toLowerCase() != 'div') ){
            tabbody = tabbody.nextSibling;
        }

        /* 撤销已选中项 */
        if( tabitem.className == 'on' ){
            tabitem.className = ''; 
            tabbody ? tabbody.style.display = 'none' : '';
        }

        /* 设置新选中项 */
        if( tabitem == obj ){
            tabitem.className = 'on';
            tabbody ? tabbody.style.display = 'block' : '';
        }

        tabbody = tabbody ? tabbody.nextSibling : tabbody;
    }while( tabitem = tabitem.nextSibling );
}
/**
 * 发生事件源的TABITEM项对象
 */
function tabbar_tabitem_evtsrc( tabitems_id, event, index )
{
    /* 初始化索引 */
    index = typeof(index) == 'number' && index > 0 ? index : false;

    /* 获取索引对于的Tabbar选项对象 */
    if( index > 0 ){
        var tabitems = document.getElementById(tabitems_id).childNodes;

        for( var i=0,j=tabitems.length; i < j; i++ ){
            if( !tabitems[i].tagName || tabitems[i].tagName.toLowerCase() != 'span' ) continue;
            if( --index == 0 ) return tabitems[i];
        }
    }

    /* 默认返回发生事件源的Tabitem对象 */
    return window.ActiveXObject ? window.event.srcElement : event.target;
}

/**
 * TABITEM滑动
 */
function tabbar_tabitem_slide( caller, tabitems_id, lftrht, step )
{
    /* 初始化 */
    var tabitems  = document.getElementById(tabitems_id);
    var tabsilde  = tabitems.parentNode;

    /* 初始化 */
    step = typeof(step) == 'number' && step > 0 ? step : 80;

    /* 初始化TABITEM层的宽度 */
    if( tabitems.className.indexOf(' TABBAR_TABITEM_WIDTH_INIT') == -1 ){
        tabbar_tabitem_width_init(caller, tabitems_id);
    }

    /* 获取 margin-left */
    var marginlft = parseInt(tabitems.style.marginLeft);
    marginlft = marginlft < 0 ? marginlft : 0;

    /* 初始化 maring-left */
    tabitems.style.marginLeft = marginlft + 'px';

    /* 向左滑动 */
    if( lftrht == 'left' ){
        tabitems.style.marginLeft = (marginlft>-step ? 0 : (marginlft+step)) + 'px';
    }
    /* 向右滑动 */
    else if( lftrht == 'right' && tabitems.offsetWidth > tabsilde.offsetWidth ){
        tabitems.style.marginLeft = (tabitems.offsetWidth-tabsilde.offsetWidth<step-marginlft ? tabsilde.offsetWidth-tabitems.offsetWidth : marginlft-step)+'px';
    }
}

/**
 * 初始化TABITEM层的宽度
 */
function tabbar_tabitem_width_init( caller, tabitems_id )
{
    /* 初始化 */
    var width = 0;
    var tabitems = document.getElementById(tabitems_id);
    var tabchild = tabitems.childNodes;
    
    /* 累加宽度值 */
    for( var i=0,j=tabchild.length; i < j; i++ ){
        width += tabchild[i].offsetWidth > 0 ? tabchild[i].offsetWidth : 0;
    }
    
    /* 设置属性 */
    tabitems.style.width = width + 'px';
    tabitems.className += ' TABBAR_TABITEM_WIDTH_INIT';
}


/* ------------------------------------------------------ */
// - 常规组件函数 - 树型表格
/* ------------------------------------------------------ */

/**
 * 点击树型表格
 */
function tabletree_click( obj )
{
    /* 初始化 */
    var tbl, tr, td, tdi, i, len, i, llen;

	/* 向上递归找到 TD, TR, TABLE 对象 */
	while( obj.tagName.toLowerCase() != 'table' ){
        if( obj.tagName.toLowerCase() == 'td' ) td = obj;
        if( obj.tagName.toLowerCase() == 'tr' ) tr = obj;

		obj = obj.parentNode;
	}

    tbl = obj;

    /* 获取事件发生源所在的列索引号 */
    for( i=0,len=tr.cells.length; i < len; i++ ){
        if( tr.cells[i] == td ){
            tdi = i; break;
        }
    }

	/* 初始化 */
	var cnt = 0;
    var dis = '';
    var fnd = false;
    var lvl = parseInt(tr.className);

    for( i=0,len=tbl.rows.length; i < len; i++ ){
        if( tbl.rows[i] == tr ){
			if( (i+1) == tbl.rows.length ) break;
            fnd = true;
        }

        else if( fnd == true ){
            var cur = parseInt(tbl.rows[i].className);

            if( cur <= lvl ) break;

			if( cnt++ == 0 ){
				tbl.rows[i].style.display = tbl.rows[i].style.display != 'none' ? 'none' : '';
				dis = tbl.rows[i].style.display;
			}else{
			    tbl.rows[i].style.display = dis;
			}

            for( ii=0,llen=tbl.rows[i].cells[tdi].childNodes.length; ii < llen; ii++ ){
                if( tbl.rows[i].cells[tdi].childNodes[ii].nodeType == 1 ){
                    tbl.rows[i].cells[tdi].childNodes[ii].className = 'minus'; break;
                }
            }
        }
    }

	if( cnt == 0 ) return ;

    for( i=0,len=tr.cells[tdi].childNodes.length; i < len; i++ ){
        if( tr.cells[tdi].childNodes[i].nodeType == 1 ){
            tr.cells[tdi].childNodes[i].className = dis == 'none' ?  'plus' : 'minus'; break;
        }
    }
}


/* ------------------------------------------------------ */
// - 系统窗口 - 需加载 window.js
/* ------------------------------------------------------ */

function wnd_wait( msg, configs )
{ 
    configs = typeof(configs) == 'object' && configs ? configs : {};
    
    configs.zindex   = 50;
    configs.button   = '';
    configs.titleact = 0;

    wnd_sysmsg(msg, configs, 'wait');
}
function wnd_wait_clear()
{ 
    var wnd = Wnds.find('wnd-sysmsg-wait'); 

    if( wnd ) wnd.hidden(); 
}

function wnd_alert( msg, configs, active )
{ 
    configs = typeof(configs) == 'object' && configs ? configs : {};

    configs.zindex   = 51;
    configs.button   = 'ok';
    configs.titleact = 1;

    wnd_sysmsg(msg, configs, 'alert', active);
}

function wnd_confirm( msg, configs, active )
{
    configs = typeof(configs) == 'object' && configs ? configs : {};

    configs.zindex   = 51;
    configs.button   = 'ok&cannel';
    configs.titleact = 1;

    wnd_sysmsg(msg, configs, 'confirm', active);
}

/**
 * 系统提示窗口
 *
 * @params str  msg      消息内容
 * @params obj  configs  窗口配置
 * @params str  type     窗口类型
 * @params str  active   激活窗口控制区按钮(按钮索引，false表示不启用，默认激活'ok'按钮)
 */
function wnd_sysmsg( msg, configs, type, active )
{
    /* 初始化 */
    var wnd = Wnds.find('wnd-sysmsg-'+type);

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-sysmsg-'+type, null, {'width':420, 'titleact':configs.titleact}); 

        wnd.create(); 
        wnd.zindex(configs.zindex);
        wnd.buttonAddDefault(configs.button);
    }

    /* 配置窗口 - 初始数据 */
    var html    = '<div class="wnd-client-sysmsg"><table><tr><td class="i"><i class="plaint"></i></td><td class="t">'+ msg +'</td></tr></table></div>';

    var title   = configs.title ? configs.title : '系统消息';
    var overlay = typeof(configs.overlay) == 'number' ? configs.overlay : 40;

    /* 配置窗口 - 窗口回调 */
    wnd.callback('ok'      , (typeof(configs.ok)      == 'function' ? configs.ok      : function(){}) );
    wnd.callback('okb'     , (typeof(configs.okb)     == 'function' ? configs.okb     : function(){}) );
    wnd.callback('hidden'  , (typeof(configs.hidden)  == 'function' ? configs.hidden  : function(){}) );
    wnd.callback('cannel'  , (typeof(configs.cannel)  == 'function' ? configs.cannel  : function(){}) );
    wnd.callback('cannelb' , (typeof(configs.cannelb) == 'function' ? configs.cannelb : function(){}) );

    /* 配置窗口 - 数据设置 */
    wnd.title(title, 'plaint');
    wnd.inner(html, 'html');
    wnd.overlay(overlay);

    /* 显示窗口 */
    wnd.show();

    /* 激活窗口控制区按钮 */
    if( active !== false ){
        /* 初始化 */
        active = typeof(active) == 'string' ? active : 'ok';
        keypress = active == 'ok' ? function(e){if(e.keyCode==27)this.cannel()} : null;

        /* 激活 */
        wnd.buttonActive(active, keypress);
    }
}


/* ------------------------------------------------------ */
// - 表单功能函数 - 部分函数需加载 window.js
/* ------------------------------------------------------ */

/**
 * 通用 - 构建表单的参数
 *
 * @params mix  form  表单对象或者表单ID
 *
 * @return str  数据经过URL编码
 */
function deal_form_params( form )
{
    /* 初始化参数 */
    var params = '';

    /* 表单对象 */
    form = typeof(form) == 'object' ? form : document.getElementById(form);
    
    /* 构建参数 */
    for( var i=0,len=form.length; i < len; i++ ){
        /* 无效的表单域名称 */
        if( !form[i].name ) continue;

        /* 过滤特殊情况 */
        if( form[i].type == 'radio' || form[i].type == 'checkbox' ){
            if( !form[i].checked ) continue;
        }

        /* 构建参数 */
        params += '&'+ form[i].name +'='+ encodeURIComponent(form[i].value);
    }

    return params;
}

/**
 * 模拟异步提交表单。
 * 注：该函数并不提交表单，只是为表单的模拟异步创建条件
 *
 * @params obj  form     表单对象
 * @params str  url      提交的URL地址
 * @params obj  configs  完成后回调
 *         str           configs.msg       等待时提示消息，false表示不显示
 *         str           configs.rtype     响应的数据类型，JSON(默认) TEXT
 *         fun           configs.complete  完成时回调的函数
 */
function deal_form_submit( form, url, configs )
{
    /* 初始化 */
    configs = typeof(configs) == 'object' && configs ? configs : {};

	/* 获取IFRAME */
	var iframe = deal_ajax_iframe();
    
    /* 设置IFRAME加载函数 */
    deal_ajax_iframe_attribs( {'onload':function(){deal_form_submit_complete(form,url,configs)}} )

    /* 初始化表单参数 */
    form.action   = url;
	form.target   = iframe.name;

    /* 初始化表单常量 */
    form.method   = 'post';
	form.encoding = 'multipart/form-data';

    /* 显示等待消息 */
    if( configs.msg !== false ) wnd_wait( configs.msg ? configs.msg : '请稍等！数据提交中....' );
}
function deal_form_submit_complete( form, url, configs )
{
    /* 清除窗口 */
    wnd_wait_clear();

    /* 读取响应内容并JSON化 */
    try{
        var text = deal_ajax_iframe().contentWindow.document.body.innerHTML;
        var result = text;

        /* 解决FF下由于文件上传表单域导致返回的数据加上<pre>标签的BUG */
        if( result.indexOf('<pre>') != -1 && result.substr(0, 5) == '<pre>' ){
            result = result.substring(5, result.length-6);
        }

        /* 格式化JSON数据 */
        if( configs.rtype != 'TEXT' ){
            result = eval('('+ result +')');
        }
    }catch(e){
        wnd_alert('数据解析出错！<br />'+result); return false;
    }

    if( typeof(configs.complete) == 'function' ){
        configs.complete(result, text, form);
    }
}


/**
 * 窗口表单默认键盘事件
 *
 * @params obj event   事件对象
 * @params obj wndele  窗口内元素
 */
function deal_wfm_keyboard( event, wndele )
{
    /* 获取事件源所在窗口对象 */
    var wnd = Wnds.findByElement(wndele);

    /* 回车 Enter */
    if( event.keyCode == 13 ){
        /* 事件发生源 */
        var src = event.target ? event.target : window.event.srcElement;

        /* 回车不提交的事件源 */
        if( src.tagName && src.tagName.toLowerCase() == 'textarea' ){
            return false;
        }

        /* 事件发生源失去焦点 */
        src.blur();

        /* 记录失去焦点的对象 */
        wnd.setData('blur', src);

        /* 调用窗口的确定函数 */
        wnd.ok();
    }

    /* 取消 Esc */
    else if( event.keyCode == 27 ){
        /* 调用窗口的取消函数 */
        wnd.cannel();
    }
}


/* ------------------------------------------------------ */
// - 模拟异步IFRAME
/* ------------------------------------------------------ */

/**
 * 获取模拟异步的IFRAME
 */
function deal_ajax_iframe()
{
	/* 获取IFRAME */
	var iframe = document.getElementById('deal-ajax-iframe');

    /* 返回IFRAME */
    if( iframe ) return iframe;

    /* 创建IFRAME容器层 */
    var div = document.createElement('DIV');
    div.style.display = 'none';

    /* 追加IFRAME容器层到DOM */
    document.body.appendChild(div);

    /* 创建IFRAME */
    div.innerHTML = '<IFRAME id="deal-ajax-iframe" name="deal-ajax-iframe"></IFRAME>';

    /* 返回IFRAME */
	return document.getElementById('deal-ajax-iframe');
}

/**
 * 设置模拟异步的IFRAME属性
 */
function deal_ajax_iframe_attribs( attribs )
{
	/* 获取IFRAME */
	var iframe = deal_ajax_iframe();

    /* 初始化 */
    attribs = typeof(attribs) == 'object' && attribs ? attribs : {};

    /* 移除，绑定 onload 属性 */
    if( iframe.onload ){
        window.ActiveXObject ? iframe.detachEvent('onload',iframe.onload) : iframe.removeEventListener('load',iframe.onload,true);
    }
    if( typeof(attribs.onload) == 'function' ){
        window.ActiveXObject ? iframe.attachEvent('onload',attribs.onload) : iframe.addEventListener('load',attribs.onload,true);
    }

    /* 绑定 src 属性 */
    if( typeof(attribs.src) == 'string' && attribs.src != '' ){
        iframe.src = attribs.src +'&'+ Math.random();
    }
}


/* ------------------------------------------------------ */
// - 页面加载中功能函数
/* ------------------------------------------------------ */

function deal_webpage_load()
{
    document.getElementById('dloading-div').style.display = 'block';
}

function deal_webpage_loaded()
{
    document.getElementById('dloading-div').style.display = 'none';
}


/* ------------------------------------------------------ */
// - Javascript数据格式化
/* ------------------------------------------------------ */

/**
 * 变量值格式化
 *
 * @params mix  value   要修饰的值
 * @params str  modify  修饰类型
 *
 * @return str  返回修饰后的字符
 */
function f( value, modify )
{
    /* 格式化 */
    switch( modify ){
        /* html 编码( & , " , < , > , 空格, 换行 ) */
        case 'html': value = value.replace(/\&|\"|\<|\>| |\n/g, f_html_match); break;

        /* 清除空白符 */
        case 'trim': value = value.replace(/^\s*|\s*$/g, ''); break;
    }

    return value;
}
function f_html_match( match )
{
    switch( match ){ 
        case '<'  : return '&lt;';
        case '>'  : return '&gt;';
        case '&'  : return '&amp;';
        case "'"  : return '&#39;';
        case ' '  : return '&nbsp;';
        case '"'  : return '&quot;'; 
        case '\n' : return '<br />';
    }

    return match;
}


/* ------------------------------------------------------ */
// - 开启IE6下背景缓存
/* ------------------------------------------------------ */

try{ document.execCommand('BackgroundImageCache', false, true);  }catch(e){}