<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 权限系统函数库
// | * 细粒度权限系统
// | * 该权限系统基于模块和模块组层次结构设计
// | * 每个模块对应一个文件，在所有模块中文件名必须唯一
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 权限系统 - 登陆和登出接口
/* ------------------------------------------------------ */

/**
 * 管理员登陆
 *
 * @params arr  $info  登陆信息
 *                     $info['username'],$info['password']  //[用户登陆]使用用户名和密码登陆
 *                     $info['admin_id']  //[系统内用]使用管理员ID登陆
 *
 * @return bol  true 表示登陆成功, false 表示失败
 */
function admin_login( $info )
{
    /* 登陆方式 - 1.管理员ID - 2.用户名密码 */
    if( intval($info['admin_id']) > 0 ){
        $sql = 'SELECT * FROM '. tname('admin') .' WHERE admin_id='.intval($info['admin_id']);
    }
    elseif( trim($info['username']) != '' && trim($info['password']) != '' ){
        $sql = ' SELECT * FROM '. tname('admin');
        $sql.= ' WHERE username="'. trim($info['username']) .'" AND password="'. md5(trim($info['password'])) .'"';
    }
    else{
        return false;
    }

    /* 管理员信息 */
    $info = $GLOBALS['db']->getRow($sql);

    /* 无效的登陆信息 */
    if( empty($info) ) return false;

    /* 设置管理员基本信息 SESSION */
    $_SESSION[SN_ADMIN]['id']        = $info['admin_id'];
    $_SESSION[SN_ADMIN]['name']      = $info['name'];
    $_SESSION[SN_ADMIN]['username']  = $info['username'];
    $_SESSION[SN_ADMIN]['pfiletime'] = $info['pfile_time'];

    /* 检查当前权限文件有效性 */
    if( !admin_pfile_valid() ){
        return init_privilege_sys($info['admin_id']);
    }

    return true;
}

/**
 * 确定管理员是否登陆过
 */
function admin_logined()
{
    return admin_id() > 0;
}

/**
 * 当前管理员注销
 */
function admin_destroy()
{
    unset($_SESSION[SN_ADMIN]);
}

/**
 * 当前管理员登出
 */
function admin_logout()
{
    /* 初始化 */
    global $_CFG;

    /* 注销登陆 */
    admin_destroy();

    /* 跳转到后台登陆窗口 */
    redirect($_CFG['URL_ADMIN'] . 'index.php?act=login');
}



/* ------------------------------------------------------ */
// - 权限系统 - 核心接口
/* ------------------------------------------------------ */

/**
 * 刷新权限系统 - 重新初始化权限系统
 */
function flush_privilege_sys()
{
    init_privilege_sys( admin_id() );
}


/**
 * 初始化权限系统 - 构建权限系统所需的完整数据
 * 初始化权限信息并写入权限文件，保存权限文件修改时间并重新登陆
 *
 * @params int  $admin_id  管理员ID
 *
 * @return bol  true 表示初始化成功, false 表示失败
 */
function init_privilege_sys( $admin_id )
{
    /* 无效管理员ID */
    if( intval($admin_id) <= 0 ) return false;

    /* 创建权限文件 */
    admin_pfile_create( info_privilege_sys($admin_id) );

    /* 清除文件状态缓存 - 防止filemtime返回缓存的上次修改时间 */
    clearstatcache();

    /* 保存权限文件修改时间到数据库 */
    $fields = array( 'pfile_time'=>filemtime(admin_pfile()) );
    $GLOBALS['db']->update( tname('admin'), $fields, 'admin_id='.admin_id() );

    /* 重新登陆 */
    admin_login( array('admin_id'=>admin_id()) );

    return true;
}

/**
 * 取得权限系统所需的权限信息
 *
 * @params int  $admin_id  管理员ID
 *
 * @return arr  权限信息数组
 */
