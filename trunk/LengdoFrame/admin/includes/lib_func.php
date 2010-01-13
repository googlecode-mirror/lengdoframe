<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 后台公用函数库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 系统消息
/* ------------------------------------------------------ */

/**
 * 系统消息
 *
 * @params str  $msg  消息
 */
function sys_msg( $msg )
{
    $html = '<body style="margin:0"><div style="text-align:center;border:1px solid #ccc;background:#ffffe1;';
    $html.= 'padding:20px;margin:5px;font:12px Garamonds,Tahoma;color:#777;font-weight:blod;">'. $msg .'</div></body>';

    exit($html);
}


/* ------------------------------------------------------ */
// - 组合框
/* ------------------------------------------------------ */

/**
 * 时间组合框
 *
 * @params str  $name     组合框名称
 * @params arr  $attribs  组合件属性
 *         arr            $attribs['button']   按钮属性
 *         arr            $attribs['textbox']  文本框属性
 * @params arr  $configs  组合框配置
 */
function timecbox( $name, $attribs = array(), $configs = array() )
{
    /* 无效参数 */
    if( !is_string($name) || $name == '' ) return '';

    /* 初始化参数 */
    if( !is_array($configs) ) $configs = array();

    /* 初始化参数 - 文本框属性 */
    if( !is_array($attribs['textbox']) ) $attribs['textbox'] = array();
    $attribs['textbox'] = array_merge( array('class'=>'textbox','style'=>'width:70px'), $attribs['textbox'] );
    $attribs['textbox'] = array_merge( $attribs['textbox'], array('name'=>$name, 'type'=>'text') );

    /* 构建HTML - 文本框属性 */
    $html = '<div class="timecbox"><input';
    foreach( $attribs['textbox'] AS $attrib=>$value ){
        $html.= ' '. ( $value===true ? $attrib : ($value===false?'':($attrib.'="'.$value.'"')) );
    }
    $html.= '/>';

    /* 构建HTML - 按钮及配置 */
    $html.= '<input type="button" onmouseover="combobox_mouseover(this)" class="choice" onclick="timecbox_cal(this,';
    $html.= str_replace('"',"'",json_encode($configs)) .')"/></div>';

    /* 返回 */
    return $html;
}

/**
 * 文件组合框
 *
 * @params str  $name     组合框名称
 * @params arr  $attribs  组合件属性
 *         arr            $attribs['del']      删除按钮属性
 *         arr            $attribs['view']     查看按钮属性
 *         arr            $attribs['upload']   上传按钮属性
 *         arr            $attribs['choice']   选择按钮属性
 *         arr            $attribs['filebox']  文件域属性
 */
