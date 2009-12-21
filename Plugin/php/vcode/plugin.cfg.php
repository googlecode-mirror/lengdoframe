<?php
/* 初始组件配置 */
$_PLUGIN_CFG = array();


/* 设置组件配置 - 组件版本 */
$_PLUGIN_CFG['ver']      = 'v1.0.0';

/* 设置组件配置 - 索引文件 */
$_PLUGIN_CFG['file']     = 'vcode.class.php';

/* 设置组件配置 - 组件标题 */
$_PLUGIN_CFG['title']    = '图片验证码';

/* 设置组件配置 - 融合文件 */
$_PLUGIN_CFG['append']   = array();


/* 返回组件配置 */
return $_PLUGIN_CFG;
?>
功能说明：
    图片验证码


使用说明：
    1. 拷贝 所有文件 到 /plugin/php/vcode 文件夹下
    2. 在程序中加载 /plugin/php/vcode/vcode.class.php 文件
    3. 在程序中构建 Vcode 对象并调用其成员函数使用


实例代码：
    1. 显示验证码图片
         include($_CFG['DIR_PLUGIN'].'php/vcode/vcode.class.php');

         $vcode = new VCode();
         $vcode->image();


    2. 检查验证码是否正确
         include($_CFG['DIR_PLUGIN'].'php/vcode/vcode.class.php');

         $vcode = new VCode();
         $vcode->check($_POST['vcode']);