function info_privilege_sys( $admin_id )
{
    /* 无效管理员ID */
    if( ($admin_id = intval($admin_id)) <= 0 ) return array();

    /* 初始化 */
    $m_ids = array(); //管理员拥有的模块IDS

    /* 权限相关信息变量初始化 */
    $_priv = array();
    $_priv['role']          = array(); //管理员角色信息
    $_priv['module_map']    = array(); //管理员拥有的模块的映射
    $_priv['privilege_map'] = array(); //管理员拥有的权限的映射

    /* 管理员拥有的细粒度权限数据(ID为1的管理员拥有所有细粒度权限) */
    $sql  = ' SELECT p.privilege_id, p.name AS privilege_name,'; //SELECT, 权限字段
    $sql .= ' p.module_act_code, p.module_act_name, p.`order` AS privilege_order,'; //SELECT, 权限字段
    $sql .= ' m.module_id, m.name AS module_name, m.file AS module_file'; //SELECT, 模块字段

    if( $admin_id == 1 ){
        $sql .= ' FROM '. tname('privilege') .' AS p'; //FROM, 用户权限和权限连表
        $sql .= ' LEFT JOIN '. tname('module') .' AS m USING(module_id)'; //FROM, 权限和模块连表
        $sql .= ' ORDER BY p.`order` ASC'; //ORDER
    }else{
        $sql .= ' FROM '. tname('admin_privilege') .' AS ap LEFT JOIN '. tname('privilege') .' AS p USING(privilege_id)'; //FROM, 用户权限和权限连表
        $sql .= ' LEFT JOIN '. tname('module') .' AS m USING(module_id)'; //FROM, 权限和模块连表
        $sql .= ' WHERE ap.admin_id='. $admin_id; //WHERE
        $sql .= ' ORDER BY p.`order` ASC'; //ORDER
    }

    /* 管理员拥有的细粒度权限 */
    $privs = $GLOBALS['db']->getAll($sql);

    /* 初始化数据到变量 */
    foreach( $privs AS $priv ){
        /* 模块文件名 映射 模块记录 */
        $tmp = array();
        $tmp['id']   = $priv['module_id'];
        $tmp['name'] = $priv['module_name'];

        $_priv['module_map'][$priv['module_file']] = $tmp;

        /* 管理员拥有的模块ID */
        $m_ids[$priv['module_id']] = $priv['module_id'];

        /* 模块文件名_模块操作码 映射 权限记录 */
        $tmp = array();
        $tmp['id']              = $priv['privilege_id'];
        $tmp['name']            = $priv['privilege_name'];
        $tmp['order']           = $priv['privilege_order'];
        $tmp['module_id']       = $priv['module_id'];
        $tmp['module_act_code'] = $priv['module_act_code'];
        $tmp['module_act_name'] = $priv['module_act_name'];

        $_priv['privilege_map'][ $priv['module_file'].'_'.$priv['module_act_code'] ] = $tmp;
    }

    /* 管理员的角色信息 */
    $sql = ' SELECT '. tname('role') .'.* FROM '. tname('admin') .' INNER JOIN '. tname('role');
    $sql.= ' USING(role_id) WHERE admin_id='. $admin_id;
    $_priv['role'] = $GLOBALS['db']->getRow($sql);

    /* 管理员的角色拥有的权限数据(ID为1的角色拥有所有权限) */
    if( intval($_priv['role']['role_id']) >= 1 ){
        $sql  = ' SELECT p.privilege_id, p.name AS privilege_name,'; //SELECT, 权限字段
        $sql .= ' p.module_act_code, p.module_act_name, p.`order` AS privilege_order,'; //SELECT, 权限字段
        $sql .= ' m.module_id, m.name AS module_name, m.file AS module_file'; //SELECT, 模块字段

        if( intval($_privs['role']['role_id']) == 1 ){
            $sql .= ' FROM '. tname('privilege') .' AS p'; //FROM, 角色权限和权限连表
            $sql .= ' LEFT JOIN '. tname('module') .' AS m USING(module_id)'; //FROM, 权限和模块连表
            $sql .= ' ORDER BY p.`order` ASC'; //ORDER
        }else{
            $sql .= ' FROM '. tname('role_privilege') .' AS rp LEFT JOIN '. tname('privilege') .' AS p USING(privilege_id)'; //FROM, 角色权限和权限连表
            $sql .= ' LEFT JOIN '. tname('module') .' AS m USING(module_id)';  //FROM, 权限和模块连表
            $sql .= ' WHERE rp.role_id='. $_priv['role']['role_id']; //WHERE
            $sql .= ' ORDER BY p.`order` ASC'; //ORDER
        }

        /* 管理员的角色拥有的权限 */
        $privs = $GLOBALS['db']->getAll($sql);
    }else{
        $privs = array();
    }

    /* 初始化数据到变量 */
    foreach( $privs AS $priv ){
        /* 模块文件名 映射 模块记录 */
        $tmp = array();
        $tmp['id']   = $priv['module_id'];
        $tmp['name'] = $priv['module_name'];

        $_priv['module_map'][$priv['module_file']] = $tmp;

        /* 管理员拥有的模块IDS */
        $m_ids[$priv['module_id']] = $priv['module_id'];

        /* 模块文件名_模块操作码 映射 权限记录 */
        $tmp = array();
        $tmp['id']              = $priv['privilege_id'];
        $tmp['name']            = $priv['privilege_name'];
        $tmp['order']           = $priv['privilege_order'];
        $tmp['module_id']       = $priv['module_id'];
        $tmp['module_act_code'] = $priv['module_act_code'];
        $tmp['module_act_name'] = $priv['module_act_name'];

        $_priv['privilege_map'][ $priv['module_file'].'_'.$priv['module_act_code'] ] = $tmp;
    }

    /* 取得所有模块 */
    $sql = 'SELECT * FROM '. tname('module') .' WHERE module_id <> 1 ORDER BY lft ASC';
    $modules = $GLOBALS['db']->getAll($sql);

    /* 初始化隐藏模块标记层 */
    $hlvl = -1;

    /* 隐藏模块(过滤隐藏的模块) */
    foreach( $modules AS $i=>$r ){
        /* 重置隐藏模块标记层 */
        if( $hlvl != -1 && $r['lvl'] <= $hlvl ) $hlvl = -1;

        /* 设置隐藏模块标记层 */
        if( $hlvl == -1 && $r['hidden'] == 1 ) $hlvl = $r['lvl'];

        /* UNSET隐藏模块 */
        if( $hlvl != -1 && $r['lvl'] >= $hlvl ) unset($modules[$i]);
    }

    /* 重置数组下标 */
    $modules = array_values($modules);

    /* 模块过滤(过滤无权限的模块) */
    for( $i = count($modules)-1; $i >= 0; $i-- ){
        /* 保留预设节点 */
        if( in_array($modules[$i]['module_id'],$m_ids) ) continue;

        /* 非保留预设节点，但是保留预设节点的父节点 */
        if( $modules[$i+1]['lvl'] > $modules[$i]['lvl'] ) continue;

        /* 移除元素(自动重置数组下标) */
        array_splice($modules, $i, 1);
    }

    /* 构建用户模块菜单HTML - 构建HTML */
    $_priv['module_mtree'] = module_mtree($modules);

    /* 返回 */
    return $_priv;
}

