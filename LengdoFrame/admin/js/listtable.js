// +----------------------------------------------------------------------
// | LengdoFrame - 列表对象
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


var ListTable = {
    /**
     * 列表容器层ID
     */
    sId : '',

    /**
     * 列表基础URL
     */
    sUrl : '',

    /**
     * 列表请求和查询URL
     */
    sUList  : '',
    sUQuery : '',

    /**
     * 过滤条件
     * 存储格式：$field => $val
     */
    oFilter : {},

    /**
     * 列表选中项
     * 存储格式：$id => {'id':$id,'caller':$caller,'data':$data}
     */
    oChoiced : {},

    /**
     * 多选时数量限制
     * 0表示不限制. 针对 ListTable.mchoice()
     */
    iMCLimit : 0,

    /**
     * 列表集的配置数据
     * 格式：$var => $data
     */
    _cfg : {},


    /* ------------------------------------------------------ */
    // - 初始化ListTable对象
    /* ------------------------------------------------------ */

    /**
     * 初始化列表绑定或重绑定
     * 如果列表已绑定中，那么设置新的配置(如果提供配置数据)
     * 如果列表未绑定，那么备份上个绑定的列表配置数据，然后调出并设置即将要绑定列表的旧数据，再设置新的配置(如果提供配置数据)
     *
     * @params str  id       要绑定的列表ID
     * @params str  url      列表基础的URL。例如：'module.php'
     * @params str  ulist    列表请求的URL。例如：'?act=list'
     * @params str  uquery   列表查询的URL。例如：'?act=query'，默认：ulist+'&actsub=query'
     */
    init : function( id, url, ulist, uquery ){
        /* 初始化 */
        var listtable = document.getElementById(id);

        /* 无效的列表ID */
        if( !listtable ){ alert('ListTable Id Error!'); return false; }

        /* 补充全URL */
        ulist  = typeof(ulist)  == 'string' && ulist.substr(0,1)  == '?' ? (url+ulist) : ulist;
        uquery = typeof(uquery) == 'string' ? (uquery.substr(0,1)=='?' ? url+uquery : uquery) : (ulist+'&actsub=query');

        /* 列表同步机制 - 无同步标识 */
        if( listtable.className.indexOf(' LISTTABLE-SYNC-FLAG') == -1 ){
            /* 如果列表已绑定中，重置数据 */
            if( this.sId == id ){
                this.oFilter  = {};
                this.oChoiced = {};
            }

            /* 如果列表已绑定过，重置数据 */
            if( this._cfg[id] ){
                this._cfg[id].oFilter  = {};
                this._cfg[id].oChoiced = {};
            }

            /* 设置同步标别 */
            listtable.className += ' LISTTABLE-SYNC-FLAG';
        }

        /* 列表已绑定 */
        if( this.sId == id ){
            /* 设置列表的新配置数据 */
            if( typeof(url)    == 'string' && url ) this.sUrl = url;
            if( typeof(ulist)  == 'string' && ulist ) this.sUList = ulist;
            if( typeof(uquery) == 'string' && uquery ) this.sUQuery = uquery;

            return true;
        }

        /* 列表重绑定 - 保存当前绑定中的列表配置数据 */
        if( this.sId ){
            this._cfg[this.sId] = {
                                    'sUrl'     : this.sUrl,
                                    'sUList'   : this.sUList,
                                    'sUQuery'  : this.sUQuery,
                                    'oFilter'  : this.oFilter,
                                    'oChoiced' : this.oChoiced,
                                    'iMCLimit' : this.iMCLimit
                                  };
        }

        /* 列表重绑定 - 设置列表的原配置数据 */
        this.sUrl     = this._cfg[id] ? this._cfg[id].sUrl     : '';
        this.sUList   = this._cfg[id] ? this._cfg[id].sUList   : '';
        this.sUQuery  = this._cfg[id] ? this._cfg[id].sUQuery  : '';
        this.oFilter  = this._cfg[id] ? this._cfg[id].oFilter  : {};
        this.oChoiced = this._cfg[id] ? this._cfg[id].oChoiced : {};
        this.iMCLimit = this._cfg[id] ? this._cfg[id].iMCLimit : 0;

        /* 列表重绑定 - 设置列表的新配置数据 */
        if( typeof(id)     == 'string' && id ) this.sId = id;
        if( typeof(url)    == 'string' && url ) this.sUrl = url;
        if( typeof(ulist)  == 'string' && ulist ) this.sUList = ulist;
        if( typeof(uquery) == 'string' && uquery ) this.sUQuery = uquery;
    },


    /* ------------------------------------------------------ */
    // - 列表搜索函数集
    /* ------------------------------------------------------ */

    /**
     * 设置过滤条件
     *
     * @params mix  filter  过滤条件对象或者字段名
     * @params mix  value   字段值
     */
    filter : function( filter, value ){
        if( typeof(filter) == 'object' ){
            this.oFilter = filter;
        }
        else if( typeof(value) != 'undefined' ){
            this.oFilter[filter] = value;
        }
    },

    /**
     * 异步跳页
     *
     * @params int  page  页号
     */
    pageTo : function( page ){
        /* 设置过滤变量 */
        this.oFilter['page'] = typeof(page) == 'number' ? page : 1;

        /* 加载列表 */
        this.loadList();
    },

    /**
     * 异步单字段排序
     *
     * @params str  field  排序字段名
     */
    orderBy : function( field ){
        /* 设置过滤变量 */
        this.oFilter['order_fd']   = field;
        this.oFilter['order_type'] = this.oFilter['order_type'] == 'DESC' ? 'ASC' : 'DESC';

        /* 加载列表 */
        this.loadList();
    },

    /**
     * 异步搜索(初始化过滤条件)
     *
     * @params obj  filter  过滤条件对象
     */
    search : function( filter ){
        /* 设置过滤变量 */
        this.oFilter = typeof(filter) == 'object' ? filter : {};

        /* 加载列表 */
        this.loadList();
    },

    /**
     * 载入列表
     *
     * @params bol  asyn  异步请求方式。true 表示异步等待(默认)，false表示同步等待
     * @params bol  quiet 是否安静模式请求。默认flase
     */
    loadList : function( asyn, quiet ){
        /* 初始化参数 */
        asyn = asyn === false ? false : true;

        /* 异步调用 */
        Ajax.call(this.sUQuery, this.buildFilter(), callback, 'POST', 'JSON', asyn, quiet);

        /**
         * 回调函数
         * 负责将列表插入到由 ListTable.sId 指定的容器
         */
        function callback( result, text ){
            /* 错误 - 服务器端返回错误 */
            if( result.error != 0 ){
                /* 错误提示 */
                wnd_alert( result.message ? result.message : 'Server Error!' );

                /* 返回 */
                return false;
            }

            /* 初始化选中数据和搜索条件 */
            ListTable.initChoice();
            ListTable.filter(result.filter);

            /* 填充新的列表HTML */
            document.getElementById(ListTable.sId).innerHTML = result.content;
        }
    },

    /**
     * 构建过滤参数
     */
    buildFilter : function(){
        /* 初始化 */
        var params = '';

        for( var i in this.oFilter ){
            if( typeof(this.oFilter[i]) !== 'function' && this.oFilter[i] !== null ){
                params += "&"+ i +"="+ encodeURIComponent(this.oFilter[i]);
            }
        }

        return params;
    },


    /* ------------------------------------------------------ */
    // - 重置列表
    /* ------------------------------------------------------ */

    /**
     * 重置列表
     *
     * @params bol  asyn     异步请求方式。true 表示异步等待(默认)，false表示同步等待
     * @params bol  loading  使用列表BOX加载层。默认true
     */
    resetList : function( asyn, loading ){
        /* 初始化参数 */
        asyn = asyn === false ? false : true;
        loading = loading === false ? false : true;

        /* 构建列表BOX加载层 */
        loading ? this.buildListLoading() : '';

        /* 异步调用 */
        Ajax.call(this.sUList, '', callback, 'POST', 'JSON', asyn, true);

        /**
         * 回调函数
         * 负责将列表插入到由 ListTable.sId 指定的容器
         */
        function callback( result, text ){
            /* 错误 - 服务器段返回错误 */
            if( result.error != 0 ){
                /* 移除列表BOX加载层 */
                loading ? ListTable.removeListLoading() : '';

                /* 提示并返回 */
                wnd_alert('Server Error!'); return false;
            }

            /* 初始化选中数据和搜索条件 */
            ListTable.initChoice();
            ListTable.filter({});

            /* 填充新的列表BOX层HTML */
            document.getElementById(ListTable.sId).parentNode.innerHTML = result.content;
        }
    },

    /* ------------------------------------------------------ */
    // - 列表BOX加载层
    /* ------------------------------------------------------ */

    /**
     * 构建列表BOX加载层
     */
    buildListLoading : function(){
        /* 列表BOX层 */
        var box = document.getElementById(this.sId).parentNode;

        /* 创建层 */
        var b = document.createElement('DIV');
        var o = document.createElement('DIV');
        var i = document.createElement('DIV');

        /* 构建加载层节点 */
        b.appendChild(o);
        b.appendChild(i);

        /* 写入节点到BOX层 */
        box.insertBefore(b, box.childNodes[0]);

        /* 设置属性 */
        b.id           = 'list-box-loading-' + this.sId;
        b.className    = 'list-box-loading';
        b.style.width  = box.offsetWidth + 'px';
        b.style.height = box.offsetHeight + 'px';

        o.className    = 'overlay';
        o.style.width  = b.style.width;
        o.style.height = b.style.height;

        i.className    = 'loading';
        i.style.top    = (box.offsetHeight-10)/2 + 'px';
    },

    /**
     * 移除列表BOX加载层
     */
    removeListLoading : function(){
        /* 获取对象 */
        var o = document.getElementById('list-box-loading-'+this.sId);

        /* 移除对象 */
        if( o ) o.parentNode.removeChild(o);
    },


    /* ------------------------------------------------------ */
    // - 数据操作函数集
    /* ------------------------------------------------------ */

    /**
     * 删除记录行
     * 默认提交 act,id 参数
     *
     * @params obj  caller  调用者对象
     * @params mix  id      数据：记录ID
     * @params str  msg     删除提示消息
     * @params str  url     要提交的URL，默认使用 ListTable.sUrl + '?act=del'
     * @params obj  callbacks      回调函数
     *         fun  callbacks.ok   处理成功时回调的函数(不与默认的重载列表事件同时执行)
     *         fun  callbacks.fail 处理失败时回调的函数
     */
    del : function( caller, id, msg, url, callbacks ){
        /* 删除提示 */
        msg ? wnd_confirm(msg, {'ok':callback}) : callback();

        /* 回调函数 */
        function callback(){
            /* 初始化URL */
            url = typeof(url) == 'string' && url ? url : '?act=del';
            if( url.substr(0,1) == '?' ) url = ListTable.sUrl + url;

            /* 异步传输(同步等待) */
            var result = Ajax.call(url, 'id='+id, null, 'POST', 'JSON', false);

            /* 显示消息 */
            if( result.message ){
                wnd_alert(result.message);
            }

            /* 删除成功 */
            if( result.error == 0 ){
                /* 函数回调 */
                if( callbacks && typeof(callbacks.ok) == 'function' ){
                    callbacks.ok(caller);
                }
                /* 重载列表(默认) */
                else{
                    ListTable.loadList();
                }
            }
            /* 删除失败 */
            else{
                /* 函数回调 */
                if( callbacks && typeof(callbacks.fail) == 'function' ) callbacks.fail(caller);
            }
        }
    },

    /**
     * 创建一个编辑区
     * 默认提交 act,id,field,val 参数
     *
     * @params obj  caller  调用者对象
     * @params int  id      数据：记录的ID
     * @params str  field   要更新的字段名
     * @params str  url     要提交的URL，默认使用 ListTable.sUrl + '?act=ufield'
     * @params obj  callbacks      回调函数
     *         fun  callbacks.ok   处理成功时回调的函数
     *         fun  callbacks.fail 处理失败时回调的函数
     */
    edit : function( caller, id, field, url, callbacks ){
        /* 防止重复点击创建输入框 */
        if( caller.firstChild && caller.firstChild.tagName && caller.firstChild.tagName.toLowerCase() == 'input' ) return false;

        /* 保存原来的内容 - 过滤首尾空白 */
        var s_html = f(caller.innerHTML, 'trim');
        var s_text = f((window.ActiveXObject ? caller.innerText : caller.textContent), 'trim');

        /* 创建一个输入框 */
        var input = document.createElement('INPUT');

        /* 单元格对象 */
        var td = caller.parentNode;
        while( td && td.tagName && td.tagName.toLowerCase() != 'td' ){ td = td.parentNode; }

        /* 单元格宽度 */
        var len = td.offsetWidth;

        /* 输入框赋值 */
        input.value = s_text;
        input.style.width = (caller.offsetWidth+11 > len ? len-11 : caller.offsetWidth+11) + 'px';

        /* 隐藏对象中的内容，并将输入框加入到对象中 */
        caller.innerHTML = '';
        caller.appendChild(input);

        /* 输入框聚焦选中 */
        input.focus(); input.select();

        /* 编辑区输入事件处理函数 */
        input.onkeypress = function(e){
            /* 事件对象 */
            var evt = e || window.event;

            /* Enter, Esc */
            if( evt.keyCode == 13 ){ this.blur(); return false; }
            if( evt.keyCode == 27 ){ caller.innerHTML = s_html; }
        }

        /* 编辑区失去焦点的处理函数 */
        input.onblur = function(e){
            /* 去除边界空白符 */
            this.value = f(this.value, 'trim');

            /* 字段值未发生变化 */
            if( this.value == s_text ){
                caller.innerHTML = s_html;
            }

            /* 字段值发生变化 */
            else{
                /* 初始化URL */
                url = typeof(url) == 'string' && url ? url : '?act=ufield';
                if( url.substr(0,1) == '?' ) url = ListTable.sUrl + url;

                /* 构建参数 */
                var params = 'val='+ encodeURIComponent(this.value) +'&id='+ id +'&field='+ field;

                /* 回调函数 */
                function ajax_callback( result, text ){
                    function alert_callback(){
                        /* 处理成功，事件源对象赋值 */
                        if( result.error == 0 ){
                            /* 显示结果 */
                            caller.innerHTML = result.content === '' ? f(input.value,'html') : result.content;

                            /* 函数回调 */
                            if( callbacks && typeof(callbacks.ok) == 'function' ) callbacks.ok(caller);
                        }
                        /* 处理出错，恢复到未编辑状态 */
                        else{
                            /* 恢复输入框原数据 */
                            input.value = s_text;

                            /* 输入框聚焦选中 */
                            input.focus(); input.select();

                            /* 函数回调 */
                            if( callbacks && typeof(callbacks.fail) == 'function' ) callbacks.fail(caller);
                        }
                    }

                    /* 显示消息 */
                    result.message ? wnd_alert(result.message,{'overlay':0,'ok':alert_callback}) : alert_callback();
                }

                /* 异步传输(异步等待) */
                Ajax.call(url, params, ajax_callback, 'POST', 'JSON');
            }
        }
    },

    /**
     * 异步切换状态
     * 默认提交 act,id,field,val 参数
     *
     * @params obj  caller  调用者对象
     * @params int  id      数据：记录ID
     * @params str  field   要切换状态的字段名称
     * @params str  url     要提交的URL，默认使用 ListTable.sUrl + '?act=ufield'
     * @params obj  callbacks      回调函数
     *         fun  callbacks.ok   处理成功时回调的函数
     *         fun  callbacks.fail 处理失败时回调的函数
     */
    toggle : function( caller, id, field, url, callbacks ){
        /* 正在处理中 */
        if( caller.className == 'do' ) return false;

        /* 切换后的值 */
        var val = caller.className == 'yes' ? 0 : 1;

        /* 处理中的样式类 */
        caller.className = 'do';

        /* 初始化URL */
        url = typeof(url) == 'string' && url ? url : '?act=ufield';
        if( url.substr(0,1) == '?' ) url = ListTable.sUrl + url;

        /* 构建参数 */
        var params = 'val='+ val +'&id='+ id +'&field='+ field;

        /* 回调函数 */
        function ajax_callback( result, text ){
            function alert_callback(){
                /* 处理成功，替换图片值 */
                if( result.error == 0 ){
                    /* 更改对象的样式类 */
                    caller.className = result.content === '' ? (val?'yes':'no') : (result.content==1?'yes':'no');

                    /* 函数回调 */
                    if( callbacks && typeof(callbacks.ok) == 'function' ) callbacks.ok(caller);
                }
                /* 处理出错，恢复原状态 */
                else{
                    /* 恢复对象的样式类 */
                    caller.className = val ? 'no' : 'yes';

                    /* 函数回调 */
                    if( callbacks && typeof(callbacks.fail) == 'function' ) callbacks.fail(caller);
                }
            }

            /* 显示消息 */
            result.message ? wnd_alert(result.message,{'overlay':0,'ok':alert_callback}) : alert_callback();
        }

        /* 异步传输(异步等待) */
        Ajax.call(url, params, ajax_callback, 'POST', 'JSON', true, true);
    },

    /**
     * [测试阶段]
     * 创建一个下拉框
     * 默认提交 act,id,field,val 三个参数以及对应的数据
     *
     * @params obj  caller  调用者对象
     * @params int  id      数据：记录的ID
     * @params str  field   要更新的字段名
     * @params str  opts    JSON格式，下拉框的项[{val:xx,txt:xx}]
     * @params str  url     要提交的URL，默认使用 ListTable.sUrl + '?act=ufield'
     */
    ddl : function( caller, id, field, opts, url ){
        /* 防止重复点击创建 */
        if( caller.firstChild && caller.firstChild.tagName && caller.firstChild.tagName.toLowerCase() == 'select' ) return false;

        /* 保存原来的内容 */
        var s_html = f(caller.innerHTML, 'trim');
        var s_text = f((window.ActiveXObject ? caller.innerText : caller.textContent), 'trim');

        /* 创建一个下拉框 */
        var sel = document.createElement("SELECT");

        for( var i=0,len=opts.length; i < len; i++ ){
            /* 无效数据 */
            if( typeof(opts[i]) != 'object' ) continue;

            opt = document.createElement("OPTION");
            opt.text  = opts[i].txt;
            opt.value = opts[i].val;

            sel.options.add(opt);

            if( f(s_text,'trim') == f(opts[i].txt,'trim') ){
                sel.selectedIndex = i;
            }
        }

        /* 隐藏对象中的内容，并将下拉框加入到对象中 */
        caller.innerHTML = '';
        caller.appendChild(sel);

        sel.focus();

        /* 编辑区失去焦点的处理函数 */
        sel.onblur = function(e){
            /* 去除边界空白符 */
            text = f(this.options[this.selectedIndex].text, 'trim');

            /* 字段值未发生变化 */
            if( text == s_text ){
                caller.innerHTML = s_html;
            }
            /* 字段值发生变化 */
            else{
                /* 初始化URL */
                url = typeof(url) == 'string' && url ? url : '?act=ufield';
                if( url.substr(0,1) == '?' ) url = ListTable.sUrl + url;

                /* 构建参数 */
                var params = 'val='+ encodeURIComponent(this.value) +'&id='+ id +'&field='+ field;

                /* 异步传输(同步等待) */
                var result = Ajax.call(url, params, null, "POST", "JSON", false);

                /* 显示消息 */
                if( result.message ){
                    wnd_alert(result.message);
                }

                /* 事件源对象赋值 */
                if( result.error == 0 ){
                    caller.innerHTML = text;
                }
                /* 出错，恢复到原编辑状态 */
                else{
                    caller.innerHTML = s_html;
                }
            }
        }
    },

    /**
     * 批量处理
     * 数据为 ListTable.oChoiced 中的ID值
     * 默认提交 act,ids[] 参数以及附加参数
     *
     * @params obj  caller  调用者对象
     * @params str  url     要提交的URL，默认使用 ListTable.sUrl + url(如果url的格式为'?xx=xx&...')
     * @params obj  params  附加参数{$param:$value}
     * @params str  msg     消息提示，如果没填则表示不提示消息。
     *                      消息中的%d将会被转换为批处理记录个数
     * @params obj  callbacks      回调函数
     *         fun  callbacks.ok   处理成功时回调的函数
     *         fun  callbacks.fail 处理失败时回调的函数
     */
    batch : function( caller, url, params, msg, callbacks ){
        /* 补充全URL */
        if( url.substr(0,1) == '?' ) url = ListTable.sUrl + url;

        /* 初始化附加属性 */
        if( typeof(params) != 'object' || !params ) params={};

        /* 初始化 */
        var count = 0, param = '';

        /* 构建记录IDS参数 */
        if( typeof(params) == 'object' && params ){
            for( var id in this.oChoiced ){
                param += '&ids[]='+id; count++;
            }
        }

        /* 无记录提示 */
        if( count == 0 ){
            wnd_alert('请选择记录！'); return false;
        }

        /* 确认提交提示 */
        typeof(msg) == 'string' && msg ? wnd_confirm(msg.replace('%d', count), {'ok':callback}) : callback();

        function callback(){
            /* 增加附加参数 */
            for( var i in params ){
                param += '&'+ i +'='+ encodeURIComponent(params[i]);
            }

            /* 异步传输(同步等待) */
            var result = Ajax.call(url, param, null, 'POST', 'JSON', false);

            /* 显示消息 */
            if( result.message ){
                wnd_alert(result.message);
            }

            /* 处理成功 */
            if( result.error == 0 ){
                /* 函数回调 */
                if( callbacks && typeof(callbacks.ok) == 'function' ){
                    callbacks.ok(caller);
                }
                /* 重载列表(默认) */
                else{
                    ListTable.loadList();
                }
            }
            /* 删除失败 */
            else{
                /* 函数回调 */
                if( callbacks && typeof(callbacks.fail) == 'function' ) callbacks.fail(caller);
            }
        }
    },


    /* ------------------------------------------------------ */
    // - 列表项选择函数集
    /* ------------------------------------------------------ */

    /**
     * 获取选中的值
     *
     * @params str  type  返回值类型
     *                    'VALUE'    表示返回 ID：[$id] - 默认
     *                    'ASSOC'    表示返回 ID关联数据对象：{$id:{'id':$id,'caller':$caller,'data':$data}}
     *                    'UNASSOC'  表示返回 无关联数据对象：[{'id':$id,'caller':$caller,'data':$data}]
     *
     * @return mix  返回数据对象或数组
     */
    getChoiced : function( type ){
        /* 初始化返回值类型 */
        var arr = type == 'ASSOC' ? {} : [];

        /* 构建返回值 */
        for( var id in this.oChoiced ){
            if( type == 'ASSOC' ){
                arr.id = this.oChoiced[id];
            }
            else if( type == 'UNASSOC' ){
                arr.push(this.oChoiced[id]);
            }
            else{
                arr.push(id);
            }
        }

        /* 返回 */
        return arr;
    },

    /**
     * 初始化选中记录
     *
     * @params bool  load  是否重载列表，默认 false
     */
    initChoice : function( reload ){
        /* 初始化选中数据 */
        this.oChoiced = {};

        /* 重载列表 */
        reload === true ? this.loadList() : '';
    },

    /**
     * 设置多选时数量限制
     */
    setMCLimit : function( limit ){
        if( typeof(limit) == 'number' && limit >= 0 ){
            this.iMCLimit = limit;
        }
    },

    /**
     * 选择所有行(全选)
     *
     * @params bol  type     1/true表示全选, 0/false表示不选, -1表示反选.
     * @params str  touch    触发 全选/不选/反选 的对象或者ID
     * @params str  touchs   触发子项的对象或者NAME
     * @params obj  callbacks           回调函数
     *              callbacks.choice    选中后回调的函数
     *              callbacks.unchoice  撤选后回调的函数
     */
    achoice : function( type, touch, touchs, callbacks ){
        /* 初始化 */
        touch     = typeof(touch) == 'string'  ? document.getElementById(touch)     : touch;
        touchs    = typeof(touchs) == 'string' ? document.getElementsByName(touchs) : touchs;
        callbacks = typeof(callbacks) == 'object' && callbacks ? callbacks : listtable_achoice_callbacks_default;

        /* 初始化 */
        var i, len = touchs.length;

        /* 无记录情况 */
        if( len == 0 ) return false;

        /* 操作类型：不选 */
        if( type === 0 || type === false ){
            /* 初始化选中记录 */
            this.initChoice();

            /* 撤销选中 */
            for( i=0; i < len; i++){
                callbacks.unchoice(touchs[i], 'caller');
            }

            /* 撤销触发项 */
            callbacks.unchoice(touch, 'touch');
        }

        /* 操作类型：全选 */
        else if( type === 1 || type === true ){
            /* 初始化选中记录 */
            this.initChoice();

            for( i=0; i < len; i++){
                /* 撤销/选中 */
                if( this.mchoice(touchs[i], touchs[i].value, null, callbacks) === false ) return false;
            }

            /* 撤销触发项 */
            callbacks.choice(touch, 'touch');
        }

        /* 操作类型：反选 */
        else if( type === -1 ){
            /* 初始化 */
            var flag, checked = 0;

            for( i=0; i < len; i++ ){
                /* 撤销/选中 */
                flag = this.mchoice(touchs[i], touchs[i].value, null, callbacks);

                /* 撤销/选中时失败 */
                if( flag === false ) return false;

                /* 统计选中项 */
                checked += flag;
            }

            /* 选中/撤销触发项 */
            checked == len ? callbacks.choice(touch, 'touch') : callbacks.unchoice(touch, 'touch');
        }
    },

    /**
     * 选择项并高亮选中项(单选)
     * 同一时刻只有一项被选中，再次点击撤销选中
     *
     * @params obj  caller  调用者对象
     * @params mix  id      数据：记录ID
     * @params mix  data    数据：记录数据
     * @params obj  callbacks           回调函数
     *              callbacks.choice    选中后回调的函数
     *              callbacks.unchoice  撤选后回调的函数
     *
     * @return mix  1表示选中成功，0表示撤销成功
     */
    schoice : function( caller, id, data, callbacks ){
        /* 初始化 */
        callbacks = typeof(callbacks) == 'object' && callbacks ? callbacks : listtable_schoice_callbacks_default;

        /* 处理已选中的数据 */
        for( var i in this.oChoiced ){
            /* 撤销选中时的回调函数 */
            if( callbacks && typeof(callbacks.unchoice) == 'function' ){
                callbacks.unchoice(this.oChoiced[i].caller);
            }

            /* 撤销选中 */
            delete this.oChoiced[i];

            /* 再次点击触发的撤销选中 */
            if( i == id ) return 0;
        }

        /* 保存选中记录ID */
        this.oChoiced[id] = {'id':id, 'caller':caller, 'data':data};

        /* 选中时的回调函数 */
        if( callbacks && typeof(callbacks.choice) == 'function' ){
            callbacks.choice(caller);
        }

        /* 返回 */
        return 1;
    },

    /**
     * 选择项并高亮选中项(多选)
     * 同一时刻可以多项被选中，再次点击撤销选中
     *
     * @params obj  caller  调用者对象
     * @params int  id      数据：记录ID
     * @params mix  data    数据：记录数据
     * @params obj  callbacks           回调函数
     *              callbacks.choice    选中后回调的函数
     *              callbacks.unchoice  撤选后回调的函数
     *
     * @return mix  1表示选中成功，0表示撤销成功，false表示失败
     */
    mchoice : function( caller, id, data, callbacks ){
        /* 初始化 */
        var limit = this.iMCLimit;
        callbacks = typeof(callbacks) == 'object' && callbacks ? callbacks : listtable_schoice_callbacks_default;

        /* 处理已选中的数据 */
        for( var i in this.oChoiced ){
            /* 再次点击触发的撤销选中 */
            if( i == id ){
                /* 撤销选中时的回调函数 */
                if( callbacks && typeof(callbacks.unchoice) == 'function' ){
                    callbacks.unchoice(this.oChoiced[i].caller);
                }

                /* 撤销选中 */
                delete this.oChoiced[i]; return 0;
            }

            /* 多选限制 */
            if( limit != 0 && --limit <= 0 ){
                wnd_alert('最多只能选择 '+ this.iMCLimit +' 项！'); return false;
            }
        }

        /* 保存选中记录ID */
        this.oChoiced[id] = {'id':id, 'caller':caller, 'data':data};

        /* 选中时的回调函数 */
        if( callbacks && typeof(callbacks.choice) == 'function' ){
            callbacks.choice(caller);
        }

        return 1;
    }
}


