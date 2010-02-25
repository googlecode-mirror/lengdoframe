<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 整站公用函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ----------------------------------------------------------------------- */
// - 字符安全类函数
/* ----------------------------------------------------------------------- */

/**
 * 递归方式的对变量中的特殊字符进行转义和反转义
 *
 * @params mix  $str  数组或者字符串
 *
 * @return mix
 */
function addslashes_deep( $str )
{
    return is_array($str) ? array_map('addslashes_deep', $str) : addslashes($str);
}

function stripslashes_deep( $str )
{
    return is_array($str) ? array_map('stripslashes_deep', $str) : stripslashes($str);
}

/**
 * 对 MYSQL LIKE 的内容进行转义
 *
 * @params str  $str  要转义的字符
 *
 * @return str
 */
function mysql_like_slash( $str )
{
    return strtr( $str, array("\\\\"=>"\\\\\\\\", '_'=>'\_', '%' => '\%') );
}

/**
 * 变量值格式化
 *
 * @params mix  $value   要修饰的值
 * @params str  $modify  修饰类型
 * @params str  $attrib  修饰类型的属性
 *
 * @return str  返回修饰后的字符
 */
function f( $value, $modify, $attrib = '' )
{
    switch( $modify ){
        /* html 中的 js string 编码(以单引号为边界). 编码( " ) 转义( ' => \' ) */
        case 'hstr': $value = addslashes( strtr($value,array('"'=>'&quot;')) ); break;

        /* html 编码( & , " , < , > , 空格, 换行 ) */
        case 'html': $value = strtr( htmlspecialchars($value), array(' '=>'&nbsp;',"\r\n"=>'<br />',"\n"=>'<br />') ); break;

        /* 格式化时间。默认格式YYYY-MM-DD HH:II, 无效值则返回'' */
        case 'date': $value = ($value>=57600&&$value<=2147443200) ? date(f($attrib,'default','Y-m-d H:i'),$value):''; break;

        /* html formc 编码( & , " , < , > ) */
        case 'formc': $value = htmlspecialchars($value); break;

        /* 字符颜色，格式化成FONT。默认红色 */
        case 'color': $value = '<font color="'. ($attrib?$attrib:'#ff0000') .'">'. $value .'</font>'; break;

        /* 变量默认值。默认值：'', false, 0, '0', null, array() ) */
        case 'default': $value = empty($value) ? $attrib : $value; break;

        /* 字符截取。默认截取80个字 */
        case 'truncate': $value = sub_str($value, (intval($attrib)?intval($attrib):80), true); break;
    }

    return $value;
}

/**
 * 变量值格式化, 并输出结果
 */
function e( $value, $modify = '', $attrib = '' )
{
    echo f($value, $modify, $attrib);
}


/* ----------------------------------------------------------------------- */
// - 字符函数
/* ----------------------------------------------------------------------- */

/**
 * 截取UTF-8编码下字符串的函数
 *
 * @params str  $str     被截取的字符串
 * @params int  $length  截取的长度
 * @params bol  $append  是否附加省略号
 *
 * @return str
 */
function sub_str( $str, $length = 0, $append = true )
{
    $str = trim($str);
    $len = strlen($str);

    if( $length == 0 || $length >= $len ){
        return $str;
    }
    else if( $length < 0 ){
        $length = $len + $length;
        if( $length < 0 ){
            $length = $len;
        }
    }

    if( function_exists('mb_substr') ){
        $newstr = mb_substr($str, 0, $length, 'UTF-8');
    }else if( function_exists('iconv_substr') ){
        $newstr = iconv_substr($str, 0, $length, 'UTF-8');
    }else{
        $newstr = trim_right(substr($str, 0, $length));
    }

    if( $append && $str != $newstr ){
        $newstr .= '...';
    }

    return $newstr;
}

/**
 * 去除字符串右侧可能出现的乱码
 *
 * @params str  $str  字符串
 *
 * @return str  
 */
function trim_right( $str )
{
    $len = strlen( preg_replace('/[\x00-\x7F]+/', '', $str) ) % 3;

    if( $len > 0 ){
        $str = substr($str, 0, 0-$len);
    }

    return $str;
}

/**
 * 获取数据表名（加前辍处理）
 *
 * @params str  $tname   数据表名
 *
 * @return str  
 */
function tname( $tname )
{
    global $_CFG;
    return $_CFG['tblpre'].$tname;
}


/* ----------------------------------------------------------------------- */
// - HTML控件
/* ----------------------------------------------------------------------- */

/**
 * 构建下拉列表 - 采用$_DBD作为数据
 *
 * @params str  $dbd       $_DBD数组的下标
 * @params str  $name      下拉列表的名称和ID
 * @params str  $selected  选中的值
 * @params arr  $appends   追加到顶部的下拉项
 * @params arr  $filter    需要过滤掉的Key值
 * @params bol  $contain   Key值的过滤方式  - true表示包含，false表示不包含
 */
