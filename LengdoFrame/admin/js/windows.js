// +----------------------------------------------------------------------
// | LengdoFrame - 后台弹出窗口函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 管理员管理
/* ------------------------------------------------------ */

/**
 * 增/编管理员
 */
function wnd_admin_fill( caller, act, admin_id )
{
    /* 初始化 */
    var url = 'modules/admin/admin.php';
    var wnd = Wnds.find('wnd-admin-fill');

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-admin-fill', {'okb':deal_admin_fill}, {'width':660,'height':420,'overflow':100});
        wnd.create();
    }

    /* 初始化参数 */
    url += act == 'add' ? '?act=add' : '?act=edit&admin_id='+admin_id;
    var title = act == 'add' ? '增加管理员' : '编辑管理员';

    /* 设置数据 - 当前操作 */
    wnd.setData('act', act);

    wnd.title(title);
    wnd.inner(url, 'url');

    wnd.show();
    wnd.activeControl('ok', function(e){if(e.keyCode==27)this.cannel()});
}
function deal_admin_fill()
{
    /* 初始化 */
    var url = 'modules/admin/admin.php';
    var wnd = Wnds.find('wnd-admin-fill');
    var act = wnd.getData('act') == 'add' ? '?act=insert' : '?act=update';

    /* 异步提交(异步等待) */
    Ajax.call(url+act, deal_form_params('wfm-admin-fill'), callback, 'POST', 'JSON');

    /* 回调函数 */
    function callback( result, text ){
        if( result.error == 0 ){
            /* 初始化并重载列表 */
            ListTable.init('list-admin', url, '?act=query');
            ListTable.loadList();

            wnd.hidden();
        }else{
            wnd_alert(result.message, {'overlay':0,'hidden':function(){try{wnd.getData('blur').focus();}catch(e){}}});
        }
    }

    return false;
}


/* ------------------------------------------------------ */
// - 角色管理
/* ------------------------------------------------------ */

/**
 * 增/编角色
 */
function wnd_role_fill( caller, act, role_id )
{
    /* 初始化 */
    var url = 'modules/admin/role.php';
    var wnd = Wnds.find('wnd-role-fill');

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-role-fill', {'okb':deal_role_fill}, {'width':660,'height':420,'overflow':100});
        wnd.create();
    }

    /* 初始化参数 */
    url += act == 'add' ? '?act=add' : '?act=edit&role_id='+role_id;
    var title = act == 'add' ? '增加角色' : '编辑角色';

    /* 设置数据 - 当前操作 */
    wnd.setData('act', act);

    wnd.title(title);
    wnd.inner(url, 'url');

    wnd.show();
    wnd.activeControl('ok', function(e){if(e.keyCode==27)this.cannel()});
}
function deal_role_fill()
{
    /* 初始化 */
    var url = 'modules/admin/role.php';
    var wnd = Wnds.find('wnd-role-fill');
    var act = wnd.getData('act') == 'add' ? '?act=insert' : '?act=update';

    /* 异步提交(异步等待) */
    Ajax.call(url+act, deal_form_params('wfm-role-fill'), callback, 'POST', 'JSON');

    /* 回调函数 */
    function callback( result, text ){
        if( result.error == 0 ){
            /* 初始化并重载列表 */
            ListTable.init('list-role', url, '?act=query');
            ListTable.loadList();

            wnd.hidden();
        }else{
            wnd_alert(result.message, {'overlay':0,'hidden':function(){try{wnd.getData('blur').focus();}catch(e){}}});
        }
    }

    return false;
}


/* ------------------------------------------------------ */
// - 数据库
/* ------------------------------------------------------ */

/**
 * 数据库优化
 */
function deal_dboptimize()
{
    /* 初始化 */
    var url = 'modules/db/db_optimize.php';
    var act = '?act=optimize';

    /* 执行等待中 */
    wnd_wait('数据库优化中...');

    /* 异步提交(异步等待) */
    Ajax.call(url+act, '', callback, 'GET', 'JSON', true, true);

    function callback( result, text ){
        /* 清除执行等待窗口 */
        wnd_wait_clear();

        if( result.message ){
            wnd_alert(result.message);
        }

        if( result.error == 0 ){
            /* 初始化并重载列表 */
            ListTable.init('list-dboptimize', url, '?act=query');
            ListTable.loadList(true, true);
        }
    }
}


/**
 * 数据库备份
 */