/**
 * 取得当前管理员对某一模块的所有操作
 *
 * @params str  $module_file  模块处理文件
 *
 * @return arr
 */
function admin_module_acts( $module_file )
{
    global $_PRIV;

    $module_id = admin_module_id_fk($module_file);

    if( $module_id == 0 ){
        return array();
    }

    $acts = array();
    foreach( $_PRIV['privilege_map'] AS $r ){
        if( $r['module_id'] == $module_id ){
            $acts[] = $r;
        }
    }

    return $acts;
}

/**
 * 取得当前管理员的所有模块ID数组
 *
 * @return arr
 */
function admin_module_ids()
{
    return $GLOBALS['_PRIV']['module_ids'];
}
/**
 * 取得当前管理员权限IDS。细粒度权限，不包括角色的权限
 *
 * @return arr
 */
function admin_privilege_ids()
{
    return privilege_ids( array('admin_id'=>admin_id()) );
}

/**
 * 取得权限ID/权限名称(间接，以当前管理员的权限模块数据为基础)
 *
 * @params str  $module_file      模块的文件名
 * @params str  $module_act_code  模块的操作码
 *
 */
function admin_privilege_id_fk( $module_file, $module_act_code )
{
    return intval($GLOBALS['_PRIV']['privilege_map'][ $module_file.'_'.$module_act_code ]['id']);
}
function admin_privilege_name_fk( $module_file, $module_act_code )
{
    return $GLOBALS['_PRIV']['privilege_map'][ $module_file.'_'.$module_act_code ]['name'];
}

/**
 * 取得模块ID(间接，以当前管理员的权限模块数据为基础)
 *
 * @params str  $module_file  模块的文件名
 */
