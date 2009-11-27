<?php
/**
 * 文件列表，递归文件夹
 *
 * @params str  $path     文件夹绝对路径
 * @params arr  $ext      指定扩展名文件
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
 * @params str  $rpath    文件夹绝对路径
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
?>