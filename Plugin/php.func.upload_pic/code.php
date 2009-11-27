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
?>