function admin_module_id_fk( $module_file )
{
    return intval($GLOBALS['_PRIV']['module_map'][$module_file]['id']);
}

/**
 * 取得模块操作名称(间接，以当前管理员的权限数据为基础)
 *
 * @params str  $module_file  模块的文件名
 */
function admin_module_act_name_fk( $module_file, $act )
{
    return $GLOBALS['_PRIV']['privilege_map'][ $module_file.'_'.$act ]['module_act_name'];
}

/**
 * 是否是有效的当前管理员的权限，支持或操作符“|”
 *
 * @params str  $module_file      模块的文件名
 * @params str  $module_act_code  模块的操作码
 * @params bol  $halt             是否中断程序
 *
 * @return bol  true表示合法权限，false表示非法权限
 */
function admin_privilege_valid( $module_file, $module_act_code, $halt = true )
{
    global $_LANG;

    /* 分解操作码 */
    $module_act_codes = explode('|', $module_act_code);

    foreach( $module_act_codes AS $i=>$v ){
        if( admin_privilege_id_fk($module_file, $v) > 0 ){
            return true;
        }
    }

    if( $halt === false ){
        return false;
    }else{
        sys_msg($_LANG['lawless_act']);
    }
}

/**
 * 取得当前管理员ID，姓名
 */
function admin_id()
{
    return intval($_SESSION[SN_ADMIN]['id']);
}
function admin_name()
{
    return $_SESSION[SN_ADMIN]['name'];
}

/**
 * 返回管理员的权限文件路径
 *
 * @params str  $username  管理员的登陆帐号, 默认取得当前管理员的权限文件路径
 */
function admin_pfile( $username = '' )
{
    /* 初始化 */
    global $_CFG;

    /* 返回权限文件路径 */
    if( $username == '' ){
        return $_CFG['DIR_ADMIN_PFILE'].$_SESSION[SN_ADMIN]['username'].'.php';
    }else{
        return $_CFG['DIR_ADMIN_PFILE'].trim($username).'.php';
    }
}
/**
 * 创建或者更新当前管理员权限文件
 *
 * @params arr  $privs  权限信息数组
 *
 * @return bol  true 表示写入成功，失败则退出系统
 */
function admin_pfile_create( $privs )
{
    global $_LANG;

    /* 权限信息字符串 */
    $str = serialize($privs);

    /* 权限文件路径 */
    $pfile = admin_pfile();

    /* 写入文件 */
    if( @file_put_contents($pfile, $str) === false ){
        /* 注销并返回系统消息 */
        admin_destroy(); make_json_fail($_LANG['fail_pfile_create']);
    }

    return true;
}
/**
 * 解析当前管理员权限文件内容
 *
 * @return arr
 */
function admin_pfile_parse()
{
    /* 权限文件路径 */
    $pfile = admin_pfile();

	/* 取得内容 */
    $str = @file_get_contents($pfile);

	return unserialize($str);
}
/**
 * 验证当前管理员的权限文件是否有效
 *
 * @return bol  true 表示文件存在且正确，false 表示文件不存在或者被非法修改
 */
function admin_pfile_valid()
{
    $pfile = admin_pfile();

    return is_file($pfile) && filemtime($pfile)==$_SESSION[SN_ADMIN]['pfiletime'];
}
/**
 * 初始化管理员的权限文件时间 - 权限文件时间全清零
 *
 * @params int  $admin_id  要初始化的管理员ID，0表示全部初始化
 */
function admin_pfile_init( $admin_id = 0 )
{
    if( intval($admin_id) > 0 ){
        $GLOBALS['db']->query('UPDATE '. tname('admin') .' SET pfile_time=0 WHERE admin_id='.intval($admin_id));
    }else{
        $GLOBALS['db']->query('UPDATE '. tname('admin') .' SET pfile_time=0');
    }
}


/* ------------------------------------------------------ */
// - 权限系统 - 辅助接口
/* ------------------------------------------------------ */

