<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 管理员函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 管理员
/* ------------------------------------------------------ */

/**
 * 管理员列表
 *
 * @params arr  $_REQUEST        全局搜索条件
 *                               SQL自定义所需字段 order_fd, order_type, page, rows_page
 * @return arr  $list
 *         arr  $list['data']    分页数据
 *         arr  $list['pager']   分页信息( html, rows_page, pages_group, rows_total, cur_page, row_start )
 *         arr  $list['filter']  列表过录信息
 */
function list_admin( $filter = array() )
{
    $p = $f = $list = array();

    /* 过滤条件初始化*/
    $f['name']     = trim($_REQUEST['name']) != ''     ? trim($_REQUEST['name'])     : null; //管理员姓名
    $f['username'] = trim($_REQUEST['username']) != '' ? trim($_REQUEST['username']) : null; //管理员帐号

    /* 过滤条件初始化(高优先) */
    $f['role_lft'] = isset($filter['role_info']) ? intval($filter['role_info']['lft']) : null;
    $f['role_rht'] = isset($filter['role_info']) ? intval($filter['role_info']['rht']) : null;

    /* 排序字段初始化 */
    $fields = array('name', 'username', 'rht', 'in_time');
    $f['order_fd']   = in_array($_REQUEST['order_fd'], $fields) ? $_REQUEST['order_fd'] : 'admin_id';
    $f['order_type'] = $_REQUEST['order_type'] == 'ASC' ? 'ASC' : 'DESC';

    /* 构建总记录数SQL */
    $sql = 'SELECT count(admin_id) FROM '. tname('admin') .' LEFT JOIN '. tname('role') .' USING(role_id)';

    /* 构建过滤条件SQL */
    $where  = ' WHERE 1=1';
    $where .= $f['name']     === null ? '' : ' AND '. tname('admin') .'.name LIKE "%'. mysql_like_slash($f['name']) .'%"';
    $where .= $f['username'] === null ? '' : ' AND '. tname('admin') .'.username LIKE "%'. mysql_like_slash($f['username']) .'%"';

    $where .= admin_id() == 1 ? ' AND ('. tname('admin') .'.role_id = 0' : ' AND (1<>1';
    $where .= $f['role_lft'] === null ? '' : ' OR ('. tname('role') .'.lft>'. $f['role_lft'];
    $where .= $f['role_rht'] === null ? '' : ' AND '. tname('role') .'.rht<'. $f['role_rht'] .'))'; 

    /* 设置分页信息 */
    $p['rows_page']  = intval($_REQUEST['rows_page']) ? intval($_REQUEST['rows_page']) : 16;
    $p['rows_total'] = $GLOBALS['db']->getOne($sql.$where);
    $p['html']       = pager($p['rows_page'], $p['rows_total']);
    $p['cur_page']   = cur_page($p['rows_page'], $p['rows_total']);
    $p['row_start']  = ($p['cur_page']-1) * $p['rows_page'];

    $f['page']       = $p['cur_page'];
    $f['rows_page']  = $p['rows_page'];

    /* 构建分页内容SQL */
    $sql = ' SELECT '. tname('admin') .'.*, '. tname('role') .'.name AS role_name';
    $sql.= ' FROM '. tname('admin') .' LEFT JOIN '. tname('role') .' USING(role_id) '. $where;
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
 * 取得所有管理员
 *
 * @params arr  $filter  过滤条件
 */
function all_admin( $filter = array() )
{
    /* 根据角色ID获得所有管理员 */
    if( is_numeric($filter['role_id']) && intval($filter['role_id']) >= 0 ){
        $sql = 'SELECT * FROM '. tname('admin') .' WHERE role_id='. intval($fitler['role_id']);
        return $GLOBALS['db']->getAll($sql);
    }

    /* 所有管理员 */
    if( is_array($filter) && empty($filter) ){
        $sql = 'SELECT * FROM '. tname('admin');
        return $GLOBALS['db']->getAll($sql);
    }

    return array();
}

/**
 * 根据角色大小取得子管理员(连表角色）
 *
 * @params arr  $filter  过滤条件
 * @params bol  $self    是否包括自己
 */
function sub_admin( $filter, $self )
{
    /* 根据角色信息 role_info(信任) 取得子管理员 */
    if( is_array($filter['role_info']) && !empty($filter['role_info']) ){
        $sql = ' SELECT a.*, r.name AS role_name, r.lft, r.rht, r.lvl';
        $sql.= ' FROM '. tname('admin') .' AS a INNER JOIN '. tname('role') .' AS r USING(role_id)';
        $sql.= ' WHERE r.lft'. ($self?'>=':'>') .intval($filter['role_info']['lft']);
        $sql.= ' AND r.rht'. ($self?'<=':'<') .intval($filter['role_info']['rht']);
        $sql.= ' ORDER BY r.lft ASC';

        return $GLOBALS['db']->getAll($sql);
    }

    return array();
}

/**
 * 取得管理员信息(连表角色)
 *
 * @params arr  $filter  过滤条件
 */
function info_admin( $filter )
{
    /* 根据管理员ID取得管理员信息 */
    if( is_numeric($filter['admin_id']) && intval($filter['admin_id']) > 0 ){
        $sql = ' SELECT a.*, r.name AS role_name, r.lft, r.rht, r.lvl';
        $sql.= ' FROM '. tname('admin') .' AS a LEFT JOIN '. tname('role') .' AS r USING(role_id)';
        $sql.= ' WHERE a.admin_id='. intval($filter['admin_id']);

        return $GLOBALS['db']->getRow($sql);
    }

    return array();
}

/**
 * 删除管理员
 *
 * @params arr  $filter  过滤条件
 */
function del_admin( $filter )
{
    global $_LANG;

    /* 根据管理员ID删除 */
    if( is_numeric($filter['admin_id']) && intval($filter['admin_id']) > 0 ){
        /* 初始化 */
        $admin_id = intval($filter['admin_id']);

        /* 系统帐号，保留 */
        if( $admin_id == 1 ){
            return array('error'=>1, 'message'=>$_LANG['lawless_submit']);
        }

        /* 删除管理员权限 */
        del_admin_privilege( array('admin_id'=>$admin_id) );

        /* 重置管理员日志中的管理员ID */
        $GLOBALS['db']->update( tname('admin_log'), array('admin_id'=>'0'), 'admin_id='.$admin_id );

        /* 删除管理员 */
        $GLOBALS['db']->delete( tname('admin'), 'admin_id='.$admin_id );

        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    return array('error'=>1, 'message'=>$_LANG['fail_del']);
}

/**
 * 管理员重复
 *
 * @params arr  $filter  过滤条件
 */
function exist_admin( $filter )
{
    if( trim($filter['username']) != '' ){
        $sql = ' SELECT count(admin_id) FROM '. tname('admin');
        $sql.= ' WHERE username="'. trim($filter['username']) .'"';
        $sql.= ' AND admin_id <> '. intval($filter['admin_id']); //排除指定ID记录的重复检测

        return $GLOBALS['db']->getOne($sql);
    }

    return true;
}


/* ------------------------------------------------------ */
// - 管理员权限(细粒度)
/* ------------------------------------------------------ */

/**
 * 删除管理员权限
 *
 * @params arr  $filter  过滤条件
 */
function del_admin_privilege( $filter )
{
    global $_LANG;

    /* 根据权限ID删除权限 */
    if( is_numeric($filter['privilege_id']) && intval($filter['privilege_id']) > 0 ){
        $GLOBALS['db']->delete( tname('admin_privilege'), 'privilege_id='.intval($filter['privilege_id']) );
        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 根据管理员ID删除指定的权限IDS */
    if( isset($filter['admin_id']) && isset($filter['privilege_ids']) ){
        if( !(is_numeric($filter['admin_id']) || intval($filter['admin_id']) <= 0) ){
            return array('error'=>1, 'message'=>$_LANG['fail_del']);
        }

        if( !is_array($filter['privilege_ids']) ){
            return array('error'=>1, 'message'=>$_LANG['fail_del']);
        }elseif( empty($filter['privilege_ids']) ){
            return array('error'=>0, 'message'=>$_LANG['del_ok']);
        }

        $where = ' admin_id='. intval($filter['admin_id']);
        $where.= ' AND privilege_id IN("'. implode('","', $filter['privilege_ids']) .'")';

        $GLOBALS['db']->delete( tname('admin_privilege'), $where );

        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 根据管理员ID删除权限 */
    if( is_numeric($filter['admin_id']) && intval($filter['admin_id']) > 0 ){
        $GLOBALS['db']->delete( tname('admin_privilege'), 'admin_id='.intval($filter['admin_id']) );
        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 根据权限IDS删除权限 */
    if( is_array($filter['privilege_ids']) && !empty($filter['privilege_ids']) ){
        $GLOBALS['db']->delete( tname('admin_privilege'), 'privilege_id IN("'. implode('","', $filter['privilege_ids']) .'")' );
        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    return array('error'=>1, 'message'=>$_LANG['fail_del']);
}


/* ------------------------------------------------------ */
// - 管理员日志
/* ------------------------------------------------------ */

/**
 * 日志列表
 *
 * @params arr  $_REQUEST       全局搜索条件
 *                              SQL自定义所需字段 order_fd, order_type, rows_page
 * @return arr  $list
 *         arr  $list['data']   分页数据
 *         arr  $list['pager']  分页信息( html, rows_page, pages_group, rows_total, cur_page, row_start )
 */
function list_admin_log()
{
    $p = $f = $list = array();

    /* 过滤条件初始化*/
    $f['info']  = trim($_REQUEST['info']) != '' ? trim($_REQUEST['info'])  : null;
    $f['datef'] = strtotime($_REQUEST['datef']) ? trim($_REQUEST['datef']) : null;
    $f['datet'] = strtotime($_REQUEST['datet']) ? trim($_REQUEST['datet']) : null;

    /* 排序字段初始化 */
    $fields = array();
    $f['order_fd']   = in_array( $_REQUEST['order_fd'], $fields ) ? $_REQUEST['order_fd'] : 'admin_log_id';
    $f['order_type'] = $_REQUEST['order_type'] == 'ASC' ? 'ASC' : 'DESC';

    /* 构建总记录数SQL */
    $sql = ' SELECT count(*) FROM '. tname('admin_log');

    /* 构建过滤条件SQL */
    $where  = ' WHERE 1=1';
    $where .= $f['info']  === null ? '' : ' AND info LIKE "%'. mysql_like_slash($f['info']) .'%"';
    $where .= $f['datef'] === null ? '' : ' AND in_time>='. (strtotime($f['datef'])/100000*100000);
    $where .= $f['datet'] === null ? '' : ' AND in_time<='. (strtotime($f['datet'])/100000*100000+86399);

    /* 设置分页信息 */
    $p['rows_page']  = intval($_REQUEST['rows_page']) ? intval($_REQUEST['rows_page']) : 16;
    $p['rows_total'] = $GLOBALS['db']->getOne($sql.$where);
    $p['html']       = pager($p['rows_page'], $p['rows_total']);
    $p['cur_page']   = cur_page($p['rows_page'], $p['rows_total']);
    $p['row_start']  = ($p['cur_page']-1) * $p['rows_page'];

    $f['page']       = $p['cur_page'];
    $f['rows_page']  = $p['rows_page'];

    /* 构建分页内容SQL */
    $sql = ' SELECT * FROM '. tname('admin_log') .' '.$where;
    $sql.= ' ORDER BY '. $f['order_fd'] .' '. $f['order_type'];
    $sql.= ' LIMIT '. $p['row_start'] .','. $p['rows_page'];

    /* 列表对象赋值 */
    $list['data']   = $GLOBALS['db']->getAll($sql);
    $list['pager']  = $p;
    $list['filter'] = $f;

    /* 返回 */
    return $list;
}


/* ------------------------------------------------------ */
// - 管理员相关 HTML控件
/* ------------------------------------------------------ */

/**
 * 下拉列表 - 所有管理员列表
 * 
 * @params str  $name      列表名称
 * @params mix  $selected  选中的值
 * @params arr  $appends   追加到顶部的下拉项
 * @params arr  $attribs   下拉属性
 */
function ddl_all_admin( $name, $selected = '', $appends = array(), $attribs = array() )
{
    /* 初始化 */
    $items = array();

    /* 所有管理员 */
    $all_admin = all_admin();

    /* 下拉列表顶部项 */
    if( is_array($appends) ){
        if( isset($appends['value']) && isset($appends['text']) ){
            $appends = array( array('value'=>$appends['value'],'text'=>$appends['text']) );
        }

        foreach( $appends AS $i=>$item ){
            if( isset($item['value']) && isset($item['text']) ){
                $items[] = $item;
            }
        }
    }

    /* 下拉列表项 */
    foreach( $all_admin AS $r ){
        $items[] = array( 'value'=>$r['admin_id'], 'text'=>f($r['name'],'html') );
    }

    $fc = new FormControl();
    return $fc->ddl( $name, $items, array_merge(array('selected'=>$selected),$attribs) );
}
?>