function filecbox( $name, $attribs = array() )
{
    /* 无效参数 */
    if( !is_string($name) || $name == '' ) return '';

    /* 初始化参数 */
    if( !is_array($configs) ) $configs = array();
    if( !is_array($attribs) ) $attribs = array();

    /* 初始化参数 - 文件域遮掩层和文件域属性 */
    if( !is_array($attribs['textbox']) ) $attribs['textbox'] = array();
    $attribs['textbox'] = array_merge( array('class'=>'textbox','readonly'=>true), $attribs['textbox'] );
    $attribs['textbox'] = array_merge( $attribs['textbox'], array('type'=>'text') );

    if( !is_array($attribs['overlay']) ) $attribs['overlay'] = array();
    $attribs['overlay'] = array_merge( $attribs['overlay'], array('onmouseover'=>'combobox_mouseover(this)','class'=>'overlay','href'=>'javascript:void(0)') );

    if( !is_array($attribs['filebox']) ) $attribs['filebox'] = array();
    $attribs['filebox'] = array_merge( $attribs['filebox'], array('name'=>$name,'type'=>'file','class'=>'filebox','size'=>'1') );

    /* 构建HTML */
    $html = '<div class="filecbox">';

    /* 构建HTML - 文件框 */
    $html.= '<input';
    foreach( $attribs['textbox'] AS $attrib=>$value ){
        $html.= ' '. ( $value===true ? $attrib : ($value===false?'':($attrib.'="'.$value.'"')) );
    }
    $html.= '/>';

    /* 构建HTML - 文件域清除按钮 */
    $html.= '<a class="clear" href="javascript:void(0)" onclick="filecbox_clear(this)" onmouseover="combobox_mouseover(this)" title="清除"></a>';

    /* 构建HTML - 文件域遮掩层和文件域 */
    $html.= '<a';
    foreach( $attribs['overlay'] AS $attrib=>$value ){
        $html.= ' '. $attrib .'="'. $value .'"';
    }
    $html.= '><input';
    foreach( $attribs['filebox'] AS $attrib=>$value ){
        $html.= ' '. ( $value===true ? $attrib : ($value===false?'':($attrib.'="'.$value.'"')) );
    }
    $html.= '/></a>';

    /* 构建HTML - 选择按钮 */
    if( is_array($attribs['choice']) ){
        /* 初始化参数 - 选择按钮属性 */
        $attribs['choice'] = array_merge( $attribs['choice'], array('class'=>'choice','href'=>'javascript:void(0)','onmouseover'=>'combobox_mouseover(this)') );

        /* 构建HTML - 选择按钮 */
        $html.= '<a';
        foreach( $attribs['choice'] AS $attrib=>$value ){
            $html.= ' '. $attrib .'="'. $value .'"';
        }
        $html.= '></a>';
    }

    /* 构建HTML - 上传按钮 */
    if( is_array($attribs['upload']) ){
        /* 初始化参数 - 文件域属性 */
        $attribs['upload'] = array_merge( $attribs['upload'], array('class'=>'upload','href'=>'javascript:void(0)','onmouseover'=>'combobox_mouseover(this)') );

        /* 构建HTML - 上传按钮 */
        $html.= '<a';
        foreach( $attribs['upload'] AS $attrib=>$value ){
            $html.= ' '. $attrib .'="'. $value .'"';
        }
        $html.= '></a>';
    }

    /* 构建HTML - 删除按钮和查看按钮 */
    if( is_array($attribs['del']) || is_array($attribs['view']) ){
        $html.= '<div class="uploaded">';
        
        /* 构建HTML - 删除按钮 */
        if( is_array($attribs['del']) ){
            /* 初始化参数 - 删除按钮属性 */
            $attribs['del'] = array_merge( $attribs['del'], array('class'=>'del','href'=>'javascript:void(0)','onmouseover'=>'combobox_mouseover(this)') );

            /* 构建HTML - 删除按钮 */
            $html.= '<a';
            foreach( $attribs['del'] AS $attrib=>$value ){
                $html.= ' '. $attrib .'="'. $value .'"';
            }
            $html.= '></a>';
        }

        /* 构建HTML - 查看按钮 */
        if( is_array($attribs['view']) ){
            /* 初始化参数 - 查看按钮属性 */
            $attribs['view'] = array_merge( $attribs['view'], array('class'=>'view','href'=>'javascript:void(0)','onmouseover'=>'combobox_mouseover(this)') );

            /* 构建HTML - 查看按钮 */
            $html.= '<a';
            foreach( $attribs['view'] AS $attrib=>$value ){
                $html.= ' '. $attrib .'="'. $value .'"';
            }
            $html.= '></a>';
        }

        $html.= '</div>';
    }

    /* 返回 */
    return $html;
}

/**
 * 数字步长组合框
 *
 * @params str  $name     组合框名称
 * @params arr  $attribs  组合件属性
 *         arr            $attribs['textbox']  文本框属性
 * @params arr  $configs  组合框配置
 *         mix            $configs.tstep  向上的步长
 *         mix            $configs.bstep  向下的步长
 *         mix            $configs.limit  上下限(步长大于0时为上限，小于0时为下限)
 *         mix            $configs.fixed  小数点后精度长度
 */
