<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 角色函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 角色
/* ------------------------------------------------------ */

/**
 * 取得子角色
 *
 * @params arr  $filter  过滤条件
 * @params bol  $self    是否包括自身
 */
function sub_role( $filter, $self = true )
{
    /* 根据角色信息(信任)取得子角色 */
    if( is_array($filter['info']) && !empty($filter['info']) ){
        $sql = ' SELECT * FROM '. tname('role') .' WHERE lft'. ($self?'>=':'>') . intval($filter['info']['lft']);
        $sql.= ' AND rht'. ($self?'<=':'<') . intval($filter['info']['rht']) .' ORDER BY lft ASC';

        return $GLOBALS['db']->getAll($sql);
    }

    return array();
}
/**
 * 取得子角色的IDS
 */
function sub_role_id( $filter, $self = true )
{
    /* 根据角色信息 info(信任) 取得子角色 */
    if( is_array($filter['info']) && !empty($filter['info']) ){
        $sql = ' SELECT role_id FROM '. tname('role') .' WHERE lft'. ($self?'>=':'>') . intval($filter['info']['lft']);
        $sql.= ' AND rht'. ($self?'<=':'<') . intval($filter['info']['rht']) .' ORDER BY lft ASC';

        return $GLOBALS['db']->getCol( $sql );
    }

    return array();
}

/**
 * 取得父节点信息
 *
 * @params arr  $filter  过滤条件
 */
function info_parent_role( $filter )
{
    /* 根据角色信息(信任)取得父节点信息 */
    if( is_array($filter['info']) && !empty($filter['info']) ){
        $sql = ' SELECT * FROM '. tname('role') .' WHERE lft<'. intval($filter['info']['lft']);
        $sql.= ' AND rht>'. intval($filter['info']['rht']);
        $sql.= ' ORDER BY lft DESC limit 1';

        return $GLOBALS['db']->getRow($sql);
    }

    return array();
}

/**
 * 取得角色信息
 *
 * @params arr  $filter  过滤条件
 */
function info_role( $filter )
{
    /* 根据角色ID获得角色信息 */
    if( is_numeric($filter['role_id']) && intval($filter['role_id']) > 0 ){
        $sql = 'SELECT * FROM '. tname('role') .' WHERE role_id='. intval($filter['role_id']);
        return $GLOBALS['db']->getRow($sql);
    }

    return array();
}

/**
 * 比较角色的大小
 *
 * @params arr  $filter1  条件1
 * @params arr  $filter2  条件2
 *
 * @return mix  '>' 表示$filter1大.  '=' 表示一样大.  '<' 表示$filter2大.  false 表示无法比较
 */
function cmp_role( $filter1, $filter2 )
{
    $cmp1 = $cmp2 = array();

    /* 无效数据 */
    if( !is_array($filter1['info']) || !is_array($filter2['info']) ){
        return false;
    }
    if( empty($filter1['info']) || empty($filter2['info']) ){
        return false;
    }

    /* 角色比较 */
    $cmp1['lft'] = intval($filter1['info']['lft']);
    $cmp1['rht'] = intval($filter1['info']['rht']);
    $cmp2['lft'] = intval($filter2['info']['lft']);
    $cmp2['rht'] = intval($filter2['info']['rht']);

    if( $cmp1['lft']  <  $cmp2['lft'] && $cmp1['rht']  >  $cmp2['rht'] ) return '>';
    if( $cmp1['lft'] ==  $cmp2['lft'] && $cmp1['rht'] ==  $cmp2['rht'] ) return '=';
    if( $cmp1['lft']  >  $cmp2['lft'] && $cmp1['rht']  <  $cmp2['rht'] ) return '<';

    /* 无法比较 */
    return false;
}

/**
 * 比较角色权限的大小
 *
 * @params arr  $filter1 条件1
 * @params arr  $filter2 条件2
 *
 * @return mix  '>' 表示$filter1大.  '=' 表示一样大.  '<' 表示$filter2大.  false 表示无法比较
 */
function cmp_role_privilege( $filter1, $filter2 )
{
    $cmp1 = array();

    /* 提取条件数据 */
    if( is_numeric($filter1['role_id']) && intval($filter1['role_id']) > 0 ){
        $filter1['privilege_ids'] = all_role_privilege_id( array('role_id'=>$filter1['role_id']) );
    }

    return cmp_privilege($filter1, $filter2);
}


/**
 * 删除角色
 *
 * @params arr  $filter  过滤条件
 */
