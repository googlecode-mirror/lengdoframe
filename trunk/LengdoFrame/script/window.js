// +----------------------------------------------------------------------
// | LengdoFrame - 窗口类
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


var Wnds = {
    /**
     * 窗口对象集合
     */
    wnds : {},

    /**
     * 查找已创建的窗口
     *
     * @params mix  id  窗口ID
     *
     * @return obj  窗口对象，如果失败返回null
     */
    find : function( id ){
        /* 根据窗口ID返回窗口对象 */
        if( typeof(id) == 'string' ){
            return this.wnds[id] ? this.wnds[id] : null;
        }

        return null;
    },

    /**
     * 查找已创建的窗口
     *
     * @params mix  obj  窗口内元素对象或者ID
     *
     * @return obj  窗口对象，如果失败返回null
     */
    findByElement : function( obj ){
        /* 获取对象 */
        if( typeof(obj) == 'string' ){
            obj = document.getElementById(obj);
        }

        /* 无效的对象 */
        if( !obj.parentNode ) return null;

        /* 递归查找窗口对象 */
        while( obj = obj.parentNode ){
            if( obj.className && obj.className == 'wnd-div' ){
                return typeof(this.wnds[obj.id]) == 'object' ? this.wnds[obj.id] : null;
            }
        }

        return null;
    }
}


/**
 * 窗口类
 *
 * @params  str  id                  窗口ID
 * @params  obj  callbacks           回调函数集
 *          fun  callbacks.ok        当点击确定，窗口消失后调用此函数
 *          fun  callbacks.okb       当点击确定，窗口消失前调用此函数 - 当返回false时，ok函数终止
 *          fun  callbacks.cannel    当点击取消，窗口消失后调用此函数
 *          fun  callbacks.cannelb   当点击取消，窗口消失前调用此函数 - 当返回false时，cannel函数终止
 *          fun  callbacks.hidden    但窗口隐藏时调用此函数
 *          fun  callbacks.complete  当窗口加载完成后调用此函数
 * @params  obj  configs             配置集
 *          str  configs.title       窗口标题
 *          int  configs.width       窗口宽度。        默认：200                                               注：窗口客户区宽度=窗口宽度-2
 *          int  configs.height      窗口客户区高度。  默认：auto
 *          int  configs.overlay     窗口遮掩层透明度。false表示不显示，0-100表示透明度
 *          int  configs.titleact    窗口标题栏的操作按钮 000(最小化，最大化，关闭)。默认001                   注：前辍0省略
 *          int  configs.overflow    窗口客户区溢出时滚动条，默认0000(scroll-x，scroll-y，hidden-x，hidden-y)  注：前辍0省略
 */
function Wnd( id, callbacks, configs ){
    /* 初始化参数 - 配置 */
    if( typeof(configs) != 'object' || !configs ) configs = {};

    this.sId           = id;
    this.sTitle        = typeof(configs.title)     == 'string' ? configs.title     : '';
    this.iWidth        = typeof(configs.width)     == 'number' ? configs.width     : 200;
    this.iHeight       = typeof(configs.height)    == 'number' ? configs.height    : 'auto';
    this.iOverlay      = typeof(configs.overlay)   == 'number' ? configs.overlay   : (configs.overlay===false ? false : 40);
    this.iTitleAct     = typeof(configs.titleact)  == 'number' ? configs.titleact  : 1;
    this.iOverflow     = typeof(configs.overflow)  == 'number' ? configs.overflow  : 0;


    /* 初始化参数 - 回调函数 */
    if( typeof(callbacks) != 'object' || !callbacks ) callbacks = {};

    this.fOk           = typeof(callbacks.ok)       == 'function' ? callbacks.ok       : function(){};
    this.fOkBefore     = typeof(callbacks.okb)      == 'function' ? callbacks.okb      : function(){};
    this.fCannel       = typeof(callbacks.cannel)   == 'function' ? callbacks.cannel   : function(){};
    this.fCannelBefore = typeof(callbacks.cannelb)  == 'function' ? callbacks.cannelb  : function(){};
    this.fHidden       = typeof(callbacks.hidden)   == 'function' ? callbacks.hidden   : function(){};
    this.fComplete     = typeof(callbacks.complete) == 'function' ? callbacks.complete : function(){};


    /* 初始化参数 - 内部参数 */
    this.iTop          = 0;    //窗口Top
    this.iLeft         = 0;    //窗口Left
    this.oData         = {};   //自定义数据
    this.sState        = '';   //窗口状态

    this.oWnd          = null; //窗口对象
    this.oOverlay      = null; //遮掩层对象

    this.oTitle        = null; //标题层
    this.oTitleDivs    = {};   //标题层Div对象集合

    this.oClient       = null; //客户区对象

    this.oControl      = null; //控制区
    this.oControlBtns  = {};   //控制区按钮对象集
}


