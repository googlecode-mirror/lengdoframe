<?php
/* 初始化 */
$_PLUGIN_CFG = array();


/* 索引说明 */
$_PLUGIN_CFG['file']   = 'fckeditor.php';
$_PLUGIN_CFG['title']  = 'FCKeditor编辑器';


/* 使用说明 */
$_PLUGIN_CFG['help'][] = '拷贝 所有文件 到 /plugin/js.fckeditor/ 文件夹下';
$_PLUGIN_CFG['help'][] = '在 /includes/systemconfig.php 文件中配置编辑器的路径信息
                             $_CFG["DIR_JSEDITOR_UPLOAD"] = $_CFG["DIR_ROOT"]   . "upload/jseditor/";        //FCKeditor文件上传目录的绝对地址
                             $_CFG["URL_JSEDITOR_UPLOAD"] = $_CFG["URL_ROOT"]   . "upload/jseditor/";        //FCKeditor文件上传目录的相对地址
                             $_CFG["URL_JSEDITOR_FOLDER"] = $_CFG["URL_PLUGIN"] . "js.fckeditor/fckeditor/"; //FCKeditor核心文件夹相对地址      
                         ';
$_PLUGIN_CFG['help'][] = '在程序中加载 /plugin/js.fckeditor/fckeditor.php 文件';
$_PLUGIN_CFG['help'][] = '在程序中构建 Fckeditor 对象调用其成员函数使用';


/* 实例代码 */
$_PLUGIN_CFG['example'][] = '使用PHP构建编辑器
                                 require_once($_CFG["DIR_PLUGIN"]."js.fckeditor/fckeditor.php");

                                 $fck = new FCKeditor( $name, array("value"=>$content) );
                                 echo $fck->CreateHtml();
                            ';
?>