function ddl_dbd( $dbd, $name, $selected = '', $appends = array(), $attribs = array(), $filter = array(), $contain = false )
{
    global $_DBD;

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
    foreach( $_DBD[$dbd] AS $key=>$text ){
        if( $contain == true && !in_array($key,$filter) ) continue;
        if( $contain == false && in_array($key,$filter) ) continue;

        $items[] = array( 'value'=>$key, 'text'=>f($text,'html') );
    }

    $formc = new Formc();
    return $formc->ddl( $name, $items, array_merge(array('selected'=>$selected),$attribs) );
}

/**
 * 构建下拉列表 - 采用数据库作为数据
 *
 * @params str  $name      下拉列表的名称和ID
 * @params str  $table     数据库表
 * @params arr  $fields    作为下拉列表项的值和文本的字段：array( '$value_fields' => '$text_fields' )
 * @params str  $where     SQL中的WHERE条件
 * @params str  $selected  选中的值
 * @params arr  $appends   追加到顶部的下拉项
 * @params arr  $filter    需要过滤掉的Key值
 * @params bol  $contain   Key值的过滤方式 - true表示包含，false表示不包含
 */
function ddl_db( $name, $table, $fields, $where = '', $selected = '', $appends = array(), $attribs = array(), $filter = array(), $contain = false )
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
    $vfd  = key($fields);
    $tfd  = $fields[$vfd];

    $sql  = "SELECT `{$vfd}`, `{$tfd}` FROM `{$table}`". (empty($where) ? '' : (' WHERE '.$where));
    $rows = $GLOBALS['db']->getAll($sql);

    foreach( $rows AS $r ){
        if( $contain == true && !in_array($r[$vfd],$filter) ) continue;
        if( $contain == false && in_array($r[$vfd],$filter) ) continue;

        $items[] = array( 'value'=>$r[$vfd], 'text'=>f($r[$tfd],'html') );
    }

    $formc = new Formc();
    return $formc->ddl( $name, $items, array_merge(array('selected'=>$selected),$attribs) );
}

/**
 * 构建单选框 - 采用$_DBD作为数据
 *
 * @params str  $dbd      $_DBD数组的下标
 * @params str  $name     单选框的名称
 * @params int  $checked  要选中的值
 * @params arr  $filter   需要过滤掉的Key值
 * @params bol  $contain  Key值的过滤方式 - true表示包含，false表示不包含
 */
function radio_dbd( $dbd, $name, $checked, $filter = array(), $contain = false )
{
    global $_DBD;

    $html  = '';
    $formc = new Formc();

    foreach( $_DBD[$dbd] AS $key=>$text ){
        if( $contain == true && !in_array($key,$filter) ) continue;
        if( $contain == false && in_array($key,$filter) ) continue;

        $html .= $formc->radio( $name, array('value'=>$key, 'text'=>$text, 'checked'=>($key==$checked?true:false)) );
    }

    return $html;
}

/**
 * 构建复选框 - 采用$_DBD作为数据
 *
 * @params str  $dbd      $_DBD数组的下标
 * @params arr  $checked  要选中的值
 * @params arr  $filter   需要过滤掉的Key值
 * @params bol  $contain  Key值的过滤方式 - true表示包含，false表示不包含
 */
function cb_dbd( $dbd, $checked = array(), $filter = array(), $contain = false )
{
    global $_DBD;

    /* 初始化参数 */
    if( !is_array($checked) ){
        $checked = array();
    }

    /* 初始化 */
    $html  = '';
    $formc = new Formc();

    foreach( $_DBD[$dbd] AS $name=>$text ){
        if( $contain == true && !in_array($name,$filter) ) continue;
        if( $contain == false && in_array($name,$filter) ) continue;

        $html .= $formc->cb( $name, array('value'=>'1', 'text'=>$text, 'checked'=>(in_array($name,$checked)?true:false)) );
    }

    return $html;
}


/* ------------------------------------------------------ */
// - 异步JSON
/* ------------------------------------------------------ */

/**
 * 将数据格式化成JSON
 * 
 * @params str  $error    指明是否有错
 * @params str  $message  错误消息
 * @params str  $content  内容
 * @params arr  $append   追加JSON项
 */
function make_json_response( $error = '0', $message = '', $content = '', $append = array() )
{
    /* 初始化 */
    $res = array( 'error'=>$error, 'message'=>$message, 'content'=>$content );

    /* 辅助项 */
    foreach( $append AS $key=>$value ){
        $res[$key] = $value;
    }

    /* JSON编码，输出 */
    exit( json_encode($res) );
}
function make_json_ok( $message = '', $content = '', $append = array() )
{
    make_json_response('0', $message, $content, $append);
}
function make_json_fail( $message = '' )
{
    make_json_response('1', $message);
}


/* ----------------------------------------------------------------------- */
// - 其他函数
/* ----------------------------------------------------------------------- */

/**
 * 页面跳转
 */
function redirect( $url )
{
    header('location:'.$url); exit();
}

/**
 * 将字节转成可阅读格式
 *
 * @params int  $num  要转化的数字
 */
function bitunit( $num )
{
    $unit = array(' B',' KB',' MB',' GB');

    for ( $i = 0 ; $i < count($unit); $i++ ){
       /* 1024B 会显示为 1KB */
       if( $num >= pow(2, 10*$i) ){
           $bit_size = (ceil($num / pow(2, 10*$i)*100)/100) . $unit[$i];
       }
    }

    return $bit_size;
}
?>