/**
 * 格式化模块操作集
 *
 * @params arr  $module_act  模块操作权限集的信息
 *                           $module_act[]['name']
 *                           $module_act[]['module_act_name']
 *                           $module_act[]['module_act_code']
 *
 * @params arr  $attribs     按钮的属性
 *                           $attribs[$module_act_code]['...']      - 按钮的HTML标签属性
 *                           $attribs[$module_act_code]['type']     - 按钮类型(继承按钮类型(默认))
 *                           $attribs[$module_act_code]['icon']     - 不对按钮类型为超链接起作用
 *                           $attribs[$module_act_code]['ddlwidth'] - 下拉列表宽度
 *
 * @params arr  $type        按钮类型(默认)
 *                           'a'    表示超链接
 *                           'btn'  表示一般按钮
 *                           'mdd'  表示移动下拉按钮
 *                           'cdd'  表示点击下拉按钮
 *                           'cddl' 表示点击下拉按钮(带分隔线)
 *
 * @params arr  items        下拉列表项
 *                           items[$module_act_code][]['icon']    = ''
 *                           items[$module_act_code][]['text']    = ''
 *                           items[$module_act_code][]['onclick'] = ''
 *
 * @return str  返回格式化成HTML的字符串
 */
function format_module_acts( $module_acts, $attribs = array(), $type = '', $items = array() )
{
    /* 初始化 */
    $html = '';
    $brks = array('type', 'attribs');
    $natt = array('icon', 'ddlwidth');

    /* 构建操作的HTML */
    foreach( $module_acts AS $r ){
        /* 按钮属性 */
        $attrib = $attribs[$r['module_act_code']];
        $attrib = empty($attrib) ? array() : $attrib;

        /* 按钮属性过滤处理 */
        $tpl['info'] = array();
        $tpl['info']['id'] = md5( implode(',',$r).implode(',',$attrib) );

        $tpl['info']['icon'] = $r['module_act_code'];
        $tpl['info']['text'] = $r['module_act_name'];

        $tpl['info']['attribs'] = array();
        $tpl['info']['attribs']['href'] = 'javascript:void(0)';

        foreach( $attrib AS $key => $val ){
            /* 过滤无效key */
            if( in_array($key,$brks) ) continue;

            /* 按标签属性过滤key */
            if( in_array($key,$natt) ){
                $tpl['info'][$key] = $val;
            }else{
                $tpl['info']['attribs'][$key] = $val;
            }
        }

        /* 下拉列表 */
        $tpl['items'] = $items[$r['module_act_code']];
        $tpl['items'] = empty($tpl['items']) ? array() : $tpl['items'];

        /* 初始化页面信息 */
        $tpl['_body'] = $attrib['type'] ? $attrib['type'] : $type;

        /* 取得HTML */
        $html .= tpl_fetch('titleact.html', $tpl);
    }

    return $html;
}

/**
 * 过滤管理员对模块的操作
 *
 * @params arr  $module_acts  模块的权限记录集
 * @params arr  $filter       模块操作码
 * @params bol  $reserve      是否是保留还是剔出, 对于 $filter 中的值
 *
 * @params arr  返回过滤后的模块操作数组
 */
function filter_module_acts( $module_acts, $filter = array(), $reserve = true )
{
    if( !is_array($module_acts) )  return array();
    if( !is_array($filter) )       return array();

    $acts = array();

    foreach( $module_acts AS $r ){
        if( $reserve ){
            if( in_array($r['module_act_code'], $filter) )
                $acts[] = $r;
        }else{
            if( !in_array($r['module_act_code'], $filter) )
                $acts[] = $r;
        }
    }

    return $acts;
}


/* ------------------------------------------------------ */
// - 权限系统 - 模块菜单树
/* ------------------------------------------------------ */

/**
 * 模块菜单树的HTML代码
 *
 * @params arr  $arr  模块数据(经过排序)
 */
function module_mtree( $data )
{
    /* 初始化数组内部指针 */
    reset($data);

    /* 递归构建模块菜单树并返回 */
    return module_mtree_r($data);
}
/**
 * 模块菜单树的HTML代码 - 递归构建
 *
 * @params arr  $arr  模块数据(经过排序)
 * @params str  $url  基础URL
 * @params int  $lvl  当前模块层级
 */
