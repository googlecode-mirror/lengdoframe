<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 后台公用函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 系统消息
/* ------------------------------------------------------ */

/**
 * 系统消息
 *
 * @params str  $msg  消息
 */
function sys_msg( $msg )
{
    $html  = '<body style="margin:0"><div style="text-align:center;border:1px solid #ccc;background:#ffffe1;';
    $html .= 'padding:20px;margin:5px;font:12px Garamonds,Tahoma;color:#777;font-weight:blod;">'. $msg .'</div></body>';

    exit($html);
}


/* ------------------------------------------------------ */
// - 组合框
/* ------------------------------------------------------ */

/**
 * 时间组合框
 *
 * @params str  $name     组合框名称
 * @params arr  $configs  组合框配置
 * @params arr  $attribs  文本框的属性
 */
function timecbox( $name, $configs = array(), $attribs = array() )
{
    /* 无效参数 */
    if( !is_string($name) || $name == '' ) return;

    /* 初始化参数 */
    if( !is_array($configs) ) $configs = array();

    /* 初始化参数 */
    if( is_array($attribs) && !empty($attribs) ){
        $attribs['name']  = $name;
        $attribs['type']  = 'text';
        $attribs['class'] = isset($attribs['class']) ? $attribs['class'] : 'textbox';
        $attribs['style'] = isset($attribs['style']) ? $attribs['style'] : 'width:70px;';
    }else{
        $attribs = array('name'=>$name,'type'=>'text', 'class'=>'textbox', 'style'=>'width:70px');
    }
    
    /* 构建HTML */
    $html = '<div class="timecbox"><input';

    /* 构建HTML - 文本框属性 */
    foreach( $attribs AS $attrib=>$value ){
        if( $value === null ){
            $html .= ' '. $attrib;
        }
        else{
            $html .= ' '. $attrib .'="'. $value .'"';
        }
    }

    /* 构建HTML - 按钮及配置 */
    $html .= '/><input type="button" onmouseover="combobox_mouseover(this)" class="choice" onclick="timecbox_cal(this,';
    $html .= str_replace('"',"'",json_encode($configs)) .')"/></div>';

    /* 返回 */
    return $html;
}


/* ------------------------------------------------------ */
// - 文件类
/* ------------------------------------------------------ */

/**
 * 取出视图HTML代码
 *
 * @params str  $file  视图文件名
 * @params arr  $tpl   视图的数据变量
 */
function tpl_fetch( $file, $tpl )
{
    /* 初始化 */
    global $_LANG, $_DBD, $_CFG;

    /* 缓存开始 */
    ob_start();

    /* 加载视图 */
    include($_CFG['DIR_ADMIN_TPL'] . $file);

    /* 获取视HTML */
    $html = ob_get_contents();

    /* 缓存结束 */
    ob_end_clean();
    
    /* 返回 */
    return $html;
}

/**
 * 文件(夹)权限检查函数
 *
 * @params str  $file_path   文件(夹)路径
 *
 * @return int  返回8421码，false表示文件不存在
 *              如果是文件，8421码分别代表
 *                  文件可执行rename()函数、文件可改、可写、文件可读。
 *              如果是文件夹，8421码分别代表
 *                  目录下文件可执行rename()函数、目录下文件可改、目录可写、目录可读。
 */
function file_privilege( $file_path )
{
    /* 参数过滤 */
    $file_path = rtrim( str_replace("\\",'/',$file_path), '/ ' );

    /* 文件或文件夹不存在不存在 */
    if( !file_exists($file_path) ) return false;

    /* 文件权限属性初始化 */
    $mark = 0;

    /* windows操作系统 */
    if( strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ){
        /* 如果是目录 */
        if( is_dir($file_path) ){
            /* 检查目录是否可读 */
            $dir = @opendir($file_path);
            if( $dir === false ){
                return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
            }
            if( @readdir($dir) !== false ){
                $mark ^= 1; //目录可读 001，目录不可读 000
            }
            @closedir($dir);

            /* 测试文件 */
            $test_file = $file_path . '/_test.txt';

            /* 检查目录是否可写 */
            $fp = @fopen($test_file, 'wb');
            if( $fp === false ){
                return $mark; //如果目录中的文件创建失败，返回目录可读不可写 001。
            }
            if( @fwrite($fp, 'directory access testing.') !== false ){
                $mark ^= 2; //目录可写可读011
            }
            @fclose($fp);

            @unlink($test_file);

            /* 检查目录下文件是否可修改 */
            $fp = @fopen($test_file, 'ab+');
            if( $fp === false ){
                return $mark;
            }
            if( @fwrite($fp, "modify test.\r\n") !== false ){
                $mark ^= 4; //目录下文件可改可写可读111
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if( @rename($test_file, $test_file) !== false ){
                $mark ^= 8;
            }
            @unlink($test_file);
        }

        /* 如果是文件 */
        else if( is_file($file_path) ){
            /* 以读方式打开 */
            $fp = @fopen($file_path, 'rb');
            if( $fp ){
                $mark ^= 1; //可读 001
            }
            @fclose($fp);

            /* 试着修改文件 */
            $fp = @fopen($file_path, 'ab+');
            if( $fp && @fwrite($fp, '') !== false ){
                $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
            }
            @fclose($fp);

            /* 检查文件是否有执行rename()函数的权限 */
            if( @rename($file_path, $file_path) !== false ){
                $mark ^= 8;
            }
        }
    }

    /* 其他操作系统 */
    else{
        if( @is_readable($file_path) ){
            $mark ^= 1;
        }

        if( @is_writable($file_path) ){
            $mark ^= 14;
        }
    }

    return $mark;
}


/* ------------------------------------------------------ */
// - 管理员日志
/* ------------------------------------------------------ */

/**
 * 插入管理员日志
 *
 * @params str  $info  信息
 */
function admin_log( $info )
{
    $fields = array();

    $fields['ip'] = $_SERVER['REMOTE_ADDR'];
    $fields['info'] = addslashes(stripslashes($info));
    $fields['in_time'] = time();

    $fields['admin_id'] = admin_id();
    $fields['admin_name'] = addslashes(admin_name());
    $fields['admin_username'] = addslashes(admin_username());

    $GLOBALS['db']->insert( tname('admin_log'), $fields );
}


/* ------------------------------------------------------ */
// - 分页函数
/* ------------------------------------------------------ */

/**
 * 分页处理函数
 *
 * @params int  $rows_page    每页行数
 * @params int  $rows_total   总共行数
 * @params str  $pages_group  多少页号为一组
 *
 * @return str  分页HTML
 **/
function pager( $rows_page, $rows_total, $pages_group = 5 )
{
	/* 参数初始化 */
	$pages_total = floor(($rows_total+$rows_page-1)/$rows_page);
	$pages_total = $pages_total <= 0 ? 1 : $pages_total;

	/* 获得当前页 */
    $cur = pager_current($rows_page, $rows_total, $page_param);

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
        $_html .= '<a href="javascript:ListTable.pageTo(1)" class="first">1</a>';
        $_html .= '<a href="javascript:ListTable.pageTo('. ($cur-10<1?1:$cur-10) .')" class="first">..</a>&nbsp;';
    }

    /* 分页组 */
    for( $i=$beg; $i <= $end; $i++ ){
        if( $i > $pages_total ) break;

        $_html .= '<a href="javascript:ListTable.pageTo('. $i .')" class="';
        $_html .= $i == $cur ? 'on' : 'num';
        $_html .= '">'. $i .'</a>&nbsp;';
    }

    /* 尾页 */
    if( $end < $pages_total ){
        $_html .= '<a href="javascript:ListTable.pageTo('. ($cur+10>$pages_total?$pages_total:$cur+10) .')" class="last">..</a>';
        $_html .= '<a href="javascript:ListTable.pageTo('. $pages_total .')" class="last">'. $pages_total .'</a>';
    }

    /* 记录条数信息 */
    $_html .= '<a class="info">[ '. (($cur-1)*$rows_page+1) .'/'. $rows_total .' ]</a>';

    /* 返回 */
    return $_html;
}
/**
 * 取得当前页号
 */