function numscbox( $name, $attribs = array(), $configs = array() )
{
    /* 无效参数 */
    if( !is_string($name) || $name == '' ) return '';

    /* 初始化参数 - 文本框属性 */
    if( !is_array($attribs['textbox']) ) $attribs['textbox'] = array();
    $attribs['textbox'] = array_merge( array('class'=>'textbox','style'=>'width:70px'), $attribs['textbox'] );
    $attribs['textbox'] = array_merge( $attribs['textbox'], array('name'=>$name, 'type'=>'text') );

    /* 构建HTML - 文本框属性 */
    $html = '<table class="numscbox" cellpadding="0" cellspacing="0"><tr><td><input';
    foreach( $attribs['textbox'] AS $attrib=>$value ){
        $html.= ' '. ( $value===true ? $attrib : ($value===false?'':($attrib.'="'.$value.'"')) );
    }    
    $html.= '/></td><td>';

    /* 初始化参数 */
    $config = array();
    if( is_numeric($configs['limit']) ) $config['limit'] = $configs['limit'];
    if( is_numeric($configs['fixed']) ) $config['fixed'] = $configs['fixed'];

    $config = str_replace('"',"'",json_encode($config));

    /* 构建HTML - 文本框属性 */
    $html.= '<a onmouseover="combobox_mouseover(this)" class="buttont" onclick="numscbox_calc(this,';
    $html.= (is_numeric($configs['tstep'])&&$configs['tstep']>=0?$configs['tstep']:1) .','. $config .')"></a>';
    $html.= '<a onmouseover="combobox_mouseover(this)" class="buttonb" onclick="numscbox_calc(this,';
    $html.= (is_numeric($configs['bstep'])&&$configs['bstep']<=0?$configs['bstep']:-1) .','. $config .')"></a>';
    $html.= '</td></tr></table>';

    return $html;
}

/**
 * 选择组合框
 *
 * @params str  $name     组合框名称
 * @params arr  $attribs  组合件属性
 *         arr            $attribs['button']   按钮属性
 *         arr            $attribs['textbox']  文本框属性
 * @params arr  $configs  组合框配置
 */
function choicecbox( $name, $attribs = array(), $configs = array() )
{
    /* 无效参数 */
    if( !is_string($name) || $name == '' ) return '';

    /* 初始化参数 - 文本框属性 */
    if( !is_array($attribs['textbox']) ) $attribs['textbox'] = array();
    $attribs['textbox'] = array_merge( array('class'=>'textbox'), $attribs['textbox'] );
    $attribs['textbox'] = array_merge( $attribs['textbox'], array('name'=>$name, 'type'=>'text') );

    /* 初始化参数 - 按钮属性 */
    if( !is_array($attribs['button']) ) $attribs['button'] = array();
    $attribs['button'] = array_merge( array('onmouseover'=>'combobox_mouseover(this)'), $attribs['button'] );
    $attribs['button'] = array_merge( $attribs['button'], array('class'=>'choice','type'=>'button') );

    /* 构建HTML - 文本框属性 */
    $html = '<div class="choicecbox"><input';
    foreach( $attribs['textbox'] AS $attrib=>$value ){
        $html.= ' '. ( $value===true ? $attrib : ($value===false?'':($attrib.'="'.$value.'"')) );
    }    
    $html.= '/>';

    /* 构建HTML - 按钮属性 */
    $html.= '<input';
    foreach( $attribs['button'] AS $attrib=>$value ){
        $html.= ' '. ( $value===true ? $attrib : ($value===false?'':($attrib.'="'.$value.'"')) );
    }
    $html.= '/></div>';

    /* 返回 */
    return $html;
}


/* ------------------------------------------------------ */
// - 文件类
/* ------------------------------------------------------ */

/**
 * 取出视图HTML代码
 *
 * @params str  $file  视图文件名
 * @params arr  $tpl   视图的数据变量
 */
function tpl_fetch( $file, $tpl )
{
    /* 初始化 */
    global $_LANG, $_DBD, $_CFG;

    /* 缓存开始 */
    ob_start();

    /* 加载视图 */
    include($_CFG['DIR_ADMIN_TPL'] . $file);

    /* 获取视HTML */
    $html = ob_get_contents();

    /* 缓存结束 */
    ob_end_clean();
    
    /* 返回 */
    return $html;
}

/**
 * 文件(夹)权限检查函数
 *
 * @params str  $file_path   文件(夹)路径
 *
 * @return int  返回8421码，false表示文件不存在
 *              如果是文件，8421码分别代表
 *                  文件可执行rename()函数、文件可改、可写、文件可读。
 *              如果是文件夹，8421码分别代表
 *                  目录下文件可执行rename()函数、目录下文件可改、目录可写、目录可读。
 */