function module_mtree_r( &$arr, $url = 'modules/', $lvl = 1 )
{
    /* 如果当前节点的层级低，则返回 */
    if( !($curr=current($arr)) || $curr['lvl'] < $lvl ) return '';

    /* HTML开始 */
    if( $lvl == 1 ){
        $html = '<div class="module_mtree" onclick="module_mtree_click(event)">';
    }else{
        $prev = prev($arr); next($arr);
        $html = '<div id="module_mtree_id_'. $prev['module_id'] .'_" style="display:none">';
    }

    do{
        /* 取得当前数组元素和下一个数组元素，并下移数组指针 */
        $curr = current($arr);
        $next = next($arr);

        /* 初始化当前节点类型 */
        $type = $lvl==1 ? 'root' : ($curr['rht']-$curr['lft']>1?'parent':'leaf');

        /* 构建HTML */
        $html .= '<a onfocus="this.blur()" id="module_mtree_id_'. $curr['module_id'] .'" class="'. $type .'" ';
        $html .= 'href="javascript:void(0)" onclick="'. ($type == 'leaf' ? ("module_mtree_request('".$url.$curr['file']."')") : '');
        $html .= '" style="padding-left:'. ($lvl > 2 ? 20*($lvl-1) : 20) .'px';
        $html .= '"><i class="'. ($type == 'leaf' ? basename($curr['file'],'.php') : '');
        $html .= '"></i>'. $curr['name'] .'</a>';

        /* 如果下一个节点的层级高，则递归 */
        if( intval($next['lvl']) > $lvl ){
            /* 递归 */
            $html .= module_mtree_r($arr, $url.$curr['file'].'/', $lvl+1);

            /* 经过递归处理后的下一个未处理数组元素 */
            $curr = current($arr);

            /* 当前预留层级比较，如果低则返回 */
            if( !$curr || $curr['lvl'] < $lvl ) return $html.'</div>';
        }

        /* 如果下一个节点的层级低，则返回 */
        elseif( intval($next['lvl']) < $lvl ){
            return $html.'</div>';
        }
    }while(1);

    return $html.'</div>';
}


/* ------------------------------------------------------ */
// - 权限模块
/* ------------------------------------------------------ */

/**
 * 权限列表
 *
 * @params arr  $_REQUEST       全局搜索条件
 *                              SQL自定义所需字段 order_fd, order_type, page, rows_page
 * @return arr  $list
 *         arr  $list['data']   分页数据
 *         arr  $list['pager']  分页信息( html, rows_page, pages_group, rows_total, cur_page, row_start )
 */
function list_privilege()
{
    $p = $f = $list = array();

    /* 过滤条件初始化*/
    $f['module_ids'] = is_numeric($_REQUEST['module_id']) ? sub_module_id( array('module_id'=>$_REQUEST['module_id']) ) : null;

    /* 排序字段初始化 */
    $f['order_fd']   = 'p.module_id, p.`order`';
    $f['order_type'] = 'ASC';

    /* 构建总记录数SQL */
    $sql = 'SELECT count(*) FROM '. tname('privilege') .' AS p';

    /* 构建过滤条件SQL */
    $where  = ' WHERE 1=1';
    $where .= $f['module_ids'] === null ? '' : ' AND p.module_id IN("'. implode('","',$f['module_ids']) .'")';

    /* 设置分页信息 */
    $p['rows_page']  = intval($_REQUEST['rows_page']) ? intval($_REQUEST['rows_page']) : 16;
    $p['rows_total'] = $GLOBALS['db']->getOne($sql.$where);
    $p['html']       = pager($p['rows_page'], $p['rows_total']);
    $p['cur_page']   = pager_current($p['rows_page'], $p['rows_total']);
    $p['row_start']  = ($p['cur_page']-1) * $p['rows_page'];

    $f['page']       = $p['cur_page'];
    $f['rows_page']  = $p['rows_page'];

    /* 构建分页内容SQL */
    $sql = ' SELECT p.*, m.name AS module_name';
    $sql.= ' FROM '. tname('privilege') .' AS p LEFT JOIN '. tname('module') .' AS m USING(module_id)'. $where;
    $sql.= ' ORDER BY '. $f['order_fd'] .' '. $f['order_type'];
    $sql.= ' LIMIT '. $p['row_start'] .','. $p['rows_page'];

    /* 列表对象赋值 */
    $list['data']   = $GLOBALS['db']->getAll($sql);
    $list['pager']  = $p;
    $list['filter'] = $f;

    /* 返回 */
    return $list;
}

/**
 * 取得权限IDS
 *
 * @params arr  $filter  过滤数组
 *
 * @return arr
 */
