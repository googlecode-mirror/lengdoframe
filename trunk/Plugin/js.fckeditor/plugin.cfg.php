功能说明：
    FCKeditor编辑器


使用说明：
    1. 拷贝 所有文件 到 /plugin/js.fckeditor/ 文件夹下
    2. 在 /includes/systemconfig.php 文件中配置编辑器的路径信息
          $_CFG['DIR_JSEDITOR_UPLOAD'] = $_CFG['DIR_ROOT']   . 'upload/jseditor/';        //FCKeditor文件上传目录的绝对地址
          $_CFG['URL_JSEDITOR_UPLOAD'] = $_CFG['URL_ROOT']   . 'upload/jseditor/';        //FCKeditor文件上传目录的相对地址
          $_CFG['URL_JSEDITOR_FOLDER'] = $_CFG['URL_PLUGIN'] . 'js.fckeditor/fckeditor/'; //FCKeditor核心文件夹相对地址
    3. 在程序中加载 /plugin/js.fckeditor/fckeditor.php 文件
    4. 程序中构建 Fckeditor 对象调用其成员函数使用


实例代码：
    1. 使用PHP构建编辑器
         require_once($_CFG['DIR_PLUGIN'].'js.fckeditor/fckeditor.php');

         $fck = new FCKeditor( $name, array('value'=>$content) );
         echo $fck->CreateHtml();