/* ------------------------------------------------------ */
// - 窗口配置
/* ------------------------------------------------------ */

/**
 * 设置/返回窗口标题
 *
 * @params str  text  要显示的文本，undefined表示返回标题文本
 * @params str  icon  图标的样式
 *
 * @return str  返回标题
 *              设置标题时返回旧的标题
 */
Wnd.prototype.title = function( text, icon ){
    /* 返回标题文本 */
    if( typeof(text) == 'undefined' ) return this.sTitle;

    /* 拷贝，更新配置 */
    var src = this.sTitle;
    this.sTitle = typeof(text) == 'string' ? text : '';

    /* 初始化图标参数 */
    icon = typeof(icon) == 'string' ? ('<i class="'+ icon +'"></i>') : '';

    /* 设置标题 */
    this.oTitleDivs['title'].innerHTML = icon + this.sTitle + '&nbsp;';

    /* 返回 */
    return src;
}

/**
 * 设置/返回窗口宽度
 *
 * @params int  width  宽度值，undefined表示返回宽度
 *
 * @return int  返回宽度
 *              设置宽度时返回旧的宽度
 */
Wnd.prototype.width = function( width ){
    /* 返回宽度 */
    if( typeof(width) == 'undefined' ) return this.iWidth;

    /* 拷贝，更新配置 */
    var src = this.iWidth;
    this.iWidth = typeof(width) == 'number' && width >= 2 ? width : 200;

    /* 设置宽度 */
    this.oWnd.style.width    = this.iWidth + 'px';
    this.oClient.style.width = this.iWidth - 2 + 'px';

    /* 返回 */
    return src;
}

/**
 * 设置/返回窗口客户区高度
 *
 * @params int  height  int或'auto'表示设置高度，undefined表示返回高度
 *
 * @return int  返回高度
 *              设置高度时返回旧的高度
 */
Wnd.prototype.height = function( height ){
    /* 返回高度 */
    if( typeof(height) == 'undefined' ) return this.iHeight;

    /* 拷贝，更新配置 */
    var src = this.iHeight;
    this.iHeight = typeof(height) == 'number' && height >= 0 ? height : 'auto';

    /* 设置高度 */
    this.oClient.style.height = this.iHeight == 'auto' ? 'auto' : (this.iHeight+'px');

    /* 返回 */
    return src;
}

/**
 * 返回客户区对象
 */
Wnd.prototype.client = function(){
    return this.oClient;
}

/**
 * 设置/返回窗口zindex
 *
 * @params int  zindex  int表示设置zindex，undefined表示返回zindex
 *
 * @return int  返回zindex
 *              设置zindex时返回旧的zindex
 */
Wnd.prototype.zindex = function( zindex ){
    /* 返回zindex */
    if( typeof(zindex) == 'undefined' ) return this.oWnd.style.zIndex;

    /* 拷贝配置 */
    var src = this.oWnd.style.zIndex;
    zindex = typeof(zindex) == 'number' && zindex > 0 ? zindex : 0; 

    /* 设置zindex */
    this.oWnd.style.zIndex = zindex;
    this.oOverlay ? this.oOverlay.style.zIndex = zindex : '';

    /* 返回 */
    return src;
}

/**
 * 设置/返回窗口遮掩层透明度
 *
 * @params mix  overlay  int表示设置透明度，false表示隐掉overlay，undefined表示返回透明度
 *
 * @return int  返回透明度
 *              设置透明度时返回旧的透明度
 */