function privilege_ids( $filter )
{
    /* 根据管理员ID取得权限IDS - 不包括管理员的角色权限 */
    if( is_numeric($filter['admin_id']) && intval($filter['admin_id']) > 0 ){
        /* ID为1的管理员拥有所有权限 */
        if( intval($filter['admin_id']) == 1 ){
            return $GLOBALS['db']->getCol( 'SELECT privilege_id FROM '.tname('privilege') );
        }

        /* 管理员的权限IDS */
        $sql = 'SELECT privilege_id FROM '. tname('admin_privilege') .' WHERE admin_id='. intval($filter['admin_id']);
        return $GLOBALS['db']->getCol($sql);
    }

    /* 根据模块ID取得权限IDS */
    if( is_numeric($filter['module_id']) && intval($filter['module_id']) > 0 ){
        $sql = 'SELECT privilege_id FROM '. tname('privilege') .' WHERE module_id='. intval($filter['module_id']) ;
        return $GLOBALS['db']->getCol($sql);
    }

    /* 根据模块IDS取得权限IDS */
    if( is_array($filter['module_ids']) && !empty($filter['module_ids']) ){
        $sql = 'SELECT privilege_id FROM '. tname('privilege') .' WHERE module_id IN("'. implode('","', $filter['module_ids']) .'")';
        return $GLOBALS['db']->getCol($sql);
    }

    return array();
}

/**
 * 权限信息
 *
 * @params arr  $filter  过滤参数
 */
function info_privilege( $filter )
{
    /* 根据权限ID取得权限信息 */
    if( is_numeric($filter['privilege_id']) && intval($filter['privilege_id']) > 0 ){
        $sql = 'SELECT * FROM '. tname('privilege') .' WHERE privilege_id='. intval($filter['privilege_id']);
        return $GLOBALS['db']->getRow($sql);
    }

    return array();
}

/**
 * 删除权限
 *
 * @params arr  $filter  过滤条件
 */