function file_privilege( $file_path )
{
    /* 参数过滤 */
    $file_path = rtrim( str_replace("\\",'/',$file_path), '/ ' );

    /* 文件或文件夹不存在不存在 */
    if( !file_exists($file_path) ) return false;

    /* 文件权限属性初始化 */
    $mark = 0;

    /* windows操作系统 */
    if( strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ){
        /* 如果是目录 */
        if( is_dir($file_path) ){
            /* 检查目录是否可读 */
            $dir = @opendir($file_path);
            if( $dir === false ){
                return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
            }
            if( @readdir($dir) !== false ){
                $mark ^= 1; //目录可读 001，目录不可读 000
            }
            @closedir($dir);

            /* 测试文件 */
            $test_file = $file_path . '/_test.txt';

            /* 检查目录是否可写 */
            $fp = @fopen($test_file, 'wb');
            if( $fp === false ){
                return $mark; //如果目录中的文件创建失败，返回目录可读不可写 001。
            }
            if( @fwrite($fp, 'directory access testing.') !== false ){
                $mark ^= 2; //目录可写可读011
            }
            @fclose($fp);

            @unlink($test_file);

            /* 检查目录下文件是否可修改 */
            $fp = @fopen($test_file, 'ab+');
            if( $fp === false ){
                return $mark;
            }
            if( @fwrite($fp, "modify test.\r\n") !== false ){
                $mark ^= 4; //目录下文件可改可写可读111
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if( @rename($test_file, $test_file) !== false ){
                $mark ^= 8;
            }
            @unlink($test_file);
        }

        /* 如果是文件 */
        else if( is_file($file_path) ){
            /* 以读方式打开 */
            $fp = @fopen($file_path, 'rb');
            if( $fp ){
                $mark ^= 1; //可读 001
            }
            @fclose($fp);

            /* 试着修改文件 */
            $fp = @fopen($file_path, 'ab+');
            if( $fp && @fwrite($fp, '') !== false ){
                $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
            }
            @fclose($fp);

            /* 检查文件是否有执行rename()函数的权限 */
            if( @rename($file_path, $file_path) !== false ){
                $mark ^= 8;
            }
        }
    }

    /* 其他操作系统 */
    else{
        if( @is_readable($file_path) ){
            $mark ^= 1;
        }

        if( @is_writable($file_path) ){
            $mark ^= 14;
        }
    }

    return $mark;
}


/* ------------------------------------------------------ */
// - 管理员日志
/* ------------------------------------------------------ */

/**
 * 插入管理员日志
 *
 * @params str  $info  信息
 */
function admin_log( $info )
{
    $fields = array();

    $fields['ip'] = $_SERVER['REMOTE_ADDR'];
    $fields['info'] = addslashes(stripslashes($info));
    $fields['in_time'] = time();

    $fields['admin_id'] = admin_id();
    $fields['admin_name'] = addslashes(admin_name());
    $fields['admin_username'] = addslashes(admin_username());

    $GLOBALS['db']->insert( tname('admin_log'), $fields );
}


/* ------------------------------------------------------ */
// - 分页函数
/* ------------------------------------------------------ */

/**
 * 分页处理函数
 *
 * @params int  $rows_page    每页行数
 * @params int  $rows_total   总共行数
 * @params str  $pages_group  多少页号为一组
 *
 * @return str  分页HTML
 **/
function pager( $rows_page, $rows_total, $pages_group = 5 )
{
	/* 参数初始化 */
	$pages_total = floor(($rows_total+$rows_page-1)/$rows_page);
	$pages_total = $pages_total <= 0 ? 1 : $pages_total;

	/* 获得当前页 */
    $cur = pager_current($rows_page, $rows_total, $page_param);

    /* 构建HTML */

    /* 分析 */
    if( $cur > floor($pages_group/2) ){
        $beg = $cur - floor($pages_group/2);
        $end = $cur + floor($pages_group/2) - 1 + ceil($pages_group%2);

        if( $end > $pages_total ){
            $beg -= $end - $pages_total;
            $beg < 1 ? $beg = 1 : '';
        }
    }else{
        $beg = 1;
        $end = $pages_group;
    }

    /* 第一页 */
    if( $beg > 1 ){
        $_html .= '<a href="javascript:ListTable.pageTo(1)" class="first">1</a>';
        $_html .= '<a href="javascript:ListTable.pageTo('. ($cur-10<1?1:$cur-10) .')" class="first">..</a>&nbsp;';
    }

    /* 分页组 */
    for( $i=$beg; $i <= $end; $i++ ){
        if( $i > $pages_total ) break;

        $_html .= '<a href="javascript:ListTable.pageTo('. $i .')" class="';
        $_html .= $i == $cur ? 'on' : 'num';
        $_html .= '">'. $i .'</a>&nbsp;';
    }

    /* 尾页 */
    if( $end < $pages_total ){
        $_html .= '<a href="javascript:ListTable.pageTo('. ($cur+10>$pages_total?$pages_total:$cur+10) .')" class="last">..</a>';
        $_html .= '<a href="javascript:ListTable.pageTo('. $pages_total .')" class="last">'. $pages_total .'</a>';
    }

    /* 记录条数信息 */
    $_html .= '<a class="info">[ '. (($cur-1)*$rows_page+1) .'/'. $rows_total .' ]</a>';

    /* 返回 */
    return $_html;
}
/**
 * 取得当前页号
 */
function pager_current( $rows_page, $rows_total )
{
	/* 参数初始化 */
	$pages_total = floor(($rows_total+$rows_page-1)/$rows_page);
	$pages_total = $pages_total <= 0 ? 1 : $pages_total;

	/* 获得当前页 */
    if( intval($_REQUEST['page']) <= 0 ){
        return 1;
    }elseif( intval($_REQUEST['page']) > $pages_total ){
        return $pages_total;
    }else{
        return intval($_REQUEST['page']);
    }
}


/* ------------------------------------------------------ */
// - HTML文件下载头
/* ------------------------------------------------------ */

/**
 * HTTP导出
 *
 * @params str  $file     导出的文件名
 * @params str  $data     导出的数据
 * @params str  $oencode  输出编码，'UTF-8', 'GB2312'
 */
function http_download( $file, $data, $oencode = 'UTF-8' )
{
    /* 输出数据导出的文件头 */
    http_download_header($file);

    /* 编码并输出数据 */
    echo http_download_encode($data, $oencode); exit();
}
function http_download_header( $file )
{
    /* 文件扩展名 */
    $ext = end( explode('.',$file) );

    /* HTML文件头的内容类型 */
    $ctype = array();
    $ctype['sql'] = 'text/plain';
    $ctype['csv'] = 'application/vnd.ms-excel';

    /* 输出文件头 */
    header('Content-Type: '.$ctype[$ext]);                      //文件类型
    header('Content-disposition: attachment; filename='.$file); //文件名称
}
function http_download_encode( $str, $oencode, $iencode = 'UTF-8' )
{
    if( function_exists('mb_convert_encoding') ){
        return mb_convert_encoding($str, $oencode, $iencode);
    }

    if( function_exists('iconv') ){
        return iconv($iencode, $oencode.'//IGNORE', $str);
    }

    return $str;
}


/* ------------------------------------------------------ */
// - 列表导出
/* ------------------------------------------------------ */

/**
 * 列表数据导出
 *
 * @params str  $file  导出的文件名
 * @params arr  $rows  导出的数据集
 * @params str  $cols  要导出的列名
 */
function list_export( $file, $rows, $cols = '' )
{
    /* 初始化 */
    $str = '';

    /* 初始化列名 */
    $cols = empty($cols) ? array() : explode(',',$cols);

    /* 输出数据 */
    foreach( $rows AS $r ){
        if( empty($cols) ){
            $str .= '"'. implode('","', str_replace('"','""',$r)) ."\"\r\n";
        }else{
            foreach( $cols AS $col ){
                $str .= '"'. str_replace('"','""',$r[$col]) .'",';
            }
            $str = rtrim($str, ',')."\r\n";
        }
    }

    http_download($file, $str, 'GB2312');
}


/* ------------------------------------------------------ */
// - 列表排序图标
/* ------------------------------------------------------ */

/**
 * 构建排序图像HTML和访问这个HTML的变量名
 * 变量名格式：img_{$field}
 *
 * @params arr  $filter  过滤条件，含有order_fd，order_type值
 *
 * @return arr  
 */
function order_img( $filter )
{
    $flag['var'] = 'img_' . preg_replace('/^.*\./', '', $filter['order_fd']);
    $flag['img'] = '<span class="'. strtolower($filter['order_type']) .'"></span>';

    return $flag;
}
?>