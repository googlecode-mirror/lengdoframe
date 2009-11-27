<?php
/**
 * 上传图片 v1.0.0
 *
 * @params str  $folder  要上传的文件所在的文件夹
 * @params arr  $fcname  文件域对象名称
 *
 * @return str  返回文件名(相对于$folder的路径)
 */
function upload_pic( $folder, $fcname )
{
    /* 文件域对象检测 */
    if( !is_array($_FILES[$fcname]) || $_FILES[$fcname]['error'] != 0 || !$_FILES[$fcname]['size'] ) return '';

    /* 增加文件夹路径末尾斜干 */
    $folder = rtrim(str_replace("\\",'/',$folder), '/') . '/';

    /* 检查文件格式 */
    $ext = strtolower( substr($_FILES[$fcname]['name'],-4) );
    if( !in_array($ext, array('.jpg','.gif')) ){
        sys_msg('无效图片格式');
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

    /* 移动文件 */
    if( move_uploaded_file($_FILES[$fcname]['tmp_name'], $folder.$filename) ){
        return $filename;
    }else{
        return '';
    }
}

/**
 * 缩放图片 v1.0.0
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
        $dfilename = thumb_pic_fname_append($sfilename, '_thumb');
    }

    /* 加载辅助库 */
    require_once(DIR_ROOT . 'class/image.class.php');

    $img = new Image();

    $img->setSrcImg($folder.$sfilename);
    $img->setDstImg($folder.$dfilename);
    $img->createImg($width, $height);

    return $dfilename;
}
/**
 * 图片文件名追加字符
 *
 * @params str  $fname   文件名称或者文件路径，带扩展名
 * @params str  $append  要追加的字符
 *
 * @return str  
 */
function thumb_pic_fname_append( $fname, $append )
{
    /* 初始化文件名 */
    $fname = trim($fname);

    /* 无效的文件名 */
    if( !preg_match('/\.[a-zA-Z0-9]+$/', $fname) ) return $fname;

    /* 分解文件名 */
    $arr = explode('.', $fname);
    $arr[count($arr)-2] .= $append;

    /* 重组文件名并返回 */
    return implode('.', $arr);
}

/**
 * 删除图片 v1.0.0
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
?>