/* ------------------------------------------------------ */
// - 列表项选择回调函数集
/* ------------------------------------------------------ */

/* 单选回调函数 */
var listtable_schoice_callbacks_default = {
    choice   : function ( caller ){ TableAct.hiLight(caller, 'tr', 'protect'); },
    unchoice : function ( caller ){ TableAct.hiLight(caller, 'tr', '#FFFFFF'); }
}

/* 全选回调函数 */
var listtable_achoice_callbacks_default = {
    choice : function ( touch, type ){
        /* 初始化 */
        type = typeof(type) == 'string' && type ? type : 'caller';

        if( touch.type && touch.type.toLowerCase() == 'checkbox' ){
            /* 选中复选框 */
            touch.checked = true;

            /* 高亮表格行 */
            if( type == 'caller' ) TableAct.hiLight(touch, 'tr', 'protect');
        }
    },

    unchoice : function ( touch, type ){
        /* 初始化 */
        type = typeof(type) == 'string' && type ? type : 'caller';

        if( touch.type && touch.type.toLowerCase() == 'checkbox' ){
            /* 撤销复选框 */
            touch.checked = false;

            /* 撤销高亮表格行 */
            if( type == 'caller' ) TableAct.hiLight(touch, 'tr', '#FFFFFF');
        }
    }
}