Wnd.prototype.overlay = function( overlay ){
    /* 返回窗口遮掩层透明度 */
    if( typeof(overlay) == 'undefined' ) return this.iOverlay;

    /* 窗口遮掩层存在检查 */
    if( !this.oOverlay ) return false;

    /* 拷贝，更新配置 */
    var src = this.iOverlay;
    this.iOverlay = typeof(overlay) == 'number' && overlay >= 0 ? overlay : (overflay===false ? false : 40);

    /* 设置窗口遮掩层透明度 */
    if( this.iOverlay === false ){
        this.oOverlay.style.display = 'none';
    }
    else{
        this.oOverlay.style.filter  = 'alpha(opacity='+ this.iOverlay +')';
        this.oOverlay.style.opacity = this.iOverlay/100;
        this.oOverlay.style.display = '';
    }

    /* 返回 */
    return src;
}

/**
 * 设置/返回窗口客户区溢出时滚动条
 *
 * @params int  overflow  溢出时滚动条标识码，undefined表示返回标识码
 *                        xxxx(scroll-x，scroll-y，hidden-x，hidden-y). 注：前辍0省略
 *
 * @return int  返回滚动条标识码
 *              设置滚动条标识码时返回旧的标识码
 */
Wnd.prototype.overflow = function( overflow ){
    /* 返回窗口溢出时滚动条显示情况 */
    if( typeof(overflow) == 'undefined' ) return this.iOverflow;

    /* 拷贝，更新配置 */
    var src = this.iOverflow;
    this.iOverflow = typeof(overflow) == 'number' && overflow >= 0 ? overflow : 0;

    /* 设置窗口溢出时滚动条 */
    this.oClient.style.overflowX = parseInt(overflow%100/10) ? 'hidden' : (parseInt(overflow/1000) ? 'scroll' : '');
    this.oClient.style.overflowY = overflow%10 ? 'hidden' : (parseInt(overflow%1000/100) ? 'scroll' : '');

    /* 返回 */
    return src;
}

/**
 * 设置/返回窗口回调函数
 *
 * @params str  type  回调函数类型
 * @params fun  func  回调函数，undefined表示返回回调函数
 *
 * @return fun
 */
Wnd.prototype.callback = function( type, func ){
    if( type == 'ok'       ) return (typeof(func) == 'function' ? this.fOk           = func : this.fOk);
    if( type == 'okb'      ) return (typeof(func) == 'function' ? this.fOkBefore     = func : this.fOkBefore);
    if( type == 'cannel'   ) return (typeof(func) == 'function' ? this.fCannel       = func : this.fCannel);
    if( type == 'cannelb'  ) return (typeof(func) == 'function' ? this.fCannelBefore = func : this.fCannelBefore);
    if( type == 'hidden'   ) return (typeof(func) == 'function' ? this.fHidden       = func : this.fHidden);
    if( type == 'complete' ) return (typeof(func) == 'function' ? this.fComplete     = func : this.fComplete);

    return false;
}


/* ------------------------------------------------------ */
// - 窗口数据
/* ------------------------------------------------------ */

/**
 * 设置/返回自定义数据
 *
 * @params str  index  数据索引
 * @params mix  value  数据值
 */
Wnd.prototype.setData = function( index, value ){
    this.oData[index] = value;
}
Wnd.prototype.getData = function( index ){
    return this.oData[index];
}


/* ------------------------------------------------------ */
// - 窗口按钮
/* ------------------------------------------------------ */

/**
 * 设置/返回控制区按钮
 *
 * @params  str  index   控制区按钮索引
 * @params  str  attrib  控制区按钮属性，undefined表示返回控制区按钮对象
 * @params  mix  value   控制区按钮属性值，undefined表示返回控制区按钮属性值
 *
 * @return int  返回控制区按钮对象，属性值
 *              设置属性值时返回旧的属性值
 */
Wnd.prototype.button = function( index, attrib, value ){
    /* 返回控制区按钮对象 */
    if( typeof(attrib) == 'undefined' ) return this.oControlBtns[index];

    /* 返回控制区按钮属性值 */
    if( typeof(value) == 'undefined' ) return this.oControlBtns[index] ? this.oControlBtns[index][attrib] : 'undefined';

    /* 拷贝，设置控制区按钮属性 */
    if( this.oControlBtns[index] ){
        var vsrc = this.oControlBtns[index][attrib]
        this.oControlBtns[index][attrib] = value;
    }

    /* 返回 */
    return vsrc;
}

