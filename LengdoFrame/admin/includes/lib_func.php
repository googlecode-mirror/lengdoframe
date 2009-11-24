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
// - 图片处理
/* ------------------------------------------------------ */

/**
 * 上传图片
 *
 * @params str  $folder  要上传的文件所在的文件夹
 * @params arr  $_file   文件域对象
 *
 * @return str  返回文件名(相对于$folder的路径)
 */
function upload_pic( $folder, $_file )
{
    global $_LANG;

    /* 文件域对象检测 */
    if( !is_array($_file) || $_file['error'] != 0 || !$_file['size'] ) return '';

    /* 增加文件夹路径末尾斜干 */
    $folder = rtrim(str_replace("\\","/",$folder), '/').'/';

    /* 检查文件格式 */
    $ext = strtolower( substr($_file['name'],-4) );
    if( !in_array($ext, array('.jpg','.gif')) ){
        sys_msg($_LANG['file_ext_error_img']);
    }

    /* 上传的文件权限检查,尝试建立下级目录. 目录格式 YYYYMM/DD */
    $d  = date('d', time());
    $ym = date('Ym', time());
    if( !file_exists($folder.$ym) ){
        @mkdir($folder.$ym);
    }
    if( !file_exists($folder.$ym.'/'.$d) ){
        @mkdir($folder.$ym.'/'.$d);
    }
    $mask = file_privilege($folder.$ym.'/'.$d);

    /* 复制文件 */
    if( $mask >= 7 ){
        $filename = $ym .'/'. $d .'/'. date( 'His', time() ) .$ext;
    }else{
        $filename = date( 'Ym_d_His', time() ). $ext;
    }

    /* 转移文件 */
    if( move_uploaded_file($_file['tmp_name'], $folder.$filename) ){
        return $filename;
    }else{
        return '';
    }
}

/**
 * 删除图片
 *
 * @params str  $folder    要删除的文件所在的文件夹
 * @params str  $filename  要删除的文件名(相对于$folder的路径)
 *
 * @return bol  true表示删除成功，false表示删除失败
 */
function delete_pic( $folder, $filename )
{
    /* 增加文件夹路径末尾斜干 */
    $folder = rtrim(str_replace("\\","/",$folder), '/').'/';

    /* 删除文件 */
    return @unlink($folder.$filename);
}

/**
 * 缩放图片
 *
 * @params str  $folder     源文件所在文件夹路径
 * @params str  $sfilename  源文件名(相对$folder的路径)
 * @params str  $dfilename  目标文件名(相对$folder的路径 - 如果为空则使用$sfile构建目标文件名，文件名追加'_thumb')
 * @params mix  $width      目标宽度，宽度类型为float时，将采用百分比缩放图片
 * @params int  $height     目标高度，如果为null，那么将采用宽度的缩放比例（仅当$img_w类型为int时）
 *
 * @return str  返回文件名(相对于$folder的路径)
 */
function thumb_pic( $folder, $sfilename, $dfilename = '', $width = 0.5, $height = null )
{
    /* 增加文件夹路径末尾斜干 */
    $folder = rtrim(str_replace("\\","/",$folder), '/').'/';

    /* 源文件存在检查 */
    if( !is_file($folder.$sfilename) ){
        return '';
    }

    /* 初始化目标文件路径 */
    if( !trim($dfilename) ){
        $dfilename = fname_append($sfilename, '_thumb');
    }

    /* 加载辅助库 */
    require_once($_CFG['DIR_CLS'] . 'image.class.php');

    $img = new Image();

    $img->setSrcImg($folder.$sfilename);
    $img->setDstImg($folder.$dfilename);
    $img->createImg($width, $height);

    return $dfilename;
}

/**
 * 获得排序图像HTML以及访问这个HTML的变量名
 * 变量名格式：img_{$field}
 *
 * @params arr  $filter  过滤条件
 */
function order_img( $filter )
{
    $flag['var'] = 'img_' . preg_replace('/^.*\./', '', $filter['order_fd']);
    $flag['img'] = '<span class="'. strtolower($filter['order_type']) .'"></span>';

    return $flag;
}


