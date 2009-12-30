<?php
/* 初始组件配置 */
$_PLUGIN_CFG = array();


/* 设置组件配置 - 组件版本 */
$_PLUGIN_CFG['ver']      = 'v3.4.0';

/* 设置组件配置 - 组件标题 */
$_PLUGIN_CFG['title']    = 'Kindeditor编辑器';

/* 设置组件配置 - 组件安装 */
$_PLUGIN_CFG['install']   = array();
$_PLUGIN_CFG['install'][] = array('fpath'=>$_CFG['DIR_ADMIN'].'script/system.js', 'src'=>'kindeditor.js' , 'type'=>'JS LOAD JS');


/* 返回组件配置 */
return $_PLUGIN_CFG;
?>
功能说明：
    Kindeditor编辑器


使用说明：
    1. 拷贝 所有文件 到 /plugin/javascript/kindeditor/ 文件夹下并安装
    2. 在 /includes/systemconfig.php 文件中配置编辑器的路径信息
          $_CFG['DIR_KINDEDITOR_UPLOAD'] = $_CFG['DIR_ROOT'] . 'upload/jseditor/';  //Kindeditor文件上传目录的绝对地址
          $_CFG['URL_KINDEDITOR_UPLOAD'] = $_CFG['URL_ROOT'] . 'upload/jseditor/';  //Kindeditor文件上传目录的相对地址
    3. 程序中调用 KE 对象并调用其成员函数使用


实例代码：
    1. 使用Javascript创建编辑器
         // HTML代码
         <textarea name="content" id="wfmc-content"></textarea>

         // Javascript代码
         KE.init({'id':'wfmc-content'});
         KE.create('wfmc-content');