/**
 * 增加控制区按钮
 *
 * @params obj  configs  按钮配置
 *         str           configs.text   按钮文字
 *         str           configs.index  按钮索引
 *         fun           configs.click  按钮单击事件
 */
Wnd.prototype.buttonAdd = function( configs ){
    /* 初始化 */
    if( typeof(configs) != 'object' || !configs ) return false;
    if( typeof(configs.index) != 'string' || !configs.index ) return false;

    /* 已经存在的按钮 */
    if( this.oControlBtns[configs.index] ) return false;

    /* 创建按钮 */
    this.createControlButton( {'index':configs.index, 'text':configs.text, 'click':configs.click} );
}

/**
 * 增加控制区默认按钮
 *
 * @params str  indexs  默认按钮标索引，默认'ok&cannel'
 */
Wnd.prototype.buttonAddDefault = function( indexs ){
    /* 初始化 */
    indexs = typeof(indexs) == 'string' ? indexs.split('&') : ['ok','cannel'];

    /* 创建按钮 */
    for( var i=0,j=indexs.length; i < j; i++ ){
        if( indexs[i] == 'ok' ) this.buttonAdd( {'index':'ok', 'text':'确定', 'click':this.ok} );
        if( indexs[i] == 'cannel' ) this.buttonAdd( {'index':'cannel', 'text':'取消', 'click':this.cannel} );
    }
}

/**
 * 删除控制区按钮
 *
 * @params str  index  按钮标索引
 */
Wnd.prototype.buttonDel = function( index ){
    /* 删除按钮DOM */
    if( this.oControlBtns[index] ){
        this.oControlBtns[index].parentNode.removeChild(this.oControlBtns[index]);
    }

    /* 删除按钮 */
    delete this.oControlBtns[index];
}

/**
 * 排序控制区按钮
 *
 * @params arr  sort  控制区按钮排序索引
 */
Wnd.prototype.buttonSort = function( sort ){
    /* 初始化 */
    sort = typeof(sort) == 'object' && sort ? sort : [];

    /* 循环排序索引 */
    for( var i=sort.length-1,last=''; i >= 0; i-- ){
        /* 无效索引 */
        if( !this.oControlBtns[sort[i]] ) continue;

        /* 排序按钮 */
        this.oControl.insertBefore( this.oControlBtns[sort[i]], (last?this.oControlBtns[last]:this.oControl.childNodes[0]) );

        /* 保存索引 */
        last = sort[i];
    }
}

/**
 * 激活控制区按钮
 *
 * @params str  index     控制区按钮索引
 * @params fun  keypress  键盘按下时处理函数，该函数将被特殊处理：
 *                            1. 自动提交兼容的事件对象
 *                            2. 该函数当作Wnd对象的成员函数来运行(意味着直接可以使用this来引用当前窗口对象)
 */
Wnd.prototype.buttonActive = function( index, keypress ){
    /* 引用this指针 */
    var self = this;

    /* 无效索引 */
    if( !this.oControlBtns[index] ) return;

    /* 键盘按下时处理函数 */
    if( typeof(keypress) == 'function' ){
        this.oControlBtns[index].onkeypress = function(e){ return keypress.apply(self,[(e||window.event)]) };
    }

    /* 按钮聚焦 */
    this.oControlBtns[index].focus();
}


/* ------------------------------------------------------ */
// - 窗口载入
/* ------------------------------------------------------ */

/**
 * 客户区内容载入
 *
 * @params str  data     载入数据
 * @params str  type     载入类型(url, html)，默认html
 *         str           url      代表通过URL载入，服务器端放回HTML
 *         str           url json 代表通过URL载入，服务器端放回JSON({'content':...})
 * @params obj  attribs  载入属性
 *         bol  attribs.move     加载完后窗口自动居中，默认true
 *         bol  attribs.loading  客户区内容填充加载层，默认true
 *         bol  attribs.complete 加载完后执行回调函数，默认true
 */