function pager_current( $rows_page, $rows_total )
{
	/* 参数初始化 */
	$pages_total = floor(($rows_total+$rows_page-1)/$rows_page);
	$pages_total = $pages_total <= 0 ? 1 : $pages_total;

	/* 获得当前页 */
    if( intval($_REQUEST['page']) <= 0 ){
        return 1;
    }elseif( intval($_REQUEST['page']) > $pages_total ){
        return $pages_total;
    }else{
        return intval($_REQUEST['page']);
    }
}


/* ------------------------------------------------------ */
// - HTML文件下载头
/* ------------------------------------------------------ */

/**
 * HTTP导出
 *
 * @params str  $file     导出的文件名
 * @params str  $data     导出的数据
 * @params str  $oencode  输出编码，'UTF-8', 'GB2312'
 */
function http_download( $file, $data, $oencode = 'UTF-8' )
{
    /* 输出数据导出的文件头 */
    http_download_header($file);

    /* 编码并输出数据 */
    echo http_download_encode($data, $oencode); exit();
}
function http_download_header( $file )
{
    /* 文件扩展名 */
    $ext = end( explode('.',$file) );

    /* HTML文件头的内容类型 */
    $ctype = array();
    $ctype['sql'] = 'text/plain';
    $ctype['csv'] = 'application/vnd.ms-excel';

    /* 输出文件头 */
    header('Content-Type: '.$ctype[$ext]);                      //文件类型
    header('Content-disposition: attachment; filename='.$file); //文件名称
}
function http_download_encode( $str, $oencode, $iencode = 'UTF-8' )
{
    if( function_exists('mb_convert_encoding') ){
        return mb_convert_encoding($str, $oencode, $iencode);
    }

    if( function_exists('iconv') ){
        return iconv($iencode, $oencode.'//IGNORE', $str);
    }

    return $str;
}


/* ------------------------------------------------------ */
// - 列表导出
/* ------------------------------------------------------ */

/**
 * 列表数据导出
 *
 * @params str  $file  导出的文件名
 * @params arr  $rows  导出的数据集
 * @params str  $cols  要导出的列名
 */
function list_export( $file, $rows, $cols = '' )
{
    /* 初始化 */
    $str = '';

    /* 初始化列名 */
    $cols = empty($cols) ? array() : explode(',',$cols);

    /* 输出数据 */
    foreach( $rows AS $r ){
        if( empty($cols) ){
            $str .= '"'. implode('","', str_replace('"','""',$r)) ."\"\r\n";
        }else{
            foreach( $cols AS $col ){
                $str .= '"'. str_replace('"','""',$r[$col]) .'",';
            }
            $str = rtrim($str, ',')."\r\n";
        }
    }

    http_download($file, $str, 'GB2312');
}


/* ------------------------------------------------------ */
// - 列表排序图标
/* ------------------------------------------------------ */

/**
 * 构建排序图像HTML和访问这个HTML的变量名
 * 变量名格式：img_{$field}
 *
 * @params arr  $filter  过滤条件，含有order_fd，order_type值
 *
 * @return arr  
 */
function order_img( $filter )
{
    $flag['var'] = 'img_' . preg_replace('/^.*\./', '', $filter['order_fd']);
    $flag['img'] = '<span class="'. strtolower($filter['order_type']) .'"></span>';

    return $flag;
}
?>