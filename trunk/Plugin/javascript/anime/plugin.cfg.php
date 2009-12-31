﻿<?php
/* 初始组件配置 */
$_PLUGIN_CFG = array();


/* 设置组件配置 - 组件版本 */
$_PLUGIN_CFG['ver']       = 'v1.0.0';

/* 设置组件配置 - 组件标题 */
$_PLUGIN_CFG['title']     = '图片动画效果';

/* 设置组件配置 - 组件安装 */
$_PLUGIN_CFG['install']   = array();
$_PLUGIN_CFG['install'][] = array('fpath'=>$_CFG['DIR_ADMIN'].'script/system.js', 'src'=>'anime.js', 'type'=>'JS LOAD JS');


/* 返回组件配置 */
return $_PLUGIN_CFG;
?>
功能说明：
    图片动画效果


使用说明：
    1. 拷贝 所有文件 到 /plugin/javascript/anime/ 文件夹下并安装
    2. 在程序中调用 Anime 对象的成员函数使用