Wnd.prototype.inner = function( data, type, attribs ){
    /* 初始化 */
    attribs = typeof(attribs) == 'object' && attribs ? attribs : {};

    /* 客户区内容填充加载层 */
    if( attribs.loading !== false ){
        /* 初始化加载层宽度和高度 */
        var w = this.iWidth - 2;
        var h = this.iHeight == 'auto' ? 60 : this.iHeight;

        /* 客户区内容填充加载层 */
        this.oClient.innerHTML = '';
        this.createClientLoading(w, h, 'fill');
    }

    /* 载入 */
    switch( type ){
        /* URL载入 */
        case 'url': this.innerURL(data, 'TEXT', attribs); break;
        case 'url json': this.innerURL(data, 'JSON', attribs); break;

        /* HTML载入 */
        default: this.innerHTML(data, attribs);
    }

    /* 窗口居中 */
    if( attribs.move !== false ) this.moved();

    /* 返回 */
    return true;
}

/**
 * 客户区内容载入 - URL类型
 */
Wnd.prototype.innerURL = function( url, rtype, attribs ){
    /* 必要组件检测 */
    if( typeof(Ajax) != 'object' ){
        wnd_alert('Please Load Ajax Object !'); return false;
    }

    /* 指针引用 */
    var self = this;

    /* 回调函数 */
    function callback( result, text ){
        /* 写入内容到客户区 */
        self.oClient.innerHTML = rtype == 'TEXT' ? result : result.content;

        /* 窗口居中 */
        if( attribs.move !== false ) self.moved();

        /* 执行加载完成后的回调函数 */
        if( attribs.complete !== false ) self.fComplete(result, text);
    }

    /* 异步加载(异步等待) */
    Ajax.call(url, '', callback, 'GET', rtype, true, true);
}

/**
 * 客户区内容载入 - HTML类型
 */
Wnd.prototype.innerHTML = function( html, attribs ){
    /* 写入HTML */
    this.oClient.innerHTML = html;

    /* 执行加载完成后的回调函数 */
    if( attribs.complete !== false ) this.fComplete(html, html);
}

/**
 * 客户区内容重载
 *
 * @params str  data     载入数据
 * @params str  type     载入类型(url, html)，默认html
 *         str           url      代表通过URL载入，服务器端放回HTML
 *         str           url json 代表通过URL载入，服务器端放回JSON({'content':...})
 * @params obj  attribs  载入属性
 *         bol  attribs.move     加载完后窗口自动居中，默认false
 *         bol  attribs.loading  客户区内容浮显加载层，默认true
 *         bol  attribs.complete 加载完后执行回调函数，默认false
 */
Wnd.prototype.reinner = function( data, type, attribs ){
    /* 初始化 */
    attribs = typeof(attribs) == 'object' && attribs ? attribs : {};

    /* 创建客户区加载层 */
    if( attribs.loading === false ){
        this.createClientLoading(this.oClient.offsetWidth-2, this.oClient.offsetHeight, 'float');
    }

    /* 重新载入 */
    this.inner(data, type, {'loading':false,'move':(attribs.move===true),'complete':(attribs.complete===true)});
}


/* ------------------------------------------------------ */
// - 窗口自适应
/* ------------------------------------------------------ */

Wnd.prototype.browserAdjust = function()
{
    /* 引用this指针 */
    var self = this;

    /* 自适应浏览器窗口调整 */
    if( window.ActiveXObject ){
        window.attachEvent('onresize', function(){self.browserResize()});
        window.attachEvent('onscroll', function(){self.browserScroll()});
    }else{
        window.addEventListener('resize', function(){self.browserResize()}, false);
        window.addEventListener('scroll', function(){self.browserScroll()}, false);
    }
}

/**
 * 自适应浏览器窗口大小调整
 */
Wnd.prototype.browserResize = function(){
    /* 窗口未显示时不允许自适应 */
    if( this.sState != 'show' ) return false;

    /* 调整遮掩层宽度 */
    if( this.oOverlay ) this.oOverlay.style.width = document.documentElement.clientWidth +'px';
}

/**
 * 自适应浏览器窗口滚动
 */
Wnd.prototype.browserScroll = function(){
    /* 窗口未显示时不允许自适应 */
    if( this.sState != 'show' ) return false;

    /* 设置窗口位置 */
    this.oWnd.style.top  = this.iTop + document.documentElement.scrollTop + 'px';
    this.oWnd.style.left = this.iLeft + document.documentElement.scrollLeft + 'px';
}


/* ------------------------------------------------------ */
// - 窗口动作
/* ------------------------------------------------------ */

