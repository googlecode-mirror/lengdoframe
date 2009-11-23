<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 模块函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 模块
/* ------------------------------------------------------ */

/**
 * 取得所有模块
 *
 * @params arr  $filter  过滤条件
 */
function all_module( $filter = array() )
{
    /* 所有模块 */
    if( empty($filter) ){
        $sql = 'SELECT * FROM '. tname('module') .' WHERE module_id <> 1 ORDER BY lft ASC';
        return $GLOBALS['db']->getAll($sql);
    }

    return array();
}

/**
 * 取得子模块的IDS
 *
 * @params arr  $filter  过滤条件
 * @params bol  $self    是否包括自身
 */
function sub_module_id( $filter, $self = true )
{
    /* 根据模块ID取得模块IDS */
    if( is_numeric($filter['module_id']) && intval($filter['module_id']) > 0 ){
        $filter['info'] = info_module( array('module_id'=>$filter['module_id']) );
    }

    /* 根据模块信息(信任)取得模块IDS */
    if( is_array($filter['info']) && !empty($filter['info']) ){
        $sql = ' SELECT module_id FROM '. tname('module') .' WHERE lft'. ($self?'>=':'>') . intval($filter['info']['lft']);
        $sql.= ' AND rht'. ($self?'<=':'<') . intval($filter['info']['rht']) .' ORDER BY lft ASC';

        return $GLOBALS['db']->getCol($sql);
    }

    return array();
}

/**
 * 取得模块信息
 *
 * @params arr  $filter  过滤条件
 */
function info_module( $filter )
{
    /* 根据模块ID取得模块信息 */
    if( is_numeric($filter['module_id']) && intval($filter['module_id']) > 0 ){
        $sql = 'SELECT * FROM '. tname('module') .' WHERE module_id='. intval($filter['module_id']);
        return $GLOBALS['db']->getRow($sql);
    }

    return array();
}


/**
 * 删除模块，只能删除叶子模块
 *
 * @params arr  $filter  过滤条件
 */
function del_module( $filter )
{
    global $_LANG;

    /* 根据模块信息(信任)删除 */
    if( is_array($filter['info']) && !empty($filter['info']) ){
        /* 虚根模块，保留 */
        if( $filter['info']['module_id'] == 1 ){
            return array('error'=>1, 'message'=>$_LANG['lawless_submit']);
        }

        /* 非叶子节点不能删除 */
        if( $filter['info']['lft'] != $filter['info']['rht']-1 ){
            return array('error'=>1, 'message'=>$_LANG['lawless_submit']);
        }

        /* 删除模块拥有的权限 */
        del_privilege( array('module_id'=>$filter['info']['module_id']) );

        /* 删除模块 */
        lrtree_del( array('table'=>tname('module'),'info'=>$filter['info']) );

        return array('error'=>0, 'message'=>$_LANG['del_ok']);
    }

    return array('error'=>1, 'message'=>$_LANG['fail_del']);
}


/**
 * 模块重复
 *
 * @params arr  $filter  过滤条件
 */
function exist_module( $filter )
{
    if( !empty($filter['file']) ){
        $sql = ' SELECT count(*) FROM '. tname('module');
        $sql.= ' WHERE file="'. trim($filter['file'] .'"');
        $sql.= ' AND module_id <> '. intval($filter['module_id']); //排除指定ID记录的重复检测

        return $GLOBALS['db']->getOne($sql);
    }

    return true;
}


/* ------------------------------------------------------ */
// - 模块 HTML控件
/* ------------------------------------------------------ */

/**
 * 下拉列表 - 模块
 *
 * @params str  $name      下拉列表名称
 * @params mix  $selected  下拉列表选中项
 * @params arr  $appends   下拉列表追加项
 * @params arr  $attribs   下拉列表属性
 */
function ddl_module( $name, $selected = '', $appends = array(), $attribs = array() )
{
    global $_LANG;

    /* 初始化 */
    $items = array();

    /* 所有模块 */
    $modules = all_module();

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
    foreach( $modules AS $r ){
        $text = f( str_repeat(' ',($r['lvl']-1)*4).$r['name'], 'html' );
        $items[] = array('value'=>$r['module_id'], 'text'=>$text);
    }

    $fc = new FormControl();
    return $fc->ddl( $name, $items, array_merge(array('selected'=>$selected),$attribs) );
}
?>