function del_role( $filter = array() )
{
    global $_LANG;

    /* 根据角色信息(信任)删除 */
    if( is_array($filter['info']) && !empty($filter['info']) ){
        /* 系统内置角色，保留 */
        if( intval($filter['info']['role_id']) == 1 ){
            return array('error'=>1, 'message'=>$_LANG['lawless_submit']);
        }

        /* 子角色IDS(包括自身) */
        $ids = sub_role_id( array('info'=>$filter['info']), true );

        /* 删除角色权限 */
        del_role_privilege( array('role_ids'=>$ids) );

        /* 重置管理员中的角色ID */
        $sql = 'UPDATE '. tname('admin') .' SET role_id=0 WHERE role_id IN("'. implode('","',$ids) .'")';
        $GLOBALS['db']->query($sql);

        /* 删除角色 */
        lrtree_del( array('table'=>tname('role'), 'info'=>$filter['info']) );

        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    return array('error'=>1, 'message'=>$_LANG['fail_del']);
}


/* ------------------------------------------------------ */
// - 角色权限
/* ------------------------------------------------------ */

/**
 * 取得所有角色权限IDS
 *
 * @params arr  $filter  过滤条件
 */
function all_role_privilege_id( $filter )
{
    /* 根据角色ID取得角色权限IDS */
    if( is_numeric($filter['role_id']) && intval($filter['role_id']) > 0 ){
        if( intval($filter['role_id']) == 1 ){
            $sql = 'SELECT privilege_id FROM '. tname('privilege');
        }else{
            $sql = 'SELECT privilege_id FROM '. tname('role_privilege') .' WHERE role_id=' . intval($filter['role_id']);
        }

        return $GLOBALS['db']->getCol($sql);
    }

    return array();
}

/**
 * 删除角色权限
 *
 * @params arr  $filter  过滤条件
 */
function del_role_privilege( $filter = array() )
{
    global $_LANG;

    /* 根据角色ID删除角色权限 */
    if( is_numeric($filter['role_id']) && intval($filter['role_id']) > 0 ){
        $GLOBALS['db']->delete( tname('role_privilege'), 'role_id='.intval($filter['role_id']) );
        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 根据权限ID删除角色权限 */
    if( is_numeric($filter['privilege_id']) && intval($filter['privilege_id']) > 0 ){
        $GLOBALS['db']->delete( tname('role_privilege'), 'privilege_id='.intval($filter['privilege_id']) );
        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 通过角色IDS删除指定权限IDS */
    if( isset($filter['role_ids']) && isset($filter['privilege_ids']) ){
        if( !is_array($filter['role_ids']) || !is_array($filter['privilege_ids']) ){
            return array('error'=>1, 'message'=>$_LANG['fail_del']);
        }elseif( empty($filter['role_ids']) || empty($filter['privilege_ids']) ){
            return array('error'=>0, 'message'=>$_LANG['del_ok']);
        }

        $where = ' role_id IN("'. implode('","',$filter['role_ids']) .'")';
        $where.= ' AND privilege_id IN("'. implode('","',$filter['privilege_ids']) .'")';

        $GLOBALS['db']->delete( tname('role_privilege'), $where );

        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 通过角色ID组删除角色权限 */
    if( is_array($filter['role_ids']) && !empty($filter['role_ids']) ){
        $GLOBALS['db']->delete( tname('role_privilege'), 'role_id IN("'. implode('","',$filter['role_ids']) .'")' );
        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    /* 通过权限ID组删除角色权限 */
    if( is_array($filter['privilege_ids']) && !empty($filter['privilege_ids']) ){
        $GLOBALS['db']->delete( tname('role_privilege'), 'privilege_id IN("'. implode('","',$filter['privilege_ids']) .'")' );
        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    return array('error'=>1,'message'=>$_LANG['fail_del']);
}


/* ------------------------------------------------------ */
// - 角色 HTML控件
/* ------------------------------------------------------ */

/**
 * 下拉列表 - 自定义的角色
 *
 * @params arr  $roles     角色数据
 * @params str  $name      下拉列表名称
 * @params mix  $selected  下拉列表选中项
 * @params arr  $appends   下拉列表追加项
 * @params arr  $attribs   下拉列表属性
 */
function ddl_role_custom( $roles, $name, $selected = '', $appends = array(), $attribs = array() )
{
    /* 初始化 */
    $items = array();

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
    foreach( $roles AS $r ){
        $text = f( str_repeat(' ',($r['lvl']-$roles[0]['lvl'])*4).$r['name'], 'html' );
        $items[] = array('value'=>$r['role_id'], 'text'=>$text);
    }

    $fc = new FormControl();
    return $fc->ddl( $name, $items, array_merge(array('selected'=>$selected),$attribs) );
}
?>