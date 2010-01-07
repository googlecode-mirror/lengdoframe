<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 改进的前序遍历树函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/**
 * 改进的前序遍历树写入
 * 
 * @params arr  $fields  要写入的数据
 * @params arr  $filter  过滤条件
 *         str           $filter['table']       前序遍历树的数据表
 *         str           $filter['where']       附加的过滤信息
 *         str           $filter['primary']     主键的字段名
 *         int           $filter['parent_id']   父节点ID
 *         arr           $filter['parent_info'] 父节点信息
 *
 * @return bol  true表示写入成功，false表示写入失败
 */
function lrtree_insert( $fields, $filter )
{
    /* 父节点信息 */
    if( is_array($filter['parent_info']) && !empty($filter['parent_info']) ){
        $info_p = $filter['parent_info'];
    }else{
        $sql = 'SELECT * FROM '. $filter['table'] .' WHERE '. $filter['primary'] .'='. $filter['parent_id'];
        $info_p = $GLOBALS['db']->getRow($sql);
    }

    /* 扩容1位 - 1.调整父节点 */
    $sql = 'UPDATE '. $filter['table'] .' SET rht=rht+2 WHERE lft<='. $info_p['lft'] .' AND rht>='. $info_p['rht'];
    $sql.= $filter['where'] ? (' AND '.$filter['where']) : '';

    $GLOBALS['db']->query($sql);

    /* 扩容1位 - 2.调整子节点 */
    $sql = 'UPDATE '. $filter['table'] .' SET lft=lft+2,rht=rht+2 WHERE lft>'. $info_p['lft'];
    $sql.= $filter['where'] ? (' AND '.$filter['where']) : '';

    $GLOBALS['db']->query($sql);

    /* 设置字段值 */
    $fields['lft'] = $info_p['lft'] + 1;
    $fields['rht'] = $info_p['lft'] + 2;
    $fields['lvl'] = $info_p['lvl'] + 1;

    /* 写入数据库 */
    return $GLOBALS['db']->insert($filter['table'], $fields);
}

/**
 * 改进的前序遍历树删除
 * 
 * @params arr  $filter  过滤条件
 *         arr           $filter['info']   信任的树信息
 *         str           $filter['table']  前序遍历树的数据表
 *         str           $filter['where']  附加的过滤信息
 *
 * @return bol  true表示删除成功，false表示删除失败
 */
function lrtree_del( $filter )
{
    /* 删除节点 */
    $where = 'lft>='. $filter['info']['lft'] .' AND rht<='. $filter['info']['rht'];
    $where.= $filter['where'] ? (' AND '.$filter['where']) : '';

    $GLOBALS['db']->delete($filter['table'], $where);


    /* 调整其他节点的lft 和 rht */

    /* 减小的值 */
    $decr = $filter['info']['rht']+1 - $filter['info']['lft'];

    /* 1.调整父节点 */
    $sql = ' UPDATE '. $filter['table'] .' SET rht=rht-'. $decr;
    $sql.= ' WHERE lft<='. $filter['info']['lft'];
    $sql.= ' AND rht>='. $filter['info']['rht'];
    $sql.= $filter['where'] ? (' AND '.$filter['where']) : '';

    $GLOBALS['db']->query($sql);

    /* 2.调整子节点 */
    $sql = ' UPDATE '. $filter['table'] .' SET lft=lft-'. $decr .',rht=rht-'. $decr;
    $sql.= ' WHERE lft>'. $filter['info']['lft'];
    $sql.= $filter['where'] ? (' AND '.$filter['where']) : '';

    $GLOBALS['db']->query($sql);

    return true;
}

/**
 * 改进的前序遍历树上移
 * 
 * @params arr  $filter  过滤条件
 *         str           $filter['table']       前序遍历树的数据表
 *         str           $filter['where']       附加的过滤信息
 *         str           $filter['primary']     主键的字段名
 *         arr           $filter['primary_id']  主键的字段值
 *
 * @return bol  true表示移动成功，false表示移动失败 
 */
