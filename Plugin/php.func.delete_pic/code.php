<?php
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
?>