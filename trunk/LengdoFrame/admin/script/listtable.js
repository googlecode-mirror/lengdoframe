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
     * 当前激活的列表ID
     */
    sId : '',


    /**
     * 所有列表的配置数据
     */
    oCfgs : {},


    /* ------------------------------------------------------ */
    // - 初始化ListTable对象
    /* ------------------------------------------------------ */

    /**
     * 初始化列表绑定或重绑定
     *
     * @params str  id       列表层对象ID
     * @params str  url      列表基础的URL。例如：'x/xx.php'
     * @params str  ulist    列表请求的URL。例如：'?act=list'， 默认：url   + '?act=list'
     * @params str  uquery   列表查询的URL。例如：'?act=query'，默认：ulist + '&actsub=query'
     */
    init : function( id, url, ulist, uquery ){
        /* 激活当前列表ID */
        this.sId = id;

        /* 初始化配置 - 基础部分 */
        this.initCfg(id, url, ulist, uquery);

        /* 初始化配置 - 同步机制部分 */
        this.initCfgSync(id);
    },

    /**
     * 初始化配置 - 基础部分
     */
    initCfg : function( id, url, ulist, uquery ){
        /* 配置已经初始化过 */
        if( this.oCfgs[id] ) return this.oCfgs[id];

        /* 初始化参数 */
        ulist  = typeof(ulist)  != 'string' || !ulist  ? (url+'?act=list') : (ulist.substr(0,1)=='?' ? (url+ulist) : ulist);
        uquery = typeof(uquery) != 'string' || !uquery ? (ulist+'&actsub=query') : (uquery.substr(0,1)=='?' ? (url+uquery) : uquery);

        /* 初始化配置 */
        this.oCfgs[id] = {};

        /* 初始化配置 - 列表ID */
        this.oCfgs[id].sId      = id;

        /* 初始化配置 - 基础URL，列表层URL，列表数据层URL */
        this.oCfgs[id].sUrl     = url;
        this.oCfgs[id].sUList   = ulist;
        this.oCfgs[id].sUQuery  = uquery;

        /* 初始化配置 - 多选时数量限制，false表示不限制. 针对 ListTable.mchoice() */
        this.oCfgs[id].iMCLimit = 0;
    },

    /**
     * 初始化配置 - 同步机制部分
     */
    initCfgSync : function( id ){
        /* 初始化 */
        var div = document.getElementById(id);
        var divdata = document.getElementById(id+'-divdata');

        /* 同步机制 - 列表层对象 */
        if( div != this.oCfgs[id].oDiv ){
            /* 初始化配置 - 列表层对象 */
            this.oCfgs[id].oDiv = div;

            /* 初始化配置 - 过滤条件，存储格式：$field => $val */
            this.oCfgs[id].oFilter = {};

            /* 初始化配置 - 列表选中项，存储格式：$id => {'id':$id,'caller':$caller,'data':$data} */
            this.oCfgs[id].oChoiced = {};
        }

        /* 同步机制 - 列表数据层对象 */
        if( !divdata ){
            /* 初始化配置 - 列表数据层对象 */
            for( var i=0,j=div.childNodes.length; i < j; i++ ){
                if( div.childNodes[i].className && 
                    div.childNodes[i].className.indexOf('listtable-data') != -1 
                ){
                    this.oCfgs[id].oDivData = div.childNodes[i]; 
                    this.oCfgs[id].oDivData.id = id + '-divdata';

                    break;
                }
            }
        }
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
            this.oCfgs[this.sId].oFilter = filter ? filter : {};
        }
        else{
            this.oCfgs[this.sId].oFilter[filter] = value;
        }
    },

    /**
     * 异步跳页
     *
     * @params int  page  页号
     */
    pageTo : function( page ){
        /* 设置过滤变量 */
        this.oCfgs[this.sId].oFilter['page'] = typeof(page) == 'number' ? page : 1;

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
        this.oCfgs[this.sId].oFilter['order_fd']   = field;
        this.oCfgs[this.sId].oFilter['order_type'] = this.oCfgs[this.sId].oFilter['order_type'] == 'DESC' ? 'ASC' : 'DESC';

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
        this.oCfgs[this.sId].oFilter = typeof(filter) == 'object' ? filter : {};

        /* 加载列表 */
        this.loadList();
    },

    /**
     * 载入列表
     *
     * @params obj  configs  载入列表时配置
     *         bol           configs.asyn   异步请求方式。true 表示异步等待(默认)，false表示同步等待
     *         bol           configs.quiet  是否安静模式请求。默认flase
     */
    loadList : function( configs ){
        /* 初始化参数 */
        configs = typeof(configs) == 'object' && configs ? configs : {};

        configs.asyn  = configs.asyn === false ? false : true;
        configs.quiet = configs.quiet === true ? true  : false;

        /* 异步调用 */
        Ajax.call(this.oCfgs[this.sId].sUQuery, this.buildFilter(), callback, 'POST', 'JSON', configs.asyn, configs.quiet);

        /**
         * 回调函数
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

            /* 填充新的列表数据层HTML */
            ListTable.oCfgs[ListTable.sId].oDivData.innerHTML = result.content;
        }
    },

    /**
     * 构建过滤参数
     */
    buildFilter : function(){
        /* 初始化 */
        var params = '';
        var filter = this.oCfgs[this.sId].oFilter;

        for( var i in filter ){
            if( typeof(filter[i]) == 'string' || typeof(filter[i]) == 'number' ){
                params += "&"+ i +"="+ encodeURIComponent(filter[i]);
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
     * @params obj  configs  重置列表时配置
     *         bol           configs.asyn     异步请求方式。true 表示异步等待(默认)，false表示同步等待
     *         bol           configs.loading  显示列表加载层。默认true
     */
    resetList : function( configs ){
        /* 初始化参数 */
        configs = typeof(configs) == 'object' && configs ? configs : {};

        configs.asyn = configs.asyn === false ? false : true;
        configs.loading = configs.loading === false ? false : true;

        /* 构建列表加载层 */
        if( configs.loading ) this.buildListLoading();

        /* 异步调用 */
        Ajax.call(this.oCfgs[this.sId].sUList, '', callback, 'POST', 'JSON', configs.asyn, true);

        /**
         * 回调函数
         */
        function callback( result, text ){
            /* 错误 - 服务器段返回错误 */
            if( result.error != 0 ){
                /* 移除列表加载层 */
                configs.loading ? ListTable.removeListLoading() : '';

                /* 提示并返回 */
                wnd_alert('Server Error!'); return false;
            }

            /* 初始化选中数据和搜索条件 */
            ListTable.initChoice();
            ListTable.filter({});

            /* 移除列表层的首尾标签代码 */
            result.content = result.content.substring( result.content.indexOf('>')+1, result.content.lastIndexOf('</div>') );

            /* 填充新的列表层HTML */
            ListTable.oCfgs[ListTable.sId].oDiv.innerHTML = result.content;
        }
    },

    /* ------------------------------------------------------ */
    // - 列表加载层
    /* ------------------------------------------------------ */

    /**
     * 构建列表加载层
     */
    buildListLoading : function(){
        /* 列表层 */
        var div = this.oCfgs[this.sId].oDiv;

        /* 创建层 */
        var b = document.createElement('DIV');
        var o = document.createElement('DIV');
        var i = document.createElement('DIV');

        /* 构建加载层节点 */
        b.appendChild(o);
        b.appendChild(i);

        /* 写入节点到列表层 */
        div.insertBefore(b, div.childNodes[0]);

        /* 设置属性 */
        b.id           = 'listtable-loading-' + this.oCfgs[this.sId].sId;
        b.className    = 'listtable-loading';
        b.style.width  = div.offsetWidth + 'px';
        b.style.height = div.offsetHeight + 'px';

        o.className    = 'overlay';
        o.style.width  = b.style.width;
        o.style.height = b.style.height;

        i.className    = 'loading';
        i.style.top    = (div.offsetHeight-10)/2 + 'px';
    },

    /**
     * 移除列表加载层
     */
    removeListLoading : function(){
        /* 获取对象 */
        var o = document.getElementById('listtable-loading-'+this.oCfgs[this.sId].sId);

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
     * @params obj  caller    调用者对象
     * @params mix  id        数据：记录ID
     * @params str  confirm   删除时提示消息
     * @params obj  configs   配置集
     *         fun            configs.ok    处理成功时回调的函数(不与默认的重载列表事件同时执行)
     *         str            configs.url   要提交的URL，默认使用 列表的基础URL + '?act=del'
     *         fun            configs.fail  处理失败时回调的函数
     */
    del : function( caller, id, confirm, configs ){
        /* 初始化 */
        configs = typeof(configs) == 'object' && configs ? configs : {};

        /* 回调函数 */
        function confirm_callback(){
            /* 初始化URL */
            configs.url = typeof(configs.url) == 'string' && configs.url ? configs.url : '?act=del';
            if( configs.url.substr(0,1) == '?' ) configs.url = ListTable.oCfgs[ListTable.sId].sUrl + configs.url;

            /* 异步传输(同步等待) */
            var result = Ajax.call(configs.url, 'id='+id, null, 'POST', 'JSON', false);

            /* 显示消息 */
            if( result.message ) wnd_alert(result.message);

            /* 删除成功 */
            if( result.error == 0 ){
                /* 函数回调 */
                if( typeof(configs.ok) == 'function' ){
                    configs.ok(caller);
                }
                /* 重载列表(默认) */
                else{
                    ListTable.loadList();
                }
            }
            /* 删除失败 */
            else{
                /* 函数回调 */
                if( typeof(configs.fail) == 'function' ) configs.fail(caller);
            }
        }

        /* 删除提示 */
        confirm ? wnd_confirm(confirm, {'ok':confirm_callback}) : confirm_callback();
    },

    /**
     * 创建一个编辑区
     * 默认提交 act,id,field,val 参数
     *
     * @params obj  caller   调用者对象
     * @params int  id       数据：记录的ID
     * @params str  field    要更新的字段名
     * @params obj  configs  配置集
     *         fun           configs.ok    处理成功时回调的函数(不与默认的重载列表事件同时执行)
     *         str           configs.url   要提交的URL，默认使用 列表的基础URL + '?act=ufield'
     *         fun           configs.fail  处理失败时回调的函数
     */
    edit : function( caller, id, field, configs ){
        /* 防止重复点击创建输入框 */
        if( caller.firstChild && caller.firstChild.tagName && caller.firstChild.tagName.toLowerCase() == 'input' ) return false;

        /* 初始化 */
        configs = typeof(configs) == 'object' && configs ? configs : {};

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
                configs.url = typeof(configs.url) == 'string' && configs.url ? configs.url : '?act=ufield';
                if( configs.url.substr(0,1) == '?' ) configs.url = ListTable.oCfgs[ListTable.sId].sUrl + configs.url;

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
                            if( typeof(configs.ok) == 'function' ) configs.ok(caller);
                        }
                        /* 处理出错，恢复到未编辑状态 */
                        else{
                            /* 恢复输入框原数据 */
                            input.value = s_text;

                            /* 输入框聚焦选中 */
                            input.focus(); input.select();

                            /* 函数回调 */
                            if( typeof(configs.fail) == 'function' ) configs.fail(caller);
                        }
                    }

                    /* 显示消息 */
                    result.message ? wnd_alert(result.message,{'overlay':0,'ok':alert_callback}) : alert_callback();
                }

                /* 异步传输(异步等待) */
                Ajax.call(configs.url, params, ajax_callback, 'POST', 'JSON');
            }
        }
    },

    /**
     * 异步切换状态
     * 默认提交 act,id,field,val 参数
     *
     * @params obj  caller   调用者对象
     * @params int  id       数据：记录ID
     * @params str  field    要切换状态的字段名称
     * @params obj  configs  配置集
     *         fun           configs.ok    处理成功时回调的函数(不与默认的重载列表事件同时执行)
     *         str           configs.url   要提交的URL，默认使用 列表的基础URL + '?act=ufield'
     *         fun           configs.fail  处理失败时回调的函数
     */
    toggle : function( caller, id, field, configs ){
        /* 正在处理中 */
        if( caller.className == 'do' ) return false;

        /* 初始化 */
        configs = typeof(configs) == 'object' && configs ? configs : {};

        /* 切换后的值 */
        var val = caller.className == 'yes' ? 0 : 1;

        /* 处理中的样式类 */
        caller.className = 'do';

        /* 初始化URL */
        configs.url = typeof(configs.url) == 'string' && configs.url ? configs.url : '?act=ufield';
        if( configs.url.substr(0,1) == '?' ) configs.url = ListTable.oCfgs[ListTable.sId].sUrl + configs.url;

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
                    if( typeof(configs.ok) == 'function' ) configs.ok(caller);
                }
                /* 处理出错，恢复原状态 */
                else{
                    /* 恢复对象的样式类 */
                    caller.className = val ? 'no' : 'yes';

                    /* 函数回调 */
                    if( typeof(configs.fail) == 'function' ) configs.fail(caller);
                }
            }

            /* 显示消息 */
            result.message ? wnd_alert(result.message,{'overlay':0,'ok':alert_callback}) : alert_callback();
        }

        /* 异步传输(异步等待) */
        Ajax.call(configs.url, params, ajax_callback, 'POST', 'JSON', true, true);
    },

    /**
     * 批量处理
     * 数据为 ListTable.oCfgs[ListTable.sId].oChoiced 中的ID值
     * 默认提交 act,ids[] 参数以及附加参数
     *
     * @params obj  caller   调用者对象
     * @params str  url      要提交的URL，默认使用 列表的基础URL + url(如果url的格式为'?xx=xx&...')
     * @params obj  configs  编辑记录时配置
     *         obj           configs.params   附加参数{$param:$value}
     *         str           configs.confirm  消息提示，如果没填则表示不提示消息。
     *                                        消息中的%d将会被转换为批处理记录个数
     */
    batch : function( caller, url, configs ){
        /* 初始化 */
        configs = typeof(configs) == 'object' && configs ? configs : {};

        /* 补全URL */
        if( url.substr(0,1) == '?' ) url = ListTable.oCfgs[ListTable.sId].sUrl + url;

        /* 初始化 */
        var count = 0, param = '';

        /* 构建记录IDS参数 */
        for( var id in this.oCfgs[this.sId].oChoiced ){
            param += '&ids[]='+id; count++;
        }

        /* 无记录提示 */
        if( count == 0 ){ wnd_alert('请选择记录！'); return false; }

        /* 回调函数 */
        function confirm_callback(){
            /* 增加附加参数 */
            if( typeof(configs.params) == 'object' && configs.params ){
                for( var i in params ){
                    param += '&'+ i +'='+ encodeURIComponent(params[i]);
                }
            }

            /* 异步传输(同步等待) */
            var result = Ajax.call(url, param, null, 'POST', 'JSON', false);

            /* 显示消息 */
            if( result.message ) wnd_alert(result.message);

            /* 处理成功 */
            if( result.error == 0 ){
                /* 函数回调 */
                if( typeof(configs.ok) == 'function' ){
                    configs.ok(caller);
                }
                /* 重载列表(默认) */
                else{
                    ListTable.loadList();
                }
            }
            /* 删除失败 */
            else{
                /* 函数回调 */
                if( typeof(configs.fail) == 'function' ) configs.fail(caller);
            }
        }

        /* 确认提交提示 */
        configs.confirm ? wnd_confirm(configs.confirm.replace('%d', count), {'ok':confirm_callback}) : confirm_callback();
    },


    /* ------------------------------------------------------ */
    // - 列表项选择函数集
    /* ------------------------------------------------------ */

    /**
     * 获取选中的值
     *
     * @params str  type  返回值类型
     *                    'IDS'      表示返回 ID集：[$id] - 默认
     *                    'ASSOC'    表示返回 ID关联数据对象：{$id:{'id':$id,'caller':$caller,'data':$data}}
     *                    'UNASSOC'  表示返回 无关联数据对象：[{'id':$id,'caller':$caller,'data':$data}]
     *
     * @return mix  返回数据对象或数组
     */
    getChoiced : function( type ){
        /* 初始化返回值类型 */
        var arr = type == 'ASSOC' ? {} : [];

        /* 初始化当前列表选中项数据 */
        var choiced = this.oCfgs[this.sId].oChoiced;

        /* 构建返回值 */
        for( var id in choiced ){
            if( type == 'ASSOC' ){
                arr.id = choiced[id];
            }
            else if( type == 'UNASSOC' ){
                arr.push(choiced[id]);
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
     * @params bol  reload  是否重载列表，默认 false
     */
    initChoice : function( reload ){
        /* 初始化选中数据 */
        this.oCfgs[this.sId].oChoiced = {};

        /* 重载列表 */
        reload === true ? this.loadList() : '';
    },

    /**
     * 设置多选时数量限制
     */
    setMCLimit : function( limit ){
        if( typeof(limit) == 'number' && limit >= 0 ){
            this.oCfgs[this.sId].iMCLimit = limit;
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
        for( var i in this.oCfgs[this.sId].oChoiced ){
            /* 撤销选中时的回调函数 */
            if( callbacks && typeof(callbacks.unchoice) == 'function' ){
                callbacks.unchoice(this.oCfgs[this.sId].oChoiced[i].caller);
            }

            /* 撤销选中 */
            delete this.oCfgs[this.sId].oChoiced[i];

            /* 再次点击触发的撤销选中 */
            if( i == id ) return 0;
        }

        /* 保存选中记录ID */
        this.oCfgs[this.sId].oChoiced[id] = {'id':id, 'caller':caller, 'data':data};

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
        var limit = this.oCfgs[this.sId].iMCLimit;
        callbacks = typeof(callbacks) == 'object' && callbacks ? callbacks : listtable_schoice_callbacks_default;

        /* 处理已选中的数据 */
        for( var i in this.oCfgs[this.sId].oChoiced ){
            /* 再次点击触发的撤销选中 */
            if( i == id ){
                /* 撤销选中时的回调函数 */
                if( callbacks && typeof(callbacks.unchoice) == 'function' ){
                    callbacks.unchoice(this.oCfgs[this.sId].oChoiced[i].caller);
                }

                /* 撤销选中 */
                delete this.oCfgs[this.sId].oChoiced[i]; return 0;
            }

            /* 多选限制 */
            if( limit != 0 && --limit <= 0 ){
                wnd_alert('最多只能选择 '+ this.oCfgs[this.sId].iMCLimit +' 项！'); return false;
            }
        }

        /* 保存选中记录ID */
        this.oCfgs[this.sId].oChoiced[id] = {'id':id, 'caller':caller, 'data':data};

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


/* ------------------------------------------------------ */
// - 列表搜索
/* ------------------------------------------------------ */

/**
 * 列表搜索
 *
 * @params obj  form    表单对象
 * @params obj  filter  附加的搜索条件
 * @params str  id      列表的ID，默认使用当前激活列表
 *
 * @return bol  false
 */
function listtable_search( form, filter, id )
{
    /* 初始化搜索条件 */
	filter = typeof(filter) == 'object' && !filter ? filter : {};

    /* 设置搜索条件 */
    for( var i=0,len=form.length; i < len; i++ ){
        /* 无效的表单域名称 */
        if( !form[i].name ) continue;

        /* 过滤特殊情况 */
        if( form[i].type == 'radio' || form[i].type == 'checkbox' ){
            if( !form[i].checked ) continue;
        }

        /* 赋值参数 */
        filter[form[i].name] = form[i].value;
    }

    /* 初始化列表对象 */
	if( typeof(id) == 'string' ) ListTable.init(id);

    /* 列表搜索 */
    ListTable.search(filter);
}