/* ------------------------------------------------------ */
// - 文件类
/* ------------------------------------------------------ */

/**
 * 文件列表，递归文件夹
 *
 * @params str  $path     文件夹真实路径
 * @params arr  $ext      指定扩展名
 * @params str  $filter   过滤掉含指定词的文件名(多个词用 | 分隔)，默认不启用
 * @params str  $contain  选择含指定词的文件名(多个词用 | 分隔)，默认不启用
 * @params bol  $page     是否使用分页
 *
 * @return arr  列表数据
 */
function list_file( $path, $ext = array(), $filter = '', $contain = '', $page = true )
{
    /* 初始化 */
    $p = $list = array();

    /* 目录有效性检查 */
    if( !is_dir($path) ){
        return $list;
    }

    /* 初始化路径格式,在路径末尾加上斜线 */
    $path  = str_replace("\\", '/', $path);
    $path .= preg_match('/[\/]$/',$path) ? '' : '/';

    /* 初始化过滤词 */
    $filters  = trim($filter)  ? explode('|', $filter)  : array();
    $contains = trim($contain) ? explode('|', $contain) : array();

    /* 保存文件夹下的文件信息 */
    $files  = array();
    $folder = opendir($path);
    while( $file = readdir($folder) ){
        /* 如果是文件 */
        if( is_file($path.'/'.$file) ){
            /* 过滤掉含指定词的文件名 */
            if( !empty($filters) ){
                $finded = false;

                foreach( $filters AS $v ){
                    if( strpos($file,$v) !== false ){
                        $finded = true; break;
                    }
                }

                if( $finded === true ) continue;
            }
            /* 选择含指定词的文件名 */
            if( !empty($contains) ){
                $finded = false;

                foreach( $contains AS $v ){
                    if( strpos($file,$v) !== false ){
                        $finded = true; break;
                    }
                }

                if( $finded === false ) continue;
            }

            /* 没指定扩展名 */
            if( empty($ext) ){
                $files[] = array( 'path'=>$path.$file, 'file'=>$file, 'ctime'=>filectime($path.$file) );
            }

            /* 指定扩展名 */
            elseif( in_array(substr($file,strrpos($file,'.')+1), $ext) ){
                $files[] = array( 'path'=>$path.$file, 'file'=>$file, 'ctime'=>filectime($path.$file) );
            }
        }

        /* 如果是文件夹 */
        elseif( !preg_match('/^\./',$file) && is_dir($path.$file) ){
            $tlist = list_file($path.$file, $ext, $filter, $contain, false);
            $files = array_merge($files, $tlist['data']);
        }
    }
    closedir($folder);

    /* 设置分页数据和信息 */
    if( $page ){
        $p['rows_page']  = intval($_REQUEST['rows_page']) ? intval($_REQUEST['rows_page']) : 15;
        $p['rows_total'] = count($files);
        $p['html']       = pager( $p['rows_page'], $p['rows_total'] );
        $p['cur_page']   = cur_page( $p['rows_page'], $p['rows_total'] );
        $p['row_start']  = ($p['cur_page']-1) * $p['rows_page'];

        $list['data']    = array_slice($files, $p['row_start'], $p['rows_page']);
        $list['pager']   = $p;
    }else{
        $list['data']    = $files;
    }

    /* 返回 */
    return $list;
}

/**
 * 文件列表 - 图像
 *
 * @params str  $rpath    文件夹真实路径
 * @params str  $upath    文件夹相对路径
 * @params str  $filter   过滤掉含指定词的文件名，默认不启用
 * @params str  $contain  选择含指定词的文件名，默认不启用
 * @params bol  $page     是否使用分页
 *
 * @params arr  列表数据
 */
