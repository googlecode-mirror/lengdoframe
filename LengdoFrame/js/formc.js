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
    // - 复选框
    /* ------------------------------------------------------ */

    /**
     * 获取复选框组
     *
     * @params str  name  复选框组名称
     */
    cbgByName : function( name ){
        /* 初始化 */
        var cbg = [];
        var arr = document.getElementsByName(name);

        /* 过滤非复选框项 */
        for( var i=0,len=arr.length; i < len; i++ ){
            if( arr[i].type && arr[i].type.toLowerCase() == 'checkbox' ) cbg.push(arr[i]);
        }

        /* 返回 */
        return cbg;
    },

    /**
     * 获取复选框组
     *
     * @params str  container  复选框组容器对象或者ID
     */
    cbgByContainer : function( container ){
        /* 初始化 */
        container = typeof(container) == 'object' && container ? container : document.getElementById(container);

        /* 递归结束 */
        if( container.type && container.type.toLowerCase() == 'checkbox' ) return [container];

        /* 递归获取复选框 */
        for( var i=0,cbg=[],len=container.childNodes.length; i < len; i++ ){
            cbg = cbg.concat(this.cbgByContainer(container.childNodes[i]));
        }

        /* 返回 */
        return cbg;
    },

    /**
     * 复选框同步复选框组
     *
     * @params mix  cb   复选框对象或者ID
     * @params obj  cbg  复选框组对象
     */
    cbSyncCbg : function( cb, cbg ){
        /* 初始化复选框对象 */
        cb = typeof(cb) == 'object' && cb ? cb : document.getElementById(cb);

        /* 同步 */
        this.cbgChecked(cbg, cb.checked);
    },

    /**
     * 容器内的复选框组同步复选框(递归容器)
     *
     * @params obj  cbg  复选框组对象
     * @params mix  cb   复选框对象或者ID
     */
    cbgSyncCb : function( cbg, cb ){
        /* 初始化复选框对象 */
        cb = typeof(cb) == 'object' && cb ? cb : document.getElementById(cb);

        /* 同步 */
        cb.checked = this.cbgState(cbg) == 1 ? true : false;
    },

    /**
     * 复选框组同步复选框组
     *
     * @params obj  cbg1  复选框组对象
     * @params obj  cbg2  复选框组对象
     */
    cbgSyncCbg : function( cbg1, cbg2 )
    {
        this.cbgChecked( cbg1, this.cbgState(cbg2)>0 );
    },

    /**
     * 设置复选框组的选中状态
     *
     * @params obj  cbg      复选框组对象
     * @params bol  checked  选中状态
     */
    cbgChecked : function( cbg, checked ){
        for( var i=0,len=cbg.length; i < len; i++ ){
            cbg[i].checked = checked;
        }
    },

    /**
     * 复选框组的状态
     *
     * @params obj  cbg  复选框组对象
     *
     * @return int  1表示全选中
     *              0表示全不选中
     *             -1表示部分选中
     */
    cbgState : function( cbg ){
        /* 获取复选框组的选中个数 */
        var checked = this.cbgCheckeds(cbg);

        /* 返回 */
        return checked ? (checked==cbg.length ? 1 : -1) : 0;
    },

    /**
     * 复选框组的选中个数
     *
     * @params obj  cbg  复选框组对象
     *
     * @return int  选中的个数
     */
    cbgCheckeds : function( cbg ){
        /* 获取复选框组的选中个数 */
        for( var i=0,len=cbg.length,cnt=0; i < len; i++ ){
            if( cbg[i].checked ) cnt++;
        }

        /* 返回 */
        return cnt;
    }
}