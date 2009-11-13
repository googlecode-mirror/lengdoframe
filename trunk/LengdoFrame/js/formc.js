// +----------------------------------------------------------------------
// | LengdoFrame - 表单控件对象
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


var Formc = {

    /* ------------------------------------------------------ */
    // - 通用
    /* ------------------------------------------------------ */

    /**
     * 获取表单控件对象
     *
     * @params mix  form  表单对象或者ID
     * @params str  name  表单控件名称
     *
     * @return obj  返回表单控件对象，失败返回NULL
     */
    get : function( form, name ){
        /* 初始化表单对象 */
        form = typeof(form) == 'object' && form ? form : document.getElementById(form);

        /* 无效表单 */
        if( !form ) return null;

        /* 查找表单控件并返回 */
        for( var i=0, len=form.length; i < len; i++ ){
            if( form[i].name == name ){
                return form[i];
            }
        }

        /* 返回 */
        return null;
    },

    /**
     * 获取表单控件集对象
     *
     * @params mix  form  表单对象或者ID
     * @params str  name  表单控件名称
     *
     * @return arr  返回表单控件集对象，失败返回NULL
     */
    gets : function ( form, name ){
        /* 初始化表单对象 */
        form = typeof(form) == 'object' && form ? form : document.getElementById(form);

        /* 无效表单 */
        if( !form ) return null;

        /* 查找表单控件并返回 */
        for( var i=0, arr = [], len=form.length; i < len; i++ ){
            if( form[i].name == name ){
                arr.push(form[i]);
            }
        }

        /* 返回 */
        return arr;
    },

    /**
     * 聚焦表单控件
     *
     * @params mix  form    表单对象或者ID
     * @params str  name    表单控件名称
     * @params bol  select  是否选中表单控件内容
     */
    focus : function( form, name, select ){
        /* 获取表单控件对象 */
        var formc = this.get(form, name);

        /* 表单控件聚焦 */
        try{ if( formc ) formc.focus(); }catch(e){}

        /* 表单控件内容选中 */
        try{ if( select ) formc.select(); }catch(e){}
    },


    /* ------------------------------------------------------ */
    // - 复选框类
    /* ------------------------------------------------------ */

    /**
     * 复选框同步复选框组
     *
     * @params obj  obj        复选框对象
     * @params str  name       复选框组名字(优先)
     * @params mix  container  复选框组所在容器对象或者ID
     */
    cbSyncCbg : function( obj, name, container ){
        this.cbgChecked(obj.checked, name, container);
    },

    /**
     * 容器内的复选框组同步复选框(递归容器)
     *
     * @params str  name       复选框组名字(优先)
     * @params mix  container  复选框组所在容器对象或者ID
     * @params mix  obj        复选框对象或者ID
     */
    cbgSyncCb : function( name, container, obj ){
        /* 初始化复选框对象 */
        obj = typeof(obj) == 'object' ? obj : document.getElementById(obj);

        if( this.isCb(obj) ){
            obj.checked = this.cbgState(name, container) == 1 ? true : false;
        }
    },

    /**
     * 复选框组同步复选框组
     *
     * @params str  s_name       源复选框组名字(优先)
     * @params mix  s_container  源复选框组容器对象或者ID
     * @params str  d_name       目标复选框组名字(优先)
     * @params mix  d_container  目标复选框组容器对象或者ID
     */
    cbgSyncCbg : function( s_name, s_container, d_name, d_container )
    {
        this.cbgChecked( (this.cbgState(s_name,s_container) == 1 ? true : false), d_name, d_container );
    },

    /**
     * 设置复选框组状态
     *
     * @params bol  checked    是否选中
     * @params str  name       复选框组名字(优先)
     * @params mix  container  复选框组所在容器对象或者ID
     */
    cbgChecked : function( checked, name, container ){
        if( name ){
            return this.cbgCheckedByName(checked, name);
        }else{
            /* 初始化容器对象 */
            container = typeof(container) == 'object' ? container : document.getElementById(container);

            return this.cbgCheckedByContainer(checked, container);
        }
    },
    cbgCheckedByName : function( checked, name ){
        var cbs = document.getElementsByName(name);

        for( var i=0,len=cbs.length; i < len; i++ ){
            if( this.isCb(cbs[i]) ) cbs[i].checked = checked;
        }

        return true;
    },
    cbgCheckedByContainer : function( checked, container ){
        if( this.isCb(container) ){
            container.checked = checked; return true;
        }

        for( var i=0,len=container.childNodes.length; i < len; i++ ){
            this.cbgCheckedByContainer(checked, container.childNodes[i]);
        }
    },


    /**
     * 获取复选框组状态
     *
     * @params str  name       复选框组名字(优先)
     * @params mix  container  复选框组所在容器对象或者ID
     *
     * @return bol  0表示全不选中，1表示全选中，-1表示部分选中
     */
    cbgState : function( name, container ){
        if( name ){
            return this.cbgStateByName(name);
        }else{
            /* 初始化容器对象 */
            container = typeof(container) == 'object' ? container : document.getElementById(container);

            return this.cbgStateByContainer(container);
        }
    },
    cbgStateByName : function( name ){
        var checked = 0;

        var cbs = document.getElementsByName(name);
        var len = cbs.length;

        if( len == 0 ) return 0;

        for( var i=0; i < len; i++ ){
            if( this.isCb(cbs[i]) && cbs[i].checked ){
                checked++;
            }
        }

        if( len == checked ){
            return 1;
        }else{
            return -1;
        }

        return true;
    },
    cbgStateByContainer : function( container, checked ){
        if( this.isCb(container) ){
            if( isNaN(checked) ){
                return container.checked ? 1 : 0;
            }else if( checked == 0 ){
                return container.checked ? -1 : 0;
            }else if( checked == 1 ){
                return container.checked ? 1 : -1;
            }

            return -1;
        }

        for( var i=0,len=container.childNodes.length; i < len; i++ ){
            checked = this.cbgStateByContainer(container.childNodes[i], checked);

            if( checked == -1 ) break;
        }

        return checked;
    },

    /**
     * 复选框组的选中个数
     *
     * @params str  name       复选框组名字(优先)
     * @params mix  container  复选框组所在容器对象或者ID
     *
     * @return int  选中的个数
     */
    cbgCheckeds : function( name, container ){
        var cnt = 0;

        if( name ){
            return this.cbgCheckedsByName(name);
        }else{
            /* 初始化容器对象 */
            container = typeof(container) == 'object' ? container : document.getElementById(container);

            return this.cbgCheckedsByContainer(container);
        }
    },
    cbgCheckedsByName : function( name ){
        var cnt = 0;

        var cbg = document.getElementsByName(name);
        for( var i=0,len=cbg.length; i < len; i++ ){
            if( this.isCb(cbg[i]) && cbg[i].checked ){
                cnt++;
            }
        }

        return cnt;
    },
    cbgCheckedsByContainer : function( container ){
        var cnt = 0;

        if( this.isCb(container) && container.checked ){
            return ++cnt;
        }

        for( var i=0,len=container.childNodes.length; i < len; i++ ){
            cnt += this.cbgCheckedsByContainer(container.childNodes[i]);
        }

        return cnt;
    },

    /**
     * 检测是否是复选框
     *
     * @params obj  obj  被检测对象
     */
    isCb : function( obj ){
        try{
            return obj.type.toLowerCase() == 'checkbox';
        }catch(e){
            return false;
        }
    }
}