/**
 * 显示窗口
 */
Wnd.prototype.show = function(){
    /* 显示窗口和遮掩层 */
    if( this.oWnd ) this.oWnd.style.display = '';
    if( this.oOverlay ) this.oOverlay.style.display = this.iOverlay === false ? 'none' : '';

    /* 设置窗口状态 */
    this.sState = 'show';
}

/**
 * 隐藏窗口
 */
Wnd.prototype.hidden = function(){
    /* 隐藏窗口和遮掩层 */
    if( this.oWnd ) this.oWnd.style.display = 'none';
    if( this.oOverlay ) this.oOverlay.style.display = 'none';

    /* 设置窗口状态 */
    this.sState = 'hidden';

    /* 调用自定义函数 */
    this.fHidden();
}

/**
 * 最大化
 */
Wnd.prototype.max = function(){
    /* 窗口未显示时不允许最大化 */
    if( this.sState != 'show' ) return false;

    /* 卸载窗口拖动 */
    this.undrag();

    /* 最大化 - 设置位置 */
    this.moved(0, 0);

    /* 最大化 - 设置宽度和高度 */
    this.iWidth  = this.width(document.documentElement.clientWidth);
    this.iHeight = this.height(document.documentElement.clientHeight-this.oTitle.offsetHeight-this.oControl.offsetHeight);
}

/**
 * 最大化恢复
 */
Wnd.prototype.remax = function(){
    /* 窗口未显示时不允许最大化恢复 */
    if( this.sState != 'show' ) return false;

    /* 安装窗口拖动 */
    this.drag();

    /* 恢复位置 */
    this.moved(this.iTop, this.iLeft);

    /* 恢复宽度和高度 */
    this.width(this.iWidth);
    this.height(this.iHeight);
}

/**
 * 控制区 - 确定按钮事件
 */
Wnd.prototype.ok = function(){
    /* 调用自定义函数 - 窗口消失前 */
    if( this.fOkBefore() === false ){ return false; }

    /* 窗口隐藏 */
    this.hidden();

    /* 调用自定义函数 - 窗口消失后 */
    this.fOk();
}

/**
 * 控制区 - 取消按钮事件
 */
Wnd.prototype.cannel = function(){
    /* 调用自定义函数 - 窗口消失前 */
    if( this.fCannelBefore() === false ){ return false; }

    /* 窗口隐藏 */
    this.hidden();

    /* 调用自定义函数 - 窗口消失后 */
    this.fCannel();
}

/**
 * 窗口定位
 *
 * @params int  top   Top位置  - 非数字类型时垂直居中
 * @params int  left  Left位置 - 非数字类型时水平居中
 *
 * @return obj  返回旧的Top和Left值
 */
Wnd.prototype.moved = function( top, left ){
    /* 垂直或水平居中时获取窗口的宽度或高度数据 */
    if( typeof(top) != 'number' || typeof(left) != 'number' ){
        /* 获取窗口的实际宽度和高度 */
        var w = this.oWnd.offsetWidth;
        var h = this.oWnd.offsetHeight;

        /* 获取窗口的实际宽度和高度 - 如果当前窗口隐藏 */
        if( this.oWnd.style.display == 'none' ){
            this.oWnd.style.visibility = 'hidden';
            this.oWnd.style.display = '';

            w = this.oWnd.offsetWidth;
            h = this.oWnd.offsetHeight;

            this.oWnd.style.display = 'none';
            this.oWnd.style.visibility = 'visible';
        }
    }

    /* 拷贝，更新配置 */
    var src    = {'top':this.iTop, 'left':this.iLeft};
    this.iTop  = typeof(top)  == 'number' ? top  : (document.documentElement.clientHeight-h)/2;
    this.iLeft = typeof(left) == 'number' ? left : (document.documentElement.clientWidth-w)/2;

    /* 设置窗口位置 */
    this.oWnd.style.top  = (this.iTop  + document.documentElement.scrollTop)  + 'px';
    this.oWnd.style.left = (this.iLeft + document.documentElement.scrollLeft) + 'px';

    /* 返回 */
    return src;
}


/* ------------------------------------------------------ */
// - 窗口创建
/* ------------------------------------------------------ */

/**
 * 创建窗口
 */
