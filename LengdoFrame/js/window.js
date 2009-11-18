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
     * 查找已创建的窗口
     * 
     * @params mix  id  窗口ID
     * 
     * @return obj  窗口对象，如果失败返回null
     */
    find : function( id ){
        /* 根据窗口ID返回窗口对象 */
        if( typeof(id) == 'string' ){
            return this[id] ? this[id] : null;
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
                return typeof(this[obj.id]) == 'object' ? this[obj.id] : null;
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
 *          int  configs.width       窗口宽度。  默认：'200px' 注：客户区宽度=窗口宽度-2
 *          int  configs.height      客户区高度。默认：'auto'
 *          int  configs.action      标题栏的操作按钮 000(最小化，最大化，关闭)，默认001. 注：前辍0省略
 *          str  configs.control     控制区类型(ok/cannel/empty/custom/default)，默认default. 注：通过&组合按钮
 *          obj  configs.buttons     控制区自定义按钮集。[{'index':str, 'text':str, 'click':fun}]
 *          int  configs.overlay     是否使用遮掩层。false表示不显示，0-100表示透明度
 *          int  configs.overflow    窗口溢出时滚动条 0000(scroll-x，scroll-y，hidden-x，hidden-y). 注：前辍0省略
 */
function Wnd( id, callbacks, configs ){
    /* 初始化参数 - 配置 */
    if( typeof(configs) != 'object' || !configs ) configs = {};

    this.sId       = id;
    this.sTitle    = typeof(configs.title)    == 'string' ? configs.title : '';
    this.sWidth    = typeof(configs.width)    == 'number' ? configs.width  + 'px' : '200px';
    this.sHeight   = typeof(configs.height)   == 'number' ? configs.height + 'px' : 'auto';
    this.iAction   = typeof(configs.action)   == 'number' ? configs.action : 1;
    this.aControl  = typeof(configs.control)  == 'string' ? configs.control.split('&') : ['default'];
    this.aButtons  = typeof(configs.buttons)  == 'object' ? configs.buttons : [];
    this.iOverlay  = typeof(configs.overlay)  == 'number' ? configs.overlay : (configs.overlay === false ? false : 40);
    this.iOverflow = typeof(configs.overflow) == 'number' ? configs.overflow : 0;


    /* 初始化参数 - 回调函数 */
    if( typeof(callbacks) != 'object' || !callbacks ) callbacks = {};

    this.fOk           = typeof(callbacks.ok)       == 'function' ? callbacks.ok       : function(){};
    this.fOkBefore     = typeof(callbacks.okb)      == 'function' ? callbacks.okb      : function(){};
    this.fCannel       = typeof(callbacks.cannel)   == 'function' ? callbacks.cannel   : function(){};
    this.fCannelBefore = typeof(callbacks.cannelb)  == 'function' ? callbacks.cannelb  : function(){};
    this.fHidden       = typeof(callbacks.hidden)   == 'function' ? callbacks.hidden   : function(){};
    this.fComplete     = typeof(callbacks.complete) == 'function' ? callbacks.complete : function(){};


    /* 初始化参数 - 内部参数 */
    this.iLeft        = 0;    //窗口Left
    this.iTop         = 0;    //窗口Top
    this.oData        = {};   //自定义数据
    this.oInner       = {};   //客户区载入配置

    this.oWnd         = null; //窗口对象
    this.oTitle       = null; //标题层
    this.oTitleDivs   = {};   //标题层Div对象集合
    this.oClient      = null; //客户区对象
    this.oControl     = null; //控制区
    this.oControlBtns = {};   //控制区Button对象集合
    this.oOverlay     = null; //遮掩层对象
}


/* ------------------------------------------------------ */
// - 窗口配置
/* ------------------------------------------------------ */

/**
 * 设置/返回z-index
 *
 * @params mix  zindex  int表示设置z-index，undefined表示返回z-index
 *
 * @params mix 
 */
Wnd.prototype.zindex = function( zindex ){
    /* 返回z-index */
    if( typeof(zindex) == 'undefined' ){
        return this.oWnd.style.zIndex;
    }

    /* 设置z-index */
    if( typeof(zindex) == 'number' && zindex >= 0 ){
        this.oWnd.style.zIndex = zindex;
        this.oOverlay ? this.oOverlay.style.zIndex = zindex : '';
    }
}

/**
 * 设置/返回overlay透明度
 *
 * @params mix  overlay  int表示设置透明度，false表示隐掉overlay，undefined表示返回透明度
 *
 * @return int
 */
Wnd.prototype.overlay = function( overlay ){
    /* 返回overlay透明度 */
    if( typeof(overlay) == 'undefined' ){
        return this.iOverlay;
    }

    /* overlay存在检查 */
    if( !this.oOverlay ) return ;

    /* 设置overlay透明度 */
    if( overlay === false ){
        this.oOverlay.style.display = 'none';
    }
    else if( typeof(overlay) == 'number' && overlay >= 0 ){
        this.oOverlay.style.filter  = 'alpha(opacity='+ overlay +')';
        this.oOverlay.style.opacity = overlay/100; 
        this.oOverlay.style.display = '';
    }else{
        return ;
    }

    /* 更新配置 */
    this.iOverlay = overlay;
}

/**
 * 设置/返回窗口宽度
 *
 * @params int  width  宽度值，undefined表示返回宽度
 *
 * @return str
 */
Wnd.prototype.width = function( width ){
    /* 返回宽度 */
    if( typeof(width) == 'undefined' ){
        return this.sWidth;
    }

    /* 设置宽度 */
    if( typeof(width) == 'number' && width >= 2 ){
        /* 更新配置 */
        this.sWidth = width + 'px';

        /* 设置宽度 */
        this.oWnd.style.width    = this.sWidth;
        this.oClient.style.width = width-2 + 'px';
    }
}

/**
 * 设置/返回客户区高度
 *
 * @params mix  height  int或'auto'表示设置高度，undefined表示返回高度
 *
 * @return str
 */
Wnd.prototype.height = function( height ){
    /* 返回高度 */
    if( typeof(height) == 'undefined' ){
        return this.sHeight;
    }

    /* 设置高度 - 参数有效 */
    if( height == 'auto' || (typeof(height) == 'number' && parseInt(height) >= 0) ){
        /* 设置高度 */
        this.sHeight = height == 'auto' ? 'auto' : parseInt(height)+'px';

        /* 设置高度 */
        this.oClient.style.height = this.sHeight;
    }
}

/**
 * 设置/返回标题文本
 * 
 * @params str  str  要显示的文本，undefined表示返回标题文本
 * @params str  ico  图标的样式
 */
Wnd.prototype.title = function( str, ico ){
    /* 返回标题文本 */
    if( typeof(str) == 'undefined' ) return this.sTitle;

    /* 设置标题 */
    if( typeof(str) == 'string' ){
        /* 初始化图标的样式类 */
        ico = typeof(ico) == 'string' ? ('<i class="'+ ico +'"></i>') : '';

        /* 设置标题 */
        this.oTitleDivs['title'].innerHTML = ico + str + '&nbsp;';

        /* 更新配置 */
        this.sTitle = str;
    }
}

/**
 * 设置/返回控制区按钮
 *
 * @params  str  index   控制区按钮索引
 * @params  str  attrib  对象HTML内置属性，undefined表示返回控制区按钮对象
 * @params  mix  value   属性值，undefined表示返回控制区按钮对象原属性值
 */
Wnd.prototype.button = function( index, attrib, value ){
    /* 返回控制区按钮对象 */
    if( typeof(attrib) == 'undefined' ) return this.oControlBtns[index];

    /* 返回控制区按钮对象原属性值 */
    if( typeof(value) == 'undefined' ){
        return this.oControlBtns[index] ? this.oControlBtns[index][attrib] : 'undefined';
    }

    /* 设置控制区按钮 */
    if( this.oControlBtns[index] ){
        this.oControlBtns[index][attrib] = value;
    }
}

/**
 * 设置/返回窗口回调函数
 * 
 * @params str  type  回调函数类型
 * @params fun  func  回调函数
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

/**
 * 返回客户端对象
 */
Wnd.prototype.client = function(){
    return this.oClient;
}

/**
 * 设置/获取自定义数据
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
// - 窗口插入
/* ------------------------------------------------------ */

/**
 * 客户区内容载入
 *
 * @params mix  str      载入数据
 * @params str  type     载入类型(url, html)，默认html
 * @params obj  attribs  载入属性
 *         bol  attribs.move     加载完后窗口自动居中，默认true
 *         bol  attribs.loading  客户区内容填充加载层，默认true
 *         bol  attribs.complete 加载完后执行回调函数，默认true
 */
Wnd.prototype.inner = function( str, type, attribs ){
    /* 初始化 */
    attribs = typeof(attribs) == 'object' && attribs ? attribs : {}

    /* 保存配置 */
    this.oInner = {'str':str, 'type':type};

    /* 客户区内容填充加载层 */
    if( attribs.loading !== false ){
        /* 初始化加载层宽度和高度 */
        var w = parseInt(this.sWidth)-2;
        var h = this.sHeight == 'auto' ? 60 : parseInt(this.sHeight);

        /* 填充客户区加载层 */
        this.oClient.innerHTML = '';
        this.buildClientLoading(w, h, true);
    }

    /* 加载类型 */
    switch( type ){
        /**
         * 类型说明：通过URL直接取得内容。通过AJAX方式取得内容
         * 数据说明：参数 str 为链接地址
         */
        case 'url':
        case 'url xml':
        case 'url json':
            /* 初始化返回数据类型 */
            var rtype = type == 'url json' ? 'JSON' : (type=='url xml' ? 'XML' :'TEXT');

            /* 必要组件检测 */
            if( typeof(Ajax) != 'object' ){
                wnd_alert('Please Load Ajax Object'); return false;
            }

            /* 引用this指针 */
            var self = this;

            /* 异步回调函数 */
            function callback( result, text ){
                /* 写入内容到客户区 */
                self.oClient.innerHTML = rtype == 'TEXT' ? result : result.content;

                /* 窗口居中 */
                if( attribs.move !== false ) self.moved();

                /* 执行加载完成后的回调函数 */
                if( attribs.complete !== false ) self.fComplete();
            }

            /* 异步加载 */
            Ajax.call(str, '', callback, 'GET', rtype, true, true);

            break;

        /**
         * 类型说明：直接写入HTML
         * 数据说明：参数 str 为HTML代码
         */
        default:
            /* 写入HTML */
            this.oClient.innerHTML = str; 

            /* 执行加载完成后的回调函数 */
            if( attribs.complete !== false ) this.fComplete();

            break;
    }

    /* 窗口居中 */
    if( attribs.move !== false ) this.moved();

    return true;
}

/**
 * 客户区内容重载入
 */
Wnd.prototype.reinner = function(){
    /* 构建客户区加载层 */
    this.buildClientLoading(this.oClient.offsetWidth-2, this.oClient.offsetHeight);

    /* 重新载入 */
    this.inner( this.oInner.str, this.oInner.type, {'loading':false,'move':false,'complete':false} );
}

/**
 * 构建客户区加载层
 *
 * @params int width     宽度
 * @params int height    高度
 * @params bol relative  使用 position:relative 加载层。默认：false
 */
Wnd.prototype.buildClientLoading = function( width, height, relative )
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

    /* 写入节点到客户区 */
    this.oClient.childNodes[0] ? this.oClient.insertBefore(b, this.oClient.childNodes[0]) : this.oClient.appendChild(b);

    /* 设置属性 */
    b.className    = 'wnd-client-loading' + (relative===true ? ' wnd-client-loading-relative' : '');
    b.style.width  = width + 'px';
    b.style.height = height + 'px';

    o.className    = 'overlay';
    o.style.width  = b.style.width;
    o.style.height = b.style.height;

    i.className    = 'loading';
    i.style.top    = (height-10)/2 + 'px';
}

/**
 * 自适应浏览器窗口大小调整
 */
Wnd.prototype.browserResize = function(){
    /* 引用this指针 */
    var self = this;

    if( self._browserResize == null ){
        self._browserResize = function(){
            self.oOverlay.style.width = document.documentElement.clientWidth +'px';
        }
    }

    return self._browserResize;
}
Wnd.prototype._browserResize = null;


/* ------------------------------------------------------ */
// - 窗口动作
/* ------------------------------------------------------ */

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
 * 显示窗口
 */
Wnd.prototype.show = function(){
    /* 引用this指针 */
    var self = this;

    /* 显示窗口和遮掩层 */
    try{
        self.oWnd.style.display = '';
        self.oOverlay.style.display = this.iOverlay === false ? 'none' : '';
    }catch(e){}

    /* 自适应浏览器窗口大小调整 */
    if( window.ActiveXObject ){
        try{ window.attachEvent('onresize', self.browserResize()); }catch(e){}
    }else{
        try{ window.addEventListener('resize', self.browserResize(), false); }catch(e){}
    }
}

/**
 * 隐藏窗口
 */
Wnd.prototype.hidden = function(){
    /* 引用this指针 */
    var self = this;

    /* 隐藏窗口和遮掩层 */
    try{
        self.oWnd.style.display = 'none';
        self.oOverlay.style.display = 'none';
    }catch(e){}

    /* 解除自适应浏览器窗口大小调整 */
    if( window.ActiveXObject ){
        try{ window.detachEvent('onresize', self.browserResize()); }catch(e){}
    }else{
        try{ window.removeEventListener('resize', self.browserResize(), false); }catch(e){}
    }

    /* 调用自定义函数 */
    this.fHidden();
}


/**
 * 窗口定位
 *
 * @params int  l  Left位置 - 非数字类型时水平居中
 * @params int  t  Top位置  - 非数字类型时垂直居中
 */
Wnd.prototype.moved = function( l, t ){
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

    /* 保存配置 */
    this.iLeft = typeof(l) == 'number' ? l : (document.documentElement.clientWidth-w)/2;
    this.iTop  = typeof(t) == 'number' ? t : (document.documentElement.clientHeight-h)/2; 

    /* 设置窗口位置 */
    this.oWnd.style.left = (this.iLeft + document.documentElement.scrollLeft) + 'px';
    this.oWnd.style.top  = (this.iTop + document.documentElement.scrollTop) + 'px';
}

/**
 * 最大化和最大化恢复
 */
Wnd.prototype.max = function(){
    /* 窗口未显示时不允许最大化 */
    if( this.oWnd.style.display == 'none' ) return false;

    /* 卸载窗口拖动 */
    this.undrag();

    /* 最大化 */
    this.oWnd.style.top       = 0;
    this.oWnd.style.left      = 0;
    this.oWnd.style.width     = document.documentElement.clientWidth +'px';
    this.oClient.style.width  = document.documentElement.clientWidth - 2 +'px';
    this.oClient.style.height = document.documentElement.clientHeight-this.oTitle.offsetHeight-this.oControl.offsetHeight +'px';
}
Wnd.prototype.remax = function(){
    /* 窗口未显示时不允许恢复 */
    if( this.oWnd.style.display == 'none' ) return false;

    /* 重载窗口拖动 */
    this.drag();

    /* 恢复 */
    this.oWnd.style.top       = this.iTop  + 'px';
    this.oWnd.style.left      = this.iLeft + 'px';
    this.oWnd.style.width     = this.sWidth;
    this.oClient.style.width  = parseInt(this.sWidth)-2 + 'px';
    this.oClient.style.height = this.sHeight;
}

/**
 * 激活控制区按钮
 *
 * @params str  index     控制区按钮索引
 * @params fun  keypress  键盘按下时处理函数，该函数将被特殊处理：
 *                            1. 自动提交兼容的事件对象
 *                            2. 该函数当作Wnd对象的成员函数来运行(意味着直接可以使用this来引用当前窗口对象)
 */
Wnd.prototype.activeControl = function( index, keypress ){
    /* 引用this指针 */
    var self = this;

    /* 无效索引 */
    if( !this.oControlBtns[index] ) return;

    /* 键盘按下时处理函数 */
    if( typeof(keypress) == 'function' ) this.oControlBtns[index].onkeypress = function(e){return keypress.apply(self,[(e||window.event)]);};

    /* 按钮聚焦 */
    this.oControlBtns[index].focus();
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
    this.createWnd();      // 创建窗口总层
    this.createTitle();    // 创建标题区
    this.createClient();   // 创建客户区
    this.createControl();  // 创建控制区

    /* 增加拖拽 */
    this.drag();

    /* 自动注册窗口到对象集合 */
    this._reg();
}

/**
 * 创建窗口总层
 */
Wnd.prototype.createWnd = function(){
    /* 创建窗口总层 */
    this.oWnd = document.createElement('DIV');

    /* 窗口总层基本属性 */
    this.oWnd.id = this.sId;
    this.oWnd.className = 'wnd-div';

    /* 窗口总层基本样式 */
    this.oWnd.style.top = document.documentElement.scrollTop +'px';
    this.oWnd.style.left = document.documentElement.scrollLeft +'px';
    this.oWnd.style.width = this.sWidth;
    this.oWnd.style.display = 'none';

    /* 将窗口总层增加到body */
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

    /* 将遮掩层增加到body */
    document.body.appendChild(this.oOverlay);
}

/**
 * 创建标题区
 */
Wnd.prototype.createTitle = function(){
    /* 初始化 */
    var o;

    /* 创建标题区层 */
    this.oTitle = document.createElement('DIV');
    this.oTitle.className = 'wnd-title';

    /* 标题区 - 左边界 */
    this.oTitleDivs['sidelft'] = document.createElement('DIV'); 
    this.oTitleDivs['sidelft'].className = 'sidelft'; 

    this.oTitle.appendChild(this.oTitleDivs['sidelft']);

    /* 标题区 - 标题详细  */
    this.oTitleDivs['title'] = document.createElement('DIV');
    this.oTitleDivs['title'].className = 'title';

    this.oTitle.appendChild(this.oTitleDivs['title']);

    /* 标题区 - 标题详细图片  */
    var img = document.createElement('I');

    this.oTitleDivs['title'].appendChild(img);

    /* 标题区 - 右边界 */
    this.oTitleDivs['siderht'] = document.createElement('DIV'); 
    this.oTitleDivs['siderht'].className = 'siderht'; 

    this.oTitle.appendChild(this.oTitleDivs['siderht']);

    /* 标题区 - 窗口操作层 - 关闭按钮 */
    if( this.iAction % 10 ){
        o = document.createElement('A');
        o.href        = 'javascript:void(0)';
        o.className   = 'close';
        o.onclick     = this._cf(this, 'cannel');
        o.onmousedown = function(e){ try{window.event.cancelBubble=true;}catch(ex){e.stopPropagation();} }

        this.oTitle.appendChild(o);
    }

    /* 将标题区层增加到窗口总层 */
    this.oWnd.appendChild(this.oTitle);
}

/**
 * 创建客户区
 */
Wnd.prototype.createClient = function(){
    /* 创建客户区层 */
    this.oClient = document.createElement('DIV');

    /* 客户区层基本属性 */
    this.oClient.className       = 'wnd-client';
    this.oClient.style.height    = this.sHeight;
    this.oClient.style.width     = parseInt(this.sWidth)-2 +'px';
    this.oClient.style.overflowX = parseInt(this.iOverflow%100/10) ? 'hidden' : (parseInt(this.iOverflow/1000) ? 'scroll' : '');
    this.oClient.style.overflowY = this.iOverflow%10 ? 'hidden' : (parseInt(this.iOverflow%1000/100) ? 'scroll' : '');

    /* 将客户区层增加到窗口总层 */
    this.oWnd.appendChild(this.oClient);
}

/**
 * 创建控制区
 */
Wnd.prototype.createControl = function(){
    /* 初始化 */
    var o,i,ii,j,jj;

    /* 创建控制区层 */
    this.oControl = document.createElement('DIV');
    this.oControl.className = 'wnd-control';

    /* 控制区按钮 */
    for( i=0,j=this.aControl.length; i < j; i++ ){
        /* 确定按钮 */
        if( this.aControl[i] == 'default' || this.aControl[i] == 'ok' ){
            o = document.createElement('INPUT');
            o.type    = 'button';
            o.value   = '确定';
            o.onclick = this._cf(this,'ok');

            this.oControl.appendChild(o);
            this.oControlBtns['ok'] = o;
        }

        /* 取消按钮 */
        if( this.aControl[i] == 'default' || this.aControl[i] == 'cannel' ){
            o = document.createElement('INPUT');
            o.type    = 'button';
            o.value   = '取消';
            o.onclick = this._cf(this,'cannel');

            this.oControl.appendChild(o);
            this.oControlBtns['cannel'] = o;
        }

        /* 自定义按钮 */
        if( this.aControl[i] == 'custom' ){
            for( ii=0,jj=this.aButtons.length; ii < jj; ii++ ){
                o = document.createElement('INPUT');
                o.type    = 'button';
                o.value   = this.aButtons[ii].text;
                o.onclick = this._cf(this, '_controlBtnClick', ii);

                this.oControl.appendChild(o);
                this.oControlBtns[(this.aButtons[ii].index?this.aButtons[ii].index:('custom'+(ii+1)))] = o;
            }
        }
    }

    /* 将控制区层增加到窗口总层 */
    this.oWnd.appendChild(this.oControl);
}
/**
 * 创建控制区 - 自定义按钮事件
 *
 * @access private
 */
Wnd.prototype._controlBtnClick = function( i ){
    /* 调用自定义函数 */
    if( typeof(this.aButtons[i].click) == 'function' ){
        this.aButtons[i].click.apply(this);
    }
}


/* ------------------------------------------------------ */
// - 窗口拖拽
/* ------------------------------------------------------ */

/**
 * 安装拖拽
 */
Wnd.prototype.drag = function(){
    this._drag(this.oTitle, this.oWnd);
}

/**
 * 卸载拖拽
 */
Wnd.prototype.undrag = function(){
    this.oTitle.onmousedown = null;

    document.onmousemove = null;
    document.onmouseup   = null;
}

/**
 * 拖拽绑定
 *
 * @params obj  obj   事件发生的对象 
 * @params obj  drag  被移动的对象
 */
Wnd.prototype._drag = function( obj, drag ){
    obj.onmousedown = function(e){
        if( !e ) e = window.event;

        /* 取的相对于触发对象坐标值 */
        if( e.layerX ){
            var x = e.layerX;
            var y = e.layerY;
        }else{
            var x = e.offsetX;
            var y = e.offsetY;
        }

        if( obj.setCapture ){
            obj.setCapture();
        }else if( window.captureEvents ){
            window.captureEvents( Event.MOUSEMOVE | Event.MOUSEUP );
        }

        /* 增加鼠标移动事件 */
        document.onmousemove = function(e){
            if( !e ) e = window.event;

            /* 取的相对于客户区坐标值 */
            if( !e.pageX ) e.pageX = (e.clientX < 0 ? 0 : e.clientX);
            if( !e.pageY ) e.pageY = (e.clientY < 0 ? 0 : e.clientY);

            var tx = e.pageX - x;
            var ty = e.pageY - y;

            if( window.ActiveXObject ){
                ty += document.documentElement.scrollTop - document.documentElement.clientTop;
            }

            drag.style.left = tx + 'px';
            drag.style.top  = ty + 'px';
        }

        /* 执行上一个鼠标松键事件 */
        if( document.onmouseup ) try{ document.onmouseup(); }catch(ex){}

        /* 增加鼠标松键事件 */
        document.onmouseup = function(){
            if( obj.releaseCapture ){
                obj.releaseCapture();
            }else if( window.captureEvents ){
                window.captureEvents(Event.MOUSEMOVE | Event.MOUSEUP);
            }

            document.onmousemove = null;
            document.onmouseup   = null;
        }
    }
}


/* ------------------------------------------------------ */
// - 辅助函数
/* ------------------------------------------------------ */

/**
 * 窗口注册到Wnds对象
 */
Wnd.prototype._reg = function(){
    Wnds[this.sId] = this;
}


/**
 * 封装有参数的函数为无参数函数
 */
Wnd.prototype._cf = function( obj, func, arg ){
    /*获取自定义参数*/
    var args = [];

    for( var i=2,len=arguments.length; i < len; i++ ){
        args.push(arguments[i]);
    }

    return function(){ obj[func].apply(obj, args); }
}