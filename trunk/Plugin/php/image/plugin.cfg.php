<?php
/* 初始插件配置 */
$_PLUGIN_CFG = array();


/* 设置插件配置 - 插件版本 */
$_PLUGIN_CFG['ver']       = 'v1.0.0';

/* 设置插件配置 - 插件标题 */
$_PLUGIN_CFG['title']     = '图片处理类';

/* 设置插件配置 - 插件安装 */
$_PLUGIN_CFG['install']   = array();


/* 返回插件配置 */
return $_PLUGIN_CFG;
?>
功能说明：
    图片处理类


使用说明：
    1. 拷贝 所有文件 到 /plugin/php/image/ 文件夹下
    2. 在程序中加载 /plugin/php/image/image.class.php 文件
    3. 在程序中构建 Image 对象并调用其成员函数使用