Wnd.prototype.create = function(){
    /* 创建遮掩层 */
    if( this.iOverlay !== false ) this.createOverlay();

    /* 创建窗口 */
    this.createWnd();      // 创建窗口层
    this.createTitle();    // 创建标题区
    this.createClient();   // 创建客户区
    this.createControl();  // 创建控制区

    /* 自适应浏览器窗口调整 */
    this.browserAdjust();

    /* 窗口拖动 */
    this.drag();

    /* 注册窗口到Wnds集合 */
    Wnds.wnds[this.sId] = this;
}

/**
 * 创建窗口层
 */
Wnd.prototype.createWnd = function(){
    /* 创建窗口总层 */
    this.oWnd = document.createElement('DIV');

    /* 窗口总层基本属性 */
    this.oWnd.id = this.sId;
    this.oWnd.className = 'wnd-div';

    /* 窗口总层基本样式 - 窗口位置 */
    this.moved(0, 0);

    /* 窗口总层基本样式 - 窗口隐藏 */
    this.oWnd.style.display = 'none';

    /* 写入DOM */
    document.body.appendChild(this.oWnd);
}

/**
 * 创建遮掩层
 */
Wnd.prototype.createOverlay = function(){
    /* 创建遮掩层 */
    this.oOverlay = document.createElement( (window.ActiveXObject ? 'IFRAME' : 'DIV') );

    /* 遮掩层基本属性 */
    this.oOverlay.frameBorder   = '0';
    this.oOverlay.className     = 'wnd-overlay';

    this.oOverlay.style.width   = document.documentElement.clientWidth +'px';
    this.oOverlay.style.height  = document.documentElement.scrollHeight +'px';
    this.oOverlay.style.filter  = 'alpha(opacity='+ this.iOverlay +')';
    this.oOverlay.style.opacity = this.iOverlay/100;
    this.oOverlay.style.display = 'none';

    /* 写入DOM */
    document.body.appendChild(this.oOverlay);
}

/**
 * 创建标题区
 */
Wnd.prototype.createTitle = function(){
    /* 创建标题区层 */
    this.oTitle = document.createElement('DIV');
    this.oTitle.className = 'wnd-title';

    /* 标题区 - 左边界 */
    this.oTitleDivs['sidelft'] = document.createElement('DIV');
    this.oTitleDivs['sidelft'].className = 'sidelft';

    this.oTitle.appendChild(this.oTitleDivs['sidelft']);

    /* 标题区 - 标题内容层  */
    this.oTitleDivs['title'] = document.createElement('DIV');
    this.oTitleDivs['title'].className = 'title';

    this.oTitle.appendChild(this.oTitleDivs['title']);

    /* 标题区 - 标题内容层 - 图标  */
    this.oTitleDivs['title'].appendChild( document.createElement('I') );

    /* 标题区 - 右边界 */
    this.oTitleDivs['siderht'] = document.createElement('DIV');
    this.oTitleDivs['siderht'].className = 'siderht';

    this.oTitle.appendChild(this.oTitleDivs['siderht']);

    /* 标题区 - 标题操作层 - 关闭按钮 */
    if( this.iTitleAct % 10 ) this.createTitleButton( {'type':'close','click':this.cannel} );

    /* 写入DOM */
    this.oWnd.appendChild(this.oTitle);
}

/**
 * 创建标题区按钮
 */
Wnd.prototype.createTitleButton = function( configs ){
    /* 初始化 */
    var self = this;

    /* 创建A对象 */
    var o = document.createElement('A');

    /* 设置A属性 */
    o.className   = configs.type;

    o.href        = 'javascript:void(0)';
    o.onclick     = function(e){ configs.click.apply(self); }
    o.onmousedown = function(e){ try{window.event.cancelBubble=true;}catch(ex){e.stopPropagation();} }

    /* 写入DOM */
    this.oTitle.appendChild(o);
}

/**
 * 创建客户区
 */