function wnd_dbbackup_fill()
{
    /* 初始化 */
    var url = 'modules/db/db_backup.php?act=backup';
    var wnd = Wnds.find('wnd-dbbackup-fill');

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-dbbackup-fill', {'okb':deal_dbbackup_fill}, {'width':660,'height':420,'overflow':100});
        wnd.create();

        /* 初始化参数 */
        wnd.title('数据库备份');
    }

    wnd.inner(url, 'url');

    wnd.show();
    wnd.activeControl('ok', function(e){if(e.keyCode==27)this.cannel()});
}
function deal_dbbackup_fill( params )
{
    /* 初始化 */
    var url = 'modules/db/db_backup.php';
    var act = '?act=dumpsql';

	/* 执行等待中 */
	if( !params ) wnd_wait('备份中...');

	/* 提交的参数 */
	var params = typeof(params) == 'string' ? params : deal_form_params('wfm-dbbackup-fill');

	/* 异步提交(异步等待) */
    Ajax.call(url+act, params, callback, 'POST', 'JSON', true, true);

    function callback( result, text ){
		/* 未处理完成 */
        if( result.error == -1 ){
            wnd_wait(result.message);
			deal_dbbackup_fill(result.content);
		}

		/* 处理完成 */
		if( result.error == 0 ){
            wnd_wait_clear();
            wnd_alert(result.message);

            /* 初始化并重载列表 */
            ListTable.init('list-dbbackup', url, '?act=query');
            ListTable.loadList(true, true);
        }
    }
}
/**
 * 数据库备份 - 导入服务器文件
 */
function deal_dbbackup_import( params, file )
{
    /* 初始化 */
    var url = 'modules/db/db_backup.php?act=import';

	/* 确认OK - 回调函数 */
	function confirm_callback(){
        /* 初始化提示 */
        if( params.indexOf('init=1') != -1 ){
            wnd_wait('数据导入初始化中...');
        }

        /* 异步提交(异步等待) */
		Ajax.call(url, params, ajax_callback, 'POST', 'JSON', true, true);

		function ajax_callback( result, text ){
            /* 0表示导入完成，-1表示导入未完成，1表示出错 */
            if( result.error == 0 ){
                wnd_wait_clear();
                wnd_alert(result.message);
            }
            else if( result.error == -1 ){
                wnd_wait(result.message);
                deal_dbbackup_import(result.content);
            }
            else if( result.error == 1 ){
                wnd_wait_clear();
                wnd_alert(result.message);
            }
		}
	}

	/* 确认提示 */
    if( file ){
	    wnd_confirm('确定导入备份文件 <b>'+ file +'</b> ？', {'ok':confirm_callback});
    }else{
        confirm_callback();
    }
}
/**
 * 数据库备份 - 上传SQL文件
 */
function deal_dbbackup_upload( form, result )
{
    if( result.message ){
        wnd_alert(result.message);
    }

    /* 重置表单 */
    form.reset();
}


/* ------------------------------------------------------ */
// - 模块管理
/* ------------------------------------------------------ */

/**
 * 增/编模块
 *
 * @params obj  caller  调用者对象
 * @params str  act     当前操作
 * @params int  id      数据：模块ID[编辑时]，模块父ID[增加时]
 */
function wnd_module_fill( caller, act, id )
{
    /* 初始化 */
    var url = 'modules/kernel/module.php';
    var wnd = Wnds.find('wnd-module-fill');

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-module-fill', {'okb':deal_module_fill}, {'width':300});
        wnd.create();
    }

    /* 初始化参数 */
    url += act == 'add' ? '?act=add&parent_id='+id : '?act=edit&module_id='+id;
    var title = act == 'add' ? '增加模块' : '编辑模块';

    /* 设置数据 - 当前操作 */
    wnd.setData('act', act);

    wnd.title(title);
    wnd.inner(url, 'url');

    wnd.show();
    wnd.activeControl('ok', function(e){if(e.keyCode==27)this.cannel()});
}
function deal_module_fill()
{
    /* 初始化 */
    var url = 'modules/kernel/module.php';
    var wnd = Wnds.find('wnd-module-fill');
    var act = wnd.getData('act') == 'add' ? '?act=insert' : '?act=update';

    /* 异步提交(异步等待) */
    Ajax.call(url+act, deal_form_params('wfm-module-fill'), callback, 'POST', 'JSON');

    /* 回调函数 */
    function callback( result, text ){
        if( result.error == 0 ){
            /* 初始化并重载列表 */
            ListTable.init('list-module', url, '?act=query');
            ListTable.loadList();

            wnd.hidden();
        }else{
            wnd_alert(result.message, {'overlay':0,'hidden':function(){try{wnd.getData('blur').focus();}catch(e){}}});
        }
    }

    return false;
}


/* ------------------------------------------------------ */
// - 权限管理
/* ------------------------------------------------------ */

