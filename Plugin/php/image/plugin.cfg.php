<?php
/* 初始组件配置 */
$_PLUGIN_CFG = array();


/* 设置组件配置 - 组件版本 */
$_PLUGIN_CFG['ver']      = 'v1.0.0';

/* 设置组件配置 - 索引文件 */
$_PLUGIN_CFG['file']     = 'image.class.php';

/* 设置组件配置 - 组件标题 */
$_PLUGIN_CFG['title']    = '图片处理类';

/* 设置组件配置 - 融合文件 */
$_PLUGIN_CFG['append']   = array();


/* 返回组件配置 */
return $_PLUGIN_CFG;
?>
功能说明：
    图片处理类


使用说明：
    1. 拷贝 所有文件 到 /plugin/php/image/ 文件夹下
    2. 在程序中加载 /plugin/php/image/image.class.php 文件
    3. 在程序中构建 Image 对象并调用其成员函数使用