function list_file_img( $rpath, $upath, $filter = '', $contain = '', $page = true )
{
    /* 初始化图像扩展名 */
    $ext = array('jpg', 'gif', 'png');

    /* 初始化路径格式,在路径末尾加上斜线 */
    $rpath  = str_replace("\\", '/', $rpath);
    $rpath .= preg_match( '/[\/]$/', $rpath ) ? '' : '/';
    $upath  = str_replace("\\", '/', $upath);
    $upath .= preg_match( '/[\/]$/', $upath ) ? '' : '/';

    $list = list_file($rpath, $ext, $filter, $contain, $page);

    /* 添加图片的URL访问路径 */
    foreach( $list['data'] AS $i=>$file ){
        $list['data'][$i]['upath'] = str_replace($rpath, $upath, $file['path']); //URL路径
        $list['data'][$i]['bpath'] = str_replace($rpath, '', $file['path']);     //基本路径（相对于$rpath）
    }

    /* 返回 */
    return $list;
}

/**
 * 文件或目录权限检查函数
 *
 * @params str  $file_path   文件路径(或文件夹路径)
 * @params bol  $rename_prv  是否在检查修改权限时检查执行rename()函数的权限
 *
 * @return int  返回 8421 码
 *              如果是文件夹，则代表
 *              目录下文件可执行rename()函数、目录下文件可改、目录可写、目录可读。
 *              如果是文件，则代表
 *              文件可执行rename()函数、文件可改、可写、文件可读。
 */
function file_privilege( $file_path )
{
    /* 参数过滤 */
    $file_path = rtrim( str_replace("\\",'/',$file_path), '/ ' );

    /* 文件或文件夹不存在不存在 */
    if( !file_exists($file_path) ){
        return false;
    }

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

/**
 * 取出模板HTML代码
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


/* ------------------------------------------------------ */
// - 功能函数
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
    $cur = cur_page($rows_page, $rows_total, $page_param);

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
function cur_page( $rows_page, $rows_total )
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


/**
 * 列表数据导出
 *
 * @params str  $file  导出的文件名
 * @params arr  $rs    导出的数据集
 * @params str  $cols  要导出的列名
 */
function list_export( $file, $rs, $cols = '' )
{
    /* 输出文件信息头 */
    header("Content-Type: application/vnd.ms-excel");           //文件类型
    header('Content-disposition: attachment; filename='.$file); //文件名称

    /* 列名数组化 */
    $cols = empty($cols) ? array() : explode(',',$cols);

    /* 输出数据 */
    foreach( $rs AS $r ){
        /* 调整输出数据 */
        $r = array_map('list_export_adjust', $r);

        if( empty($cols) ){
            $str = '"'. implode('","', $r) .'",';
        }else{
            $str = '';

            foreach( $cols AS $col ){
                $str .= '"'. $r[$col] .'",';
            }
        }

        if( $str ){
            echo substr($str,0,-1),"\r\n";
        }
    }

    exit();
}
function list_export_adjust( $str )
{
    $str = str_replace('"', '""', $str);

    if( function_exists('mb_convert_encoding') ){
        return mb_convert_encoding($str, 'GB2312', 'UTF-8');
    }

    if( function_exists('iconv') ){
        return iconv('UTF-8', 'GB2312//IGNORE', $str);
    }

    return $str;
}


/**
 * 插入管理员日志
 *
 * @params str  $info  信息
 */
function admin_log( $info )
{
    $fields = array();

    $fields['ip']         = $_SERVER['REMOTE_ADDR'];
    $fields['info']       = addslashes(stripslashes($info));
    $fields['admin_id']   = admin_id();
    $fields['admin_name'] = addslashes(admin_name());
    $fields['in_time']    = time();

    $GLOBALS['db']->insert( tname('admin_log'), $fields );
}


/* ------------------------------------------------------ */
// - 运行时设置
/* ------------------------------------------------------ */

/**
 * 初始化临时语言
 *
 * @params str  $module  模块文件名
 */
function init_temp_lang( $module )
{
    global $_LANG;

    $_LANG['add:']    = admin_privilege_name_fk($module, 'add').'：';
    $_LANG['del:']    = admin_privilege_name_fk($module, 'del').'：';
    $_LANG['edit:']   = admin_privilege_name_fk($module, 'edit').'：';
    $_LANG['assign:'] = admin_privilege_name_fk($module, 'assign').'：';
}
?>