function del_privilege( $filter )
{
    global $_LANG;

    /* 根据权限ID删除 */
    if( is_numeric($filter['privilege_id']) && intval($filter['privilege_id']) > 0 ){
        /* 删除管理员权限和角色权限 */
        del_admin_privilege( array('privilege_id'=>$filter['privilege_id']) );
        del_role_privilege( array('privilege_id'=>$filter['privilege_id']) );

        /* 删除权限 */
        $GLOBALS['db']->delete( tname('privilege'), 'privilege_id='.intval($filter['privilege_id']) );

        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 根据模块ID删除 */
    if( is_numeric($filter['module_id']) && intval($filter['module_id']) > 0 ){
        /* 取得模块的所有权限 */
        $priv_ids = privilege_ids( array('module_id'=>$filter['module_id']) );

        /* 删除管理员权限和角色权限 */
        del_admin_privilege( array('privilege_ids'=>$priv_ids) );
        del_role_privilege( array('privilege_ids'=>$priv_ids) );

        /* 删除权限 */
        $GLOBALS['db']->delete( tname('privilege'), 'module_id='. intval($filter['module_id']) );

        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    return array('error'=>1, 'message'=>$_LANG['fail_del']);
}

/**
 * 权限重复
 *
 * @params arr  $filter  过滤条件
 */
function exist_privilege( $filter )
{
    if( is_numeric($filter['module_id']) && intval($filter['module_id']) > 0 && !empty($filter['module_act_code']) ){
        $sql = ' SELECT count(privilege_id) FROM '. tname('privilege');
        $sql.= ' WHERE module_id='      . intval($filter['module_id']);
        $sql.= ' AND module_act_code="' . trim($filter['module_act_code']) .'"';
        $sql.= ' AND privilege_id <> '  . intval($filter['privilege_id']); //排除指定ID记录的重复检测

        return $GLOBALS['db']->getOne($sql);
    }

    return true;
}

/**
 * 比较细粒度权限
 *
 * @params arr  $filter1  条件1
 * @params arr  $filter2  条件2
 *
 * @return mix  '>' 表示$filter1大.  '=' 表示一样大.  '<' 表示$filter2大.  false 表示无法比较
 */
function cmp_privilege( $filter1, $filter2 )
{
    /* 无效数据 */
    if( !is_array($filter1['privilege_ids']) || !is_array($filter2['privilege_ids']) ){
        return false;
    }

    /* 空数据时比较 */
    if( empty($filter1['privilege_ids']) ){
        return empty($filter2['privilege_ids']) ? '=' : '<';
    }
    elseif( empty($filter2['privilege_ids']) ){
        return '>';
    }

    /* 权限比较 */
    $diff = array_diff($filter2['privilege_ids'], $filter1['privilege_ids']);

    if( empty($diff) ){
        return count($filter2['privilege_ids']) == count($filter1['privilege_ids']) ? '=' : '>';
    }else{
        $diff = array_diff($filter1['privilege_ids'], $filter2['privilege_ids']);

        if( empty($diff) ){
            return '<';
        }
    }

    return false;
}

/**
 * 权限表
 *
 * @params str  $name       复选框的名字
 * @params arr  $seled_ids  被选中的权限IDS
 * @params arr  $priv_ids   要显示的权限IDS，当为false表示显示全部
 *
 * @return str
 */
function html_privilege_table( $name, $seled_ids = array() , $priv_ids = false )
{
    global $_LANG;

    /* 要显示的权限IDS为空时 */
    if( is_array($priv_ids) && empty($priv_ids) ) return '';

    /* 初始化参数 */
    if( !is_array($seled_ids) ) $seled_ids = array();
    if( !is_array($priv_ids)  ) $priv_ids  = false;

    /* 构建权限SQL */
    $sql = 'SELECT * FROM '. tname('privilege'); //SELECT * FROM
    $sql.= $priv_ids === false ? '' : ' WHERE privilege_id IN("'. implode('","',$priv_ids) .'")'; //WHERE, 限制要显示的权限

    $privs = array();
    $array = $GLOBALS['db']->getAll($sql);

    /* 重构权限数组 */
    foreach( $array AS $r ){
        $privs[$r['module_id']][] = $r;
        $m_ids[$r['module_id']]   = $r['module_id']; //记录权限所在的模块
    }

    /* 所有模块 */
    $modules = all_module();

    /* 过滤模块 */
    for( $i = count($modules); $i >= 0; $i-- ){
        /* 保留预设节点 */
        if( in_array($modules[$i]['module_id'],$m_ids) ) continue;

        /* 保留预设节点的父节点 */
        if( $modules[$i+1]['lvl'] > $modules[$i]['lvl'] ) continue;

        array_splice($modules, $i, 1);
    }

    $html = '<table>';

    foreach( $modules AS $i => $module ){
        /* 模块无权限 - 说明该模块为模块组 */
        if( empty($privs[$module['module_id']]) ){
            $html .= '<tr style="background-color:#f9f9f9;"><td width="100" style="padding:4px;padding-left:6px;">';
            $html .= '<b>'. $module['name'] .'</b></td><td></td></tr>';

            continue;
        }

        $cb = $cbg  = '';
        $cb_checked = true;

        foreach( $privs[$module['module_id']] AS $r ){
             /* 初始化权限表HTML的权限ID */
            $pid  = 'ptbl_privilege_'. $r['privilege_id'] .'_'. time();

            $cbg .= '<input class="checkbox" type="checkbox"';
            $cbg .= 'name="'. $name .'[]" value="'. $r['privilege_id'] .'" id="'. $pid  .'" ';
            $cbg .= (in_array($r['privilege_id'], $seled_ids) ? 'checked="checked"' : '') .' ';
            $cbg .= 'onclick="Formc.cbgSyncCbg(Formc.cbgByContainer(this.parentNode.previousSibling),Formc.cbgByContainer(this.parentNode))">';
            $cbg .= '<label for="'. $pid .'">'. $r['module_act_name'] .'</label>';

            $cb_checked &= in_array($r['privilege_id'], $seled_ids);
        }

        /* 初始化权限表HTML的模块ID */
        $mid = 'ptbl_module_'. $module['module_id'] .'_'. time();

        $cb .= '<tr><td>';
        $cb .= '<input class="checkbox" type="checkbox" onclick="Formc.cbSyncCbg(this,Formc.cbgByContainer(this.parentNode.nextSibling))" ';
        $cb .= 'id="'. $mid .'"';
        $cb .= $cb_checked ? 'checked="checked"' : '';
        $cb .= '><label for="'. $mid .'" style="color:#005363">'. $module['name'] .'</label></td>';
        $cb .= '<td align="left">';

        $html.= $cb.$cbg.'</td></tr>';
    }

    $html.= '</table>';

    return $html;
}
?>