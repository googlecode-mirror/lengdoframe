﻿<?php
/* 初始插件配置 */
$_PLUGIN_CFG = array();


/* 设置插件配置 - 插件版本 */
$_PLUGIN_CFG['ver']       = 'v2.5.1';

/* 设置插件配置 - 插件标题 */
$_PLUGIN_CFG['title']     = 'FCKeditor编辑器';

/* 设置插件配置 - 插件安装 */
$_PLUGIN_CFG['install']   = array();


/* 返回插件配置 */
return $_PLUGIN_CFG;
?>
功能说明：
    FCKeditor编辑器


使用说明：
    1. 拷贝 所有文件 到 /plugin/javascript/fckeditor/ 文件夹下
    2. 在 /includes/systemconfig.php 文件中配置编辑器的路径信息
          $_CFG['DIR_FCKEDITOR_UPLOAD'] = $_CFG['DIR_ROOT']   . 'upload/jseditor/';  //FCKeditor文件上传目录的绝对地址
          $_CFG['URL_FCKEDITOR_UPLOAD'] = $_CFG['URL_ROOT']   . 'upload/jseditor/';  //FCKeditor文件上传目录的相对地址
          $_CFG['URL_FCKEDITOR_FOLDER'] = $_CFG['URL_PLUGIN'] . 'javascript/fckeditor/fckeditor/';  //FCKeditor核心文件夹相对地址
    3. 在程序中加载 /plugin/javascript/fckeditor/fckeditor.php 文件
    4. 程序中构建 Fckeditor 对象并调用其成员函数使用


实例代码：
    1. 使用PHP创建编辑器
         require_once($_CFG['DIR_PLUGIN'].'javascript/fckeditor/fckeditor.php');

         $fck = new FCKeditor( $name, array('value'=>$content) );
         echo $fck->CreateHtml();