function lrtree_umove( $filter )
{
    /* 节点的信息 */
    $sql  = 'SELECT * FROM '. $filter['table'] .' WHERE '. $filter['primary'] .'='. $filter['primary_id'];
    $info = $GLOBALS['db']->getRow($sql);

    /* 子节点的IDS，包括自己 */
    $sql  = ' SELECT '. $filter['primary'] .' FROM '. $filter['table'];
    $sql .= ' WHERE lft>='. $info['lft'] .' AND rht<='. $info['rht'];
    $sql .= $filter['where'] ? (' AND '.$filter['where']) : '';
    $ids  = $GLOBALS['db']->getCol($sql);

    /* 参照节点的信息 */
    $sql   = ' SELECT * FROM '. $filter['table'];
    $sql  .= ' WHERE rht='. ($info['lft']-1) .' AND lvl='. $info['lvl'];
    $sql  .= $filter['where'] ? (' AND '.$filter['where']) : '';
    $dinfo = $GLOBALS['db']->getRow($sql);

    /* 移动失败 - 已到达最顶部 */
    if( empty($dinfo) ) return false;

    /* 参照节点的字节点IDS，包括自己 */
    $sql  = ' SELECT '. $filter['primary'] .' FROM '. $filter['table'];
    $sql .= ' WHERE lft>='. $dinfo['lft'] .' AND rht<='. $dinfo['rht'];
    $sql .= $filter['where'] ? (' AND '.$filter['where']) : '';
    $dids = $GLOBALS['db']->getCol($sql);

    /* 更新节点的左右值 */
    $dif = abs($info['lft']-$dinfo['lft']);
    $sql = ' UPDATE '. $filter['table'] .' SET lft=lft-'. $dif .',rht=rht-'. $dif;
    $sql.= ' WHERE '. $filter['primary'] .' IN("'. implode('","',$ids) .'")';
    $GLOBALS['db']->query($sql);

    /* 更新参照节点的左右值 */
    $dif = abs($info['rht']-$dinfo['rht']);
    $sql = ' UPDATE '. $filter['table'] .' SET lft=lft+'. $dif .',rht=rht+'. $dif;
    $sql.= ' WHERE '. $filter['primary'] .' IN("'. implode('","',$dids) .'")';
    $GLOBALS['db']->query($sql);

    return true;
}

/**
 * 改进的前序遍历树下移
 * 
 * @params arr  $filter  过滤条件
 *         str           $filter['table']       前序遍历树的数据表
 *         str           $filter['where']       附加的过滤信息
 *         str           $filter['primary']     主键的字段名
 *         arr           $filter['primary_id']  主键的字段值
 *
 * @return bol  true表示移动成功，false表示移动失败
 */
function lrtree_dmove( $filter )
{
    /* 节点的信息 */
    $sql  = 'SELECT * FROM '. $filter['table'] .' WHERE '. $filter['primary'] .'='. $filter['primary_id'];
    $info = $GLOBALS['db']->getRow($sql);

    /* 子节点的IDS，包括自己 */
    $sql  = ' SELECT '. $filter['primary'] .' FROM '. $filter['table'];
    $sql .= ' WHERE lft>='. $info['lft'] .' AND rht<='. $info['rht'];
    $sql .= $filter['where'] ? (' AND '.$filter['where']) : '';   
    $ids  = $GLOBALS['db']->getCol($sql);

    /* 参照节点的信息 */
    $sql   = ' SELECT * FROM '. $filter['table'];
    $sql  .= ' WHERE lft='. ($info['rht']+1) .' AND lvl='. $info['lvl'];
    $sql  .= $filter['where'] ? (' AND '.$filter['where']) : '';   
    $dinfo = $GLOBALS['db']->getRow($sql);

    /* 移动失败 - 已到达最底部 */
    if( empty($dinfo) ) return false;

    /* 参照节点的字节点IDS，包括自己 */
    $sql  = ' SELECT '. $filter['primary'] .' FROM '. $filter['table'];
    $sql .= ' WHERE lft>='. $dinfo['lft'] .' AND rht<='. $dinfo['rht'];
    $sql .= $filter['where'] ? (' AND '.$filter['where']) : '';   
    $dids = $GLOBALS['db']->getCol($sql);

    /* 更新节点的左右值 */
    $dif = abs($info['rht']-$dinfo['rht']);
    $sql = ' UPDATE '. $filter['table'] .' SET lft=lft+'. $dif .',rht=rht+'. $dif;
    $sql.= ' WHERE '. $filter['primary'] .' IN("'. implode('","',$ids) .'")';
    $GLOBALS['db']->query($sql);

    /* 更新参照节点的左右值 */
    $dif = abs($info['lft']-$dinfo['lft']);
    $sql = ' UPDATE '. $filter['table'] .' SET lft=lft-'. $dif .',rht=rht-'. $dif;
    $sql.= ' WHERE '. $filter['primary'] .' IN("'. implode('","',$dids) .'")';
    $GLOBALS['db']->query($sql);

    return true;
}
?>