Wnd.prototype.createClient = function(){
    /* 创建客户区层 */
    this.oClient = document.createElement('DIV');

    /* 客户区层基本属性 */
    this.oClient.className = 'wnd-client';

    /* 客户区层基本属性 - 宽度和高度 */
    this.width(this.iWidth);
    this.height(this.iHeight);

    /* 客户区层基本属性 - 溢出时情况 */
    this.oClient.style.overflowX = parseInt(this.iOverflow%100/10) ? 'hidden' : (parseInt(this.iOverflow/1000) ? 'scroll' : '');
    this.oClient.style.overflowY = this.iOverflow%10 ? 'hidden' : (parseInt(this.iOverflow%1000/100) ? 'scroll' : '');

    /* 写入DOM */
    this.oWnd.appendChild(this.oClient);
}

/**
 * 创建客户区加载层
 *
 * @params int width   宽度
 * @params int height  高度
 * @params str type    加载层类型，'float'表示浮显加载层(默认)，'fill'表示填充加载层
 */
Wnd.prototype.createClientLoading = function( width, height, type )
{
    /* 初始化参数 */
    if( !(width > 0 && height > 0) ) return false;

    /* 创建层 */
    var b = document.createElement('DIV');
    var o = document.createElement('DIV');
    var i = document.createElement('DIV');

    /* 构建加载层节点 */
    b.appendChild(o);
    b.appendChild(i);

    /* 写入DOM */
    this.oClient.childNodes[0] ? this.oClient.insertBefore(b, this.oClient.childNodes[0]) : this.oClient.appendChild(b);

    /* 设置属性 */
    b.className    = 'wnd-client-loading' + (type=='fill' ? ' wnd-client-loading-relative' : '');
    b.style.width  = width + 'px';
    b.style.height = height + 'px';

    o.className    = 'overlay';
    o.style.width  = b.style.width;
    o.style.height = b.style.height;

    i.className    = 'loading';
    i.style.top    = (height-10)/2 + 'px';
}

/**
 * 创建控制区
 */
Wnd.prototype.createControl = function(){
    /* 创建控制区层 */
    this.oControl = document.createElement('DIV');
    this.oControl.className = 'wnd-control';

    /* 写入DOM */
    this.oWnd.appendChild(this.oControl);
}

/**
 * 创建控制区按钮
 */
Wnd.prototype.createControlButton = function( configs ){
    /* 初始化 */
    var self = this;

    /* 创建INPUT对象 */
    var o = document.createElement('INPUT');

    /* 设置INPUT属性 */
    o.type    = 'button';
    o.value   = configs.text;
    o.onclick = function(){ configs.click.apply(self) };

    /* 写入DOM */
    this.oControl.appendChild(o);
    this.oControlBtns[configs.index] = o;
}


/* ------------------------------------------------------ */
// - 窗口拖动
/* ------------------------------------------------------ */

/**
 * 安装窗口拖动
 */
Wnd.prototype.drag = function(){
    /* 引用this指针 */
    var self = this;

    /* 绑定标题层拖拽 */
    this.oTitle.onmousedown = function(e){
        /* 初始化事件 */
        if( !e ) e = window.event;

        /* 获取相对于触发对象的鼠标坐标值 */
        if( e.layerX ){
            var x = e.layerX, y = e.layerY;
        }else{
            var x = e.offsetX, y = e.offsetY;
        }

        /* 事件锁定 */
        if( self.oTitle.setCapture ){
            self.oTitle.setCapture();
        }else if( window.captureEvents ){
            window.captureEvents( Event.MOUSEMOVE | Event.MOUSEUP );
        }

        /* 增加鼠标移动事件 */
        document.onmousemove = function(e){
            /* 初始化事件 */
            if( !e ) e = window.event;

            /* 移动窗口 */
            self.moved( (e.clientY-y), (e.clientX-x) );
        }

        /* 执行旧的鼠标松键事件 */
        if( document.onmouseup ) try{ document.onmouseup(); }catch(ex){}

        /* 增加鼠标松键事件 */
        document.onmouseup = function(){
            /* 事件解锁 */
            if( self.oTitle.releaseCapture ){
                self.oTitle.releaseCapture();
            }else if( window.captureEvents ){
                window.captureEvents(Event.MOUSEMOVE | Event.MOUSEUP);
            }

            /* 事件解除 */
            document.onmouseup   = null;
            document.onmousemove = null;
        }
    }
}

/**
 * 卸载拖拽
 */
Wnd.prototype.undrag = function(){
    document.onmouseup      = null;
    document.onmousemove    = null;
    this.oTitle.onmousedown = null;
}