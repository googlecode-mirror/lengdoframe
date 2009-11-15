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
     * 列表的容器层ID
     */
    sId : '',

    /**
     * 列表的基础URL
     */
    sUrl : '',

    /**
     * 异步查询的URL
     */
    sUQuery : '',

    /**
     * 多选时数量限制，0表示不限制. 针对 ListTable.mchoice()
     */
    iMCLimit : 0,

    /**
     * 过滤条件
     * 存储格式：$field => $val
     */
    oFilter : {},

    /**
     * 列表选中项
     * 存储格式：记录ID => 数据对象{'id':$id,'object':$obj,'data':$data}
     */
    oChoiced : {},

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
     * @params str  id       要重新绑定的列表ID
     * @params str  url      基础URL
     * @params str  uquery   异步查询的URL
     * @params int  mclimit  多选时数量限制，0表示不限制
     */
    init : function( id, url, uquery, mclimit ){
        /* 初始化 */
        var listtable = document.getElementById(id);

        /* 无效的列表ID */
        if( !listtable ){
            alert('ListTable Id Error!'); return;
        }

        /* 补充全URL */
        if( typeof(uquery) == 'string' && uquery.substr(0,1) == '?' ) uquery = url + uquery;

        /* 列表同步机制 - 无同步标识 */
        if( listtable.className.indexOf(' LIST-SYNC') == -1 ){
            /* 初始化空数据 - 如果列表已绑定中 */
            if( this.sId == id ){
                this.oFilter  = {};
                this.oChoiced = {};
            }

            /* 初始化空数据 - 如果列表已绑定过 */
            if( this._cfg[id] ){
                this._cfg[id].oFilter  = {};
                this._cfg[id].oChoiced = {};
            }

            /* 设置同步标别 */
            listtable.className += ' LIST-SYNC';
        }

        /* 列表已绑定 */
        if( this.sId == id ){
            /* 设置列表的新配置数据 */
            if( typeof(url)     == 'string' && url ) this.sUrl = url;
            if( typeof(uquery)  == 'string' && uquery ) this.sUQuery = uquery;
            if( typeof(mclimit) == 'number' && mclimit >= 0 ) this.iMCLimit = mclimit;

            return true;
        }

        /* 列表重绑定 - 保存上个绑定列表的配置数据 */
        if( this.sId ){
            this._cfg[this.sId] = {
                                    'sUrl'     : this.sUrl,
                                    'sUQuery'  : this.sUQuery,
                                    'iMCLimit' : this.iMCLimit,
                                    'oFilter'  : this.oFilter,
                                    'oChoiced' : this.oChoiced
                                  };
        }

        /* 列表重绑定 - 设置列表的原配置数据 */
        this.sUrl     = this._cfg[id] ? this._cfg[id].sUrl     : '';
        this.sUQuery  = this._cfg[id] ? this._cfg[id].sUQuery  : '';
        this.oFilter  = this._cfg[id] ? this._cfg[id].oFilter  : {};
        this.oChoiced = this._cfg[id] ? this._cfg[id].oChoiced : {};
        this.iMCLimit = this._cfg[id] ? this._cfg[id].iMCLimit : 0;

        /* 列表重绑定 - 设置列表的新配置数据 */
        if( typeof(id)      == 'string' && id ) this.sId = id;
        if( typeof(url)     == 'string' && url ) this.sUrl = url;
        if( typeof(uquery)  == 'string' && uquery ) this.sUQuery = uquery;
        if( typeof(mclimit) == 'number' && mclimit >= 0 ) this.iMCLimit = mclimit;
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
        else if( typeof(filter) == 'string' || typeof(filter) == 'number' ){
            this.oFilter[filter] = value;
        }
    },

    /**
     * 异步搜索(初始化过滤条件)
     *
     * @params obj  filter  过滤条件对象
     */
    search : function( filter ){
        /* 设置过滤变量 */
        this.oFilter = typeof(filter) == 'object' ? filter : {};

        this.loadList();
    },

    /**
     * 异步跳页
     *
     * @params int  page  页号
     */
    pageTo : function( page ){
        /* 设置过滤变量 */
        this.oFilter['page'] = typeof(page) == 'number' ? page : 1;

        this.loadList();
    },

    /**
     * 异步单字段排序
     *
     * @params str  field  排序字段名
     */
    orderBy : function( field ){
        this.oFilter['order_fd']   = field;
        this.oFilter['order_type'] = this.oFilter['order_type'] == 'DESC' ? 'ASC' : 'DESC';

        this.loadList();
    },

    /**
     * 载入列表
     *
     * @params bol  asyn  异步请求方式[true表示异步等待(默认)，false表示同步等待]
     * @params bol  quiet 是否安静模式请求(默认flase)
     */
    loadList : function( asyn, quiet ){
        /* 常量对象指针 */
        var self = this;

        /* 初始化参数 */
        asyn = asyn === false ? false : true;

        /* 异步调用 */
        Ajax.call(this.sUQuery, this.buildFilter(), callback, 'POST', 'JSON', asyn, quiet);

        /**
         * 回调函数，负责将列表插入到由 ListTable.sId 指定的容器
         */
        function callback( result, text ){
            /* 错误 - 服务器段返回错误 */
            if( result.error != 0 ){
                wnd_alert('Server Error!'); return false;
            }

            /* 初始化选中记录 */
            self.ichoice(false);

            /* 填充新的列表HTML */
            document.getElementById(self.sId).innerHTML = result.content;

            /* 重新赋值过滤条件 */
            if( typeof(result.filter) == 'object' ){
                self.oFilter = result.filter;
            }
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
    // - 数据操作函数集
    /* ------------------------------------------------------ */

    /**
     * 删除记录行
     * 默认提交 act,id 三个参数以及对应的数据
     *
     * @params obj  obj  事件发生对象
     * @params mix  id   数据：记录ID
     * @params str  msg  删除提示消息
     * @params str  url  要提交的URL，默认使用 ListTable.sUrl + '?act=del'
     * @params obj  callbacks  回调函数
     *         fun  callbacks.ok   处理成功时回调的函数(不与默认的重载列表事件同时执行)
     *         fun  callbacks.fail 处理失败时回调的函数
     */
    del : function( obj, id, msg, url, callbacks ){
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

			/* 处理成功，加载列表 */
			if( result.error == 0 ){
                /* 函数回调 */
                if( callbacks && typeof(callbacks.ok) == 'function' ){
                    callbacks.ok(obj);
                }
                /* 默认重载列表 */
                else{
                    ListTable.loadList();
                }
			}
            /* 处理失败 */
            else{
                /* 函数回调 */
                if( callbacks && typeof(callbacks.fail) == 'function' ) callbacks.fail(obj);
            }
		}
    },

    /**
     * 创建一个编辑区
     * 默认提交 act,id,field,val 四个参数以及对应的数据
     *
     * @params obj  obj    事件发生对象
     * @params int  id     数据：记录的ID
     * @params str  field  要更新的字段名
     * @params str  url    要提交的URL，默认使用 ListTable.sUrl + '?act=ufield'
     * @params obj  callbacks  回调函数
     *         fun  callbacks.ok   处理成功时回调的函数
     *         fun  callbacks.fail 处理失败时回调的函数
     */
    edit : function( obj, id, field, url, callbacks ){
        /* 防止重复点击创建输入框 */
        if( obj.firstChild && obj.firstChild.tagName && obj.firstChild.tagName.toLowerCase() == 'input' ) return false;

        /* 保存原来的内容 - 过滤首尾空白 */
        var s_html = f(obj.innerHTML, 'trim');
        var s_text = f((window.ActiveXObject ? obj.innerText : obj.textContent), 'trim');

        /* 创建一个输入框 */
        var input = document.createElement('INPUT');

        /* 单元格宽度 */
        var len = this._rec(obj, 'td').offsetWidth;

        /* 输入框赋值 */
        input.value = s_text;
        input.style.width = (obj.offsetWidth+11 > len ? len-11 : obj.offsetWidth+11) + 'px';

        /* 隐藏对象中的内容，并将输入框加入到对象中 */
        obj.innerHTML = '';
        obj.appendChild(input);

        /* 输入框聚焦选中 */
        input.focus(); input.select();

        /* 编辑区输入事件处理函数 */
        input.onkeypress = function(e){
            /* 事件对象 */
            var evt = e || window.event;

            /* Enter, Esc */
            if( evt.keyCode == 13 ){ this.blur(); return false; }
            if( evt.keyCode == 27 ){ obj.innerHTML = s_html; }
        }

        /* 编辑区失去焦点的处理函数 */
        input.onblur = function(e){
            /* 去除边界空白符 */
            this.value = f(this.value, 'trim');

            /* 字段值未发生变化 */
            if( this.value == s_text ){
                obj.innerHTML = s_html;
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
                            obj.innerHTML = result.content === '' ? f(input.value,'html') : result.content;

                            /* 函数回调 */
                            if( callbacks && typeof(callbacks.ok) == 'function' ) callbacks.ok(obj);
                        }
                        /* 处理出错，恢复到未编辑状态 */
                        else{
                            /* 恢复输入框原数据 */
                            input.value = s_text;

                            /* 输入框聚焦选中 */
                            input.focus(); input.select();

                            /* 函数回调 */
                            if( callbacks && typeof(callbacks.fail) == 'function' ) callbacks.fail(obj);
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
     * 默认提交 act,id,field,val 四个参数以及对应的数据
     *
     * @params obj  obj        事件发生对象
     * @params int  id         数据：记录ID
     * @params str  field      要切换状态的字段名称
     * @params str  url        要提交的URL，默认使用 ListTable.sUrl + '?act=ufield'
     * @params obj  callbacks  回调函数
     *         fun  callbacks.ok   处理成功时回调的函数
     *         fun  callbacks.fail 处理失败时回调的函数
     */
    toggle : function( obj, id, field, url, callbacks ){
        /* 正在处理中 */
        if( obj.className == 'do' ) return false;

        /* 切换后的值 */
        var val = obj.className == 'yes' ? 0 : 1;

        /* 处理中的样式类 */
        obj.className = 'do';

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
                    obj.className = result.content === '' ? (val?'yes':'no') : (result.content==1?'yes':'no');

                    /* 函数回调 */
                    if( callbacks && typeof(callbacks.ok) == 'function' ) callbacks.ok(obj);
                }
                /* 处理出错，恢复原状态 */
                else{
                    /* 恢复对象的样式类 */
                    obj.className = val ? 'no' : 'yes';

                    /* 函数回调 */
                    if( callbacks && typeof(callbacks.fail) == 'function' ) callbacks.fail(obj);
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
     * @params obj  obj    事件发生对象
     * @params int  id     数据：记录的ID
     * @params str  field  要更新的字段名
     * @params str  opts   JSON格式，下拉框的项[{val:xx,txt:xx}]
     * @params str  url    要提交的URL，默认使用 ListTable.sUrl + '?act=ufield'
     */
    ddl : function( obj, id, field, opts, url ){
        /* 防止重复点击创建 */
        if( obj.firstChild && obj.firstChild.tagName && obj.firstChild.tagName.toLowerCase() == 'select' ) return false;

        /* 保存原来的内容 */
        var s_html = f(obj.innerHTML, 'trim');
        var s_text = f((window.ActiveXObject ? obj.innerText : obj.textContent), 'trim');

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
        obj.innerHTML = '';
        obj.appendChild(sel);

        sel.focus();

        /* 编辑区失去焦点的处理函数 */
        sel.onblur = function(e){
            /* 去除边界空白符 */
            text = f(this.options[this.selectedIndex].text, 'trim');

            /* 字段值未发生变化 */
            if( text == s_text ){
                obj.innerHTML = s_html;
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
                    obj.innerHTML = text;
                }
                /* 出错，恢复到原编辑状态 */
                else{
                    obj.innerHTML = s_html;
                }
            }
        }
    },

    /**
     * 批量处理
     * 数据为 ListTable.oChoiced 中的ID值
     * 默认提交 ids[] 一个参数以及对应的数据
     *
     * @params str  url     要提交的URL，默认使用 ListTable.sUrl + url(如果url的格式为'?xx=xx&...')
     * @params obj  params  附加参数{$param:$value}
     * @params str  msg     消息提示，如果没填则表示不提示消息。
     *                      消息中的%d将会被转换为批处理记录个数
     */
    batch : function( url, params, msg ){
        /* 补充全URL */
        if( url.substr(0,1) == '?' ) url = ListTable.sUrl + url;

        /* 初始化附加属性 */
        if( typeof(params) != 'object' || !params ) params={};

        /* 构建记录IDS参数 */
        var count = 0, param = '';
        for( var id in this.oChoiced ){
            if( typeof(this.oChoiced[id]) != 'function' ){
                param += '&ids[]='+id; count++;
            }
        }

        /* 消息提示 */
        if( count == 0 ){
            wnd_alert('请选择记录！'); return false;
        }

		msg ? wnd_confirm(msg.replace('%d', count), {'ok':callback}) : callback();

		function callback(){
			/* 增加附加参数 */
			for( var i in params ){
				if( typeof(params[i]) != 'function' ){
					param += '&'+ i +'='+ params[i];
				}
			}

			/* 异步传输(同步等待) */
			var result = Ajax.call(url, param, null, "POST", "JSON", false);

			/* 显示消息 */
			if( result.message ){
				wnd_alert(result.message);
			}

			/* 处理成功，加载列表 */
			if( result.error == 0 ){
				ListTable.loadList();
			}
		}
    },


    /* ------------------------------------------------------ */
    // - 表格项选中函数集
    /* ------------------------------------------------------ */

    /**
     * 获取选中的值
     *
     * @params str  type  返回值类型
     *                    'VALUE'    表示返回 ID [$id] - 默认
     *                    'ASSOC'    表示返回 ID映射数据对象 {$id:{'id':$id,'object':$obj,'data':$data}}
     *                    'UNASSOC'  表示返回 数据对象 [{'id':$id,'object':$obj,'data':$data}]
     *
     * @return mix  返回数据对象或数组
     */
    getChoiced : function( type ){
        /* 初始化返回值类型 */
        var arr = type == 'ASSOC' ? {} : [];

        /* 构建返回值 */
        for( var id in this.oChoiced ){
            if( typeof(this.oChoiced[id]) != 'function' ){
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
        }
        
        /* 返回 */
        return arr;
    },

    /**
     * 初始化选中记录(清空记录，恢复背景色)
     *
     * @params str  label  要消除背景色的标签 (默认消除背景色 TR)
     *                     false或空字符表示不消除背景色
     *                     一般与 ListTable.schoice/mchoice 中的 label 参数是同值
     */
    ichoice : function( label ){
        /* 初始化高亮标签 */
        label = typeof(label) == 'string' || label === false ? label : 'tr';

        /* 已选中数据处理 */
        for( var id in this.oChoiced ){
            /* 无效数据 */
            if( typeof(this.oChoiced[id]) != 'object' ) continue;

            /* 恢复背景色(异常：对象被移除了HTML) */
            try{ label ? (this._rec(this.oChoiced[id].object,label).style.backgroundColor = '') : ''; }catch(e){}

            /* 移除数据 */
            delete this.oChoiced[id];
        }
    },

    /**
     * 选择所有行，由复选框触发(全选)
     *
     * @params bol  type   1/true表示全选, 0/false表示不选, -1表示反选.
     * @params str  cbs    复选框组的名字
     * @params str  touch  触发全选(或者不选)的复选框ID
     * @params str  label  要高亮的标签，通过obj向上递归到该标签，然后高亮。 (默认高亮 TR)
     *                     false或空字符表示不高亮，同时失去多点触发功能
     */
    achoice : function( type, cbs, touch, label ){
        /* 复选框组 */
        cbs = document.getElementsByName(cbs);

        /* 初始化 */
        var i, len = cbs.length;

        /* 无记录情况 */
        if( len == 0 ) return;

        /* 操作类型：不选 */
        if( type === 0 || type === false ){
            /* 初始化选中数据 */
            this.ichoice('tr');

            /* 撤销选中的复选框 */
            for( i=0; i < len; i++){
                if( cbs[i].type && cbs[i].type.toLowerCase() == 'checkbox' ){
                    cbs[i].checked = false;
                }
            }

            /* 撤销触发复选框选中状态。 */
            try{ document.getElementById(touch).checked = false }catch(e){}
        }

        /* 操作类型：全选 */
        else if( type === 1 || type === true ){
            for( i=0; i < len; i++){
                if( cbs[i].type && cbs[i].type.toLowerCase() == 'checkbox' && cbs[i].checked != true ){
                    this.mchoice(cbs[i], cbs[i].value, '', label); cbs[i].checked = true;
                }
            }

            /* 触发复选框选中状态 */
            try{ document.getElementById(touch).checked = true }catch(e){}
        }

        /* 操作类型：反选 */
        else if( type === -1 ){
            var checked = true;

            for( i=0; i < len; i++ ){
                if( cbs[i].type && cbs[i].type.toLowerCase() == 'checkbox' ){
                    this.mchoice(cbs[i], cbs[i].value, '', label);

                    cbs[i].checked = cbs[i].checked == true ? false : true;

                    checked = checked && cbs[i].checked;
                }
            }

            /* 触发复选框选中状态 */
            try{ document.getElementById(touch).checked = checked; }catch(e){}
        }
    },

    /**
     * 选择项并高亮选中项(单选)
     * 同一时刻只有一项被选中，再次单击撤销选中
     *
     * @params obj  obj    事件源对象
     * @params mix  id     数据：记录ID
     * @params mix  data   数据：记录数据
     * @params str  label  要高亮的标签，通过obj向上递归到该标签，然后高亮。(默认高亮 TR)
     *                     false或空字符表示不高亮，同时失去多点触发功能
     * @params str  color  高亮的颜色，默认为#FFFCC1
     *
     * @return mix  1表示选中成功，0表示撤销成功，false表示失败
     */
    schoice : function( obj, id, data, label, color ){
        /* 初始化高亮标签 */
        label = typeof(label) == 'string' || label === false ? label : 'tr';

        /* 已选中数据处理 */
        for( var i in this.oChoiced ){
            /* 无效数据 */
            if( typeof(this.oChoiced[i]) != 'object' ) continue;

            /* 恢复上个选中的背景色，如果出现异常(对象被移除了HTML)则移除数据并返回 */
            try{
                label ? (this._rec(this.oChoiced[i].object,label).style.backgroundColor = '') : '';
            }catch(e){
                delete this.oChoiced[i]; return false;
            }

            /* 再次单击撤销选中 - 移除数据并恢复背景色 - 多点触发 */
            if( this._rec(this.oChoiced[i].object,label) == this._rec(obj,label) ){
                /* 恢复背景色 */
                try{ label ? (this._rec(this.oChoiced[i].object,label).style.backgroundColor = '') : ''; }catch(e){}

                /* 移除数据 */
                delete this.oChoiced[i]; return 0;
            }
            /* 移除数据 */
            else{
                delete this.oChoiced[i];
            }
        }

        /* 保存选中记录ID */
        this.oChoiced[id] = {'id':id, 'object':obj, 'data':data};

        /* 设置背景色 */
        try{ label ? (this._rec(obj,label).style.backgroundColor = typeof(color)=='string'?color:'#FFFCC1') : ''; }catch(e){}

        return 1;
    },

    /**
     * 选择项并高亮选中项(多选)
     * 同一时刻可以多项被选中，再次单击撤销选中
     *
     * @params obj  obj    事件源对象
     * @params int  id     数据：记录ID
     * @params mix  data   数据：记录数据
     * @params str  label  要高亮的标签，通过obj向上递归到该标签，然后高亮。(默认高亮 TR)
     *                     false或空字符表示不高亮，同时失去多点触发功能
     * @params str  color  高亮的颜色，默认为#FFFCC1
     *
     * @return mix  1表示选中成功，0表示撤销成功，false表示失败
     */
    mchoice : function( obj, id, data, label, color ){
        /* 初始化剩余多选限制数 */
        var limit = this.iMCLimit;

        /* 初始化高亮标签 */
        label = typeof(label) == 'string' || label === false ? label : 'tr';

        /* 已选中数据处理 */
        for( var i in this.oChoiced ){
            /* 无效数据 */
            if( typeof(this.oChoiced[i]) != 'object' ) continue;

            /* 再次单击撤销选中 - 移除数据并恢复背景色 - 多点触发 */
            if( this._rec(this.oChoiced[i].object,label) == this._rec(obj,label) ){
                /* 恢复背景色 */
                try{ label ? (this._rec(this.oChoiced[i].object,label).style.backgroundColor = '') : ''; }catch(e){}

                /* 移除数据 */
                delete this.oChoiced[i]; return 0;
            }

            /* 多选限制 */
            if( limit != 0 && --limit <= 0 ){
                wnd_alert('最多只能选择 '+ this.iMCLimit +' 项！'); return false;
            }
        }

        /* 保存选中记录ID */
        this.oChoiced[id] = {'id':id, 'object':obj, 'data':data};

        /* 设置背景色 */
        try{ label ? (this._rec(obj,label).style.backgroundColor = typeof(color)=='string'?color:'#FFFCC1') : ''; }catch(e){}

        return 1;
    },


    /* ------------------------------------------------------ */
    // - 私有函数
    /* ------------------------------------------------------ */

    /**
     * 向上递归寻找指定标签的对象
     *
     * @params obj  obj    源对象
     * @params str  label  目标对象标签
     *
     * @return obj  成功返回目标对象，失败返回源对象
     */
    _rec : function( obj, label ){
        /* 无效标签 */
        if( !label ) return obj;

        /* 初始化目标对象 */
        var obj_dest = obj;

        /* 递归查找标签为label的对象 */
        while( obj_dest && obj_dest.tagName && obj_dest.tagName.toLowerCase() != label ){
            obj_dest = obj_dest.parentNode;
        }

        /* 返回目标对象 */
        if( obj_dest && obj_dest.tagName && obj_dest.tagName.toLowerCase() == label ){
            return obj_dest;
        }

        return obj;
    }
}