/**
 * 增/编权限
 */
function wnd_privilege_fill( caller, act, privilege_id )
{
    /* 初始化 */
    var url = 'modules/kernel/privilege.php';
    var wnd = Wnds.find('wnd-privilege-fill');

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-privilege-fill', {'okb':deal_privilege_fill}, {'width':300});
        wnd.create();
    }

    /* 初始化参数 */
    url += act == 'add' ? '?act=add' : '?act=edit&privilege_id='+privilege_id;
    var title = act == 'add' ? '增加权限' : '编辑权限';

    /* 设置数据 - 当前操作 */
    wnd.setData('act', act);

    wnd.title(title);
    wnd.inner(url, 'url');

    wnd.show();
    wnd.activeControl('ok', function(e){if(e.keyCode==27)this.cannel()});
}
function deal_privilege_fill()
{
    /* 初始化 */
    var url = 'modules/kernel/privilege.php';
    var wnd = Wnds.find('wnd-privilege-fill');
    var act = wnd.getData('act') == 'add' ? '?act=insert' : '?act=update';

    /* 异步提交(异步等待) */
    Ajax.call(url+act, deal_form_params('wfm-privilege-fill'), callback, 'POST', 'JSON');

    /* 回调函数 */
    function callback( result, text ){
        if( result.error == 0 ){
            /* 初始化并重载列表 */
            ListTable.init('list-privilege', url, '?act=query');
            ListTable.loadList();

            wnd.hidden();
        }else{
            wnd_alert(result.message, {'overlay':0,'hidden':function(){try{wnd.getData('blur').focus();}catch(e){}}});
        }
    }

    return false;
}


/* ------------------------------------------------------ */
// - 系统模块
/* ------------------------------------------------------ */

/**
 * 我的帐号
 */
function wnd_myaccount_fill()
{
    /* 初始化 */
    var url = 'modules/sys/my_account.php';
    var wnd = Wnds.find('wnd-myaccount-fill');

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-myaccount-fill', {'okb':deal_myaccount_fill}, {'width':500});
        wnd.create();

        /* 初始化参数 */
        wnd.title('我的帐号');
    }

    wnd.inner(url, 'url');

    wnd.show();
    wnd.activeControl('ok', function(e){if(e.keyCode==27)this.cannel()});
}
function deal_myaccount_fill()
{
    /* 初始化 */
    var url = 'modules/sys/my_account.php?act=update';
    var wnd = Wnds.find('wnd-myaccount-fill');

    /* 异步提交(异步等待) */
    Ajax.call(url, deal_form_params('wfm-myaccount-fill'), callback, 'POST', 'JSON');

    /* 回调函数 */
    function callback( result, text ){
        if( result.error == 0 ){
            if( result.message ){
                wnd_alert(result.message, {'overlay':0});
            }

            wnd.hidden();
        }else{
            wnd_alert(result.message, {'overlay':0,'hidden':function(){try{wnd.getData('blur').focus();}catch(e){}}});
        }
    }

    return false;
}


/* ------------------------------------------------------ */
// - 通用模块
/* ------------------------------------------------------ */

/**
 * 系统信息
 */
function wnd_sysinfo_view()
{
    /* 初始化 */
    var url = 'modules/common/sysinfo.php';
    var wnd = Wnds.find('wnd-sysinfo-view');

    /* 构建窗口 */
    if( !wnd ){
        wnd = new Wnd('wnd-sysinfo-view', null, {'width':420,'control':'ok'});
        wnd.create();

        /* 初始化参数 */
        wnd.title('系统信息');
        wnd.inner(url, 'url');
    }

    wnd.show();
    wnd.activeControl('ok', function(e){if(e.keyCode==27)this.cannel()});
}

/**
 * 系统退出
 */
function wnd_syslogout()
{
    /* 回调函数 */
    function callback(){
        /* 显示页面加载中 */
        deal_webpage_load();

        /* 页面跳转 */
        window.location.href = 'index.php?act=logout';
    }

    wnd_confirm('确认退出系统？', {'ok':callback});
}


/* ------------------------------------------------------ */
// - 列表
/* ------------------------------------------------------ */

/**
 * 通用 - 当前列表搜索
 *
 * @params obj  form    表单对象
 * @params obj  filter  附加的搜索条件
 * @params str  id      列表的ID
 *
 * @return bol  false
 */
function deal_search_list( form, filter, id )
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

/**
 * 通用 - 当前列表搜索初始化
 *        载入方式1：通过重载模块(如果不提供 wndele 参数)
 *        载入方式2：通过重载窗口(如果提供了 wndele 的参数)
 *
 * @params mix  wnd      窗体内元素或者要重载的窗口ID
 * @params obj  attribs  窗体重载时的参数
 */
