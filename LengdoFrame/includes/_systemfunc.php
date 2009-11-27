<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 前台公用函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 功能函数
/* ------------------------------------------------------ */

/**
 * 分页处理函数
 *
 * @params  intval  $rows_page    每页行数
 * @params  intval  $rows_total   总共行数
 * @params  string  $pages_group  多少页号为一组
 *
 * @return  string  分页HTML
 **/
function pager( $rows_page, $rows_total, $pages_group = 5 )
{
	/* 参数初始化 */
	$pages_total = floor(($rows_total+$rows_page-1)/$rows_page);
	$pages_total = $pages_total <= 0 ? 1 : $pages_total;

	/* 获得当前页 */
    $cur = pager_current($rows_page, $rows_total, $page_param);
    $url = pager_url_rebuild();


    /* 构建HTML */

    /* 分析 */
    if( $cur > floor($pages_group/2) ){
        $beg = $cur - floor($pages_group/2);
        $end = $cur + floor($pages_group/2) - 1 + ceil($pages_group%2);

        if( $end > $pages_total ){
            $beg -= $end - $pages_total;
            $beg < 1 ? $beg = 1 : '';
        }
    }else{
        $beg = 1;
        $end = $pages_group;
    }

    /* 第一页 */
    if( $beg > 1 ){
        $html .= '<a href="'. $url .'1" class="first">1</a>&nbsp;';
        $html .= '<a href="'. $url . ($cur-10<1?1:$cur-10) .')" class="first">..</a>&nbsp;';
    }

    /* 分页组 */
    for( $i=$beg; $i <= $end; $i++ ){
        if( $i > $pages_total ) break;

        $html .= '<a href="'. $url . $i .'" class="';
        $html .= $i == $cur ? 'on' : 'num';
        $html .= '">'. $i .'</a>&nbsp;';
    }

    /* 尾页 */
    if( $end < $pages_total ){
        $html .= '<a href="'. $url . ($cur+10>$pages_total?$pages_total:$cur+10) .')" class="last">..</a>';
        $html .= '<a href="'. $url . $pages_total .'" class="last">'. $pages_total .'</a>';
    }

    /* 返回 */
    return $html;
}
/**
 * 获取当前页号
 */
function pager_current( $rows_page, $rows_total )
{
	/* 参数初始化 */
	$pages_total = floor(($rows_total+$rows_page-1)/$rows_page);
	$pages_total = $pages_total <= 0 ? 1 : $pages_total;

	/* 获得当前页 */
    if( intval($_GET['page']) <= 0 ){
        return 1;
    }elseif( intval($_GET['page']) > $pages_total ){
        return $pages_total;
    }else{
        return intval($_GET['page']);
    }
}
/**
 * 重构当前URL
 */
function pager_url_rebuild()
{
    $url = $_SERVER['PHP_SELF'].'?';

    foreach( $_GET as $key => $val ){
        if( $key != 'page' ){
            $url .= "$key=$val&";
        }
    }

    return $url.'page=';
}
?>