function deal_search_init( wndele, attribs )
{
    if( typeof(wndele) == 'string' ){
        Wnds.find(wndele).reinner(attribs)
    }
    else if( typeof(wndele) == 'object' ){
        Wnds.findByElement(wndele).reinner(attribs);
    }
    else{
        module_mtree_request(window.MODULE_URL, true);
    }
}


/* ------------------------------------------------------ */
// - 列表 - 导出
/* ------------------------------------------------------ */

/**
 * 列表数据导出
 *
 * @params str  id     列表ID
 * @params str  form   自定义导出参数的表单ID
 * @params str  url    自定义导出参数的URL
 * @params str  urldo  要导出的URL
 */
function wnd_list_export( id, form, url, urldo )
{
}
/**
 * @params str  id     列表ID
 * @params str  form   自定义导出参数的表单ID
 * @params str  url    要导出的URL
 * @params mix  limit  要导出的记录数 'all','page','choice',number
 */
function deal_list_export( id, form, url, limit )
{
    /* 初始化列表 */
    ListTable.init(id);

    /* 附加列表搜索参数 */
    url += ListTable.buildFilter();

    /* 自定义导出 */
    if( form ){
        form = document.getElementById(form);
    }

    /* 快速导出 */
    else{
        /* 获取表单对象 */
        form = document.getElementById('wfm-list-export-auto');

        if( !form ){
            form = document.createElement('FORM');

            form.id = 'wfm-list-export-auto';

            document.body.appendChild(form);
        }

        /* 添加表单域 - 要导出的记录数 */
        form.innerHTML = '<input type="hidden" name="limit" value="'+ limit +'" />';

        /* 添加表单域 - 要导出的记录ID集 */
        if( limit == 'choice' ){
            var flag = false;

            for( var id in ListTable.oChoiced ){
                form.innerHTML += '<input type="hidden" name="ids[]" value="'+ id +'" />'; flag = true;
            }

            if( flag == false ){
                wnd_alert('请选择要导出的记录！'); return false;
            }
        }
    }

    /* 表单提交 */
    deal_form_submit(form, url, null, 'TEXT', false); form.submit();
}


/* ------------------------------------------------------ */
// - 表格行移动
/* ------------------------------------------------------ */

function deal_tbltr_move( caller, updown, id, module_url )
{
    /* 初始化 */
    var url = module_url + '?act=updown';

    /* 异步提交(异步等待) */
    Ajax.call(url, 'id='+ id +'&updown='+ updown, callback, 'POST', 'JSON');

    /* 回调函数 */
    function callback( result, text ){
        if( result.error == 0 ){
            deal_tbltr_moved(caller, updown);
        }
    }
}
function deal_tbltr_moved( caller, updown )
{
    /* 上移 */
    if( updown == 'up' ){
        deal_tbltr_umoved(caller); return;
    }

    /* 获取行对象 */
    var tr = caller;
    while( tr.tagName.toLowerCase() != 'tr' ){
        tr = tr.parentNode;
    }

    /* 获取表格对象 */
    var tbl = tr;
    while( tbl.tagName.toLowerCase() != 'table' ){
        tbl = tbl.parentNode;
    }

    /* 下移 */
    for( var i=0,j=0,len=tbl.rows.length; i < len; i++ ){
        if( j != 0 && tr.className == tbl.rows[i].className ){
            deal_tbltr_umoved(tbl.rows[i]); return;
        }

        if( tbl.rows[i] == tr ){
            j = i;
        }
    }
}
function deal_tbltr_umoved( caller )
{
    /* 获取行对象 */
    var tr = caller;
    while( tr.tagName.toLowerCase() != 'tr' ){
        tr = tr.parentNode;
    }

    /* 获取表格对象 */
    var tbl = tr;
    while( tbl.tagName.toLowerCase() != 'table' ){
        tbl = tbl.parentNode;
    }

    /* 获取当前节点索引和插入点节点对象 */
    for( var i=0,len=tbl.rows.length; i < len; i++ ){
        /* 当前节点索引 */
        if( tbl.rows[i] == tr ){
            var ind_cur = i; break;
        }

        /* 插入点节点对象 */
        if( tbl.rows[i].className == tr.className ){
            var obj_des = tbl.rows[i];
        }
    }

    try{
        do{
            obj_des.parentNode.insertBefore(tbl.rows[ind_cur], obj_des);
        }while( tbl.rows[++ind_cur].className > tr.className );
    }catch(e){};
}
