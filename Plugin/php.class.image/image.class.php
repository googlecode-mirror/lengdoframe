<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 图片处理类
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


class Image
{
    var $hSrc;                      // 图片资源句柄
    var $hDst;                      // 新图句柄
    var $hMask;                     // 水印句柄

    var $sSrcImg;                   // 源文件
    var $sDstImg;                   // 目标文件

    var $sImgType;                  // 文件类型
    var $iFontSize;                 // 尺寸

    var $iImgScale = 0;             // 图片缩放比例
    var $iImgCreateQuality  = 75;   // 图片生成质量
    var $iImgDisplayQuality = 75;   // 图片显示质量,默认为80

    var $iSrcW = 0;                 // 原图宽度
    var $iSrcH = 0;                 // 原图高度
    var $iDstW = 0;                 // 新图总宽度
    var $iDstH = 0;                 // 新图总高度

    var $iEndX;                     // 新图绘制结束横坐标
    var $iEndY;                     // 新图绘制结束纵坐标
    var $iFillW;                    // 填充图形宽
    var $iFillH;                    // 填充图形高
    var $iStartX;                   // 新图绘制起始横坐标
    var $iStartY;                   // 新图绘制起始纵坐标

    var $iMaskW;                    // 水印宽
    var $iMaskH;                    // 水印高
    var $sMaskImg;                  // 水印图片
    var $sMaskWord;                 // 水印文字
    var $iMaskFontW;                // 水印字体宽
    var $iMaskFontH;                // 水印字体高
    var $mMaskFont      = 2;        // 水印字体
    var $iMaskPosX      = 0;        // 水印横坐标
    var $iMaskPosY      = 0;        // 水印纵坐标
    var $iMaskImgPct    = 50;       // 图片合并程度,值越大，合并程序越低
    var $iMaskTxtPct    = 50;       // 文字合并程度,值越小，合并程序越低
    var $iMaskOffsetX   = 5;        // 水印横向偏移
    var $iMaskOffsetY   = 5;        // 水印纵向偏移
    var $iMaskPosition  = 0;        // 水印位置
    var $sMaskFontColor = "green";  // 水印文字颜色

    var $sImgBorderColor;           // 图片边框颜色
    var $iImgBorderSize = 0;        // 图片边框尺寸

    var $_flip_x = 0;               // 水平翻转次数
    var $_flip_y = 0;               // 垂直翻转次数

    /* 文件类型定义,并指出了用于生成和输出图片的函数 */
    var $aImgTypes = array(
        'jpg'  => array('create' => 'Imagecreatefromjpeg', 'output' => 'imagejpeg' ),
        'gif'  => array('create' => 'Imagecreatefromgif' , 'output' => 'imagegif'  ),
        'png'  => array('create' => 'imagecreatefrompng' , 'output' => 'imagepng'  ),
        'jpeg' => array('create' => 'ImagecreateFromjpeg', 'output' => 'imagejpeg' )
    );


    /**
     * 构造函数
     */
    function Image()
    {
        /* 内存限制50M */
        ini_set('memory_limit', '50M');
        
        /* 初始化 */
        $this->mMaskFont = 2;
        $this->iFontSize = 12;
        $this->sMaskFontColor = '#ffffff';
    }

    /**
     * 取得图片的宽
     */
    function getImgWidth( $src )
    {
        return imagesx($src);
    }

    /**
     * 取得图片的高
     */
    function getImgHeight( $src )
    {
        return imagesy( $src );
    }

    /**
     * 设置图片生成路径
     * 自动设置源图像文件的句柄，宽度和高度
     *
     * @params str $src_img  源图片路径
     */
    function setSrcImg( $src_img )
    {
        /* 文件存在性检查 */
        file_exists($src_img) ? ($this->sSrcImg = $src_img) : die("图片不存在");
        
        /* 文件扩展名检查 */
        $this->sImgType = $this->_getPostfix($this->sSrcImg);

        $this->_checkValid($this->sImgType);
        
        /* 变量赋值 */
        $img_type  = $this->sImgType;
        $func_name = $this->aImgTypes[$img_type]['create'];

        if( function_exists($func_name) ){
            $this->hSrc  = $func_name($this->sSrcImg);
            $this->iSrcW = $this->getImgWidth($this->hSrc);
            $this->iSrcH = $this->getImgHeight($this->hSrc);
        }else{
            die($func_name."函数不被支持");
        }
    }

    /**
     * 设置图片生成路径
     * 自动创建目标文件所需的目录
     *
     * @params str  $dst_img  图片生成路径
     */
    function setDstImg( $dst_img )
    {
        /* 路径解析 */
        $arr  = explode('/', $dst_img);
        $last = array_pop($arr);
        $path = implode('/', $arr);
        
        /* 创建目录 */
        $this->_mkdirs($path);
        
        /* 变量赋值 */
        $this->sDstImg = $dst_img;
    }

    /**
     * 设置图片的显示质量
     *
     * @params str  $n  质量
     */
    function setImgDisplayQuality($n)
    {
        $this->iImgDisplayQuality = intval($n);
    }

    /**
     * 设置图片的生成质量
     *
     * @params int  $n  质量
     */
    function setImgCreateQuality( $n )
    {
        $this->iImgCreateQuality = intval($n);
    }

    /**
     * 设置水印文字
     *
     * @params str  $word  水印文字
     */
    function setMaskWord( $word )
    {
        $this->sMaskWord = $word;
    }

    /**
     * 设置水印字体颜色
     *
     * @params str  $color  字体颜色
     */
    function setMaskFontColor( $color = '#ffffff' )
    {
        $this->sMaskFontColor = $color;
    }

    /**
     * 设置水印字体
     *
     * @params mix  $font  字体
     */
    function setMaskFont( $font = 2 )
    {
        if( !is_numeric($font) && !file_exists($font) ){
            die("字体文件不存在");
        }

        $this->mMaskFont = $font;
    }

    /**
     * 设置文字字体大小，仅对truetype字体有效
     */
    function setMaskFontSize( $size = 12 )
    {
        $this->iFontSize = $size;
    }

    /**
     * 设置图片水印
     *
     * @params str  $img  水印图片源
     */
    function setMaskImg( $img )
    {
        $this->sMaskImg = $img;
    }

    /**
     * 设置水印横向偏移
     *
     * @params int  $x  横向偏移量
     */
    function setMaskOffsetX( $x )
    {
        $this->iMaskOffsetX = intval($x);
    }

    /**
     * 设置水印纵向偏移
     *
     * @params int  $y  纵向偏移量
     */
    function setMaskOffsetY( $y )
    {
        $this->iMaskOffsetY = intval($y);
    }

    /**
     * 指定水印位置
     *
     * @params int  $position  位置,1:左上,2:左下,3:右上,0/4:右下
     */
    function setMaskPosition( $position = 0 )
    {
        $this->iMaskPosition = intval($position);
    }

    /**
     * 设置图片合并程度
     *
     * @params int  $n  合并程度
     */
    function setMaskImgPct( $n )
    {
        $this->sMaskImgPct = intval($n);
    }

    /**
     * 设置文字合并程度
     *
     * @params int  $n  合并程度
     */
    function setMaskTxtPct( $n )
    {
        $this->iMaskTxtPct = intval($n);
    }

    /**
     * 设置缩略图边框
     */
    function setDstImgBorder( $size = 1, $color = '#FFFFFF' )
    {
        $this->iImgBorderSize  = intval($size);
        $this->sImgBorderColor = $color;
    }

    /**
     * 水平翻转
     */
    function flipH()
    {
        $this->_flip_x++;
    }

    /**
     * 垂直翻转
     */
    function flipV()
    {
        $this->_flip_y++;
    }

    /**
     * 创建图片，主函数
     *
     * @params mix  $img_w  目标宽度，宽度类型为float时，将采用百分比缩放图片
     * @params int  $img_h  目标高度，如果为null，那么将采用宽度的缩放比例（仅当$img_w类型为int时）
     */
    function createImg( $img_w, $img_h = null )
    {
        /* 设置新图尺寸 */
        $this->_setNewImgSize($img_w, $img_h);

        if( $this->_flip_x%2 != 0 ) $this->_flipH($this->hSrc);
        if( $this->_flip_y%2 != 0 ) $this->_flipV($this->hSrc);

        $this->_createMask();
        $this->_output();

        /* 释放 */
        imagedestroy($this->hSrc);
        imagedestroy($this->hDst);
    }

    /**
     * 生成目标图片并生成水印
     * 调用了生成水印文字和水印图片两个方法
     */
    function _createMask()
    {
        /* 创建新图并增加边框 */
        $this->hDst = imagecreatetruecolor($this->iDstW, $this->iDstH);
        $this->_drawBorder();

        /* 文字水印 */
        if( $this->sMaskWord ){
            /* 获取字体信息 */
            $this->_setFontInfo();

            if( $this->_isFull() ) die("水印文字过大");

            imagecopyresampled( $this->hDst    , $this->hSrc    ,
                                $this->iStartX , $this->iStartY ,
                                0              , 0              ,
                                $this->iEndX   , $this->iEndY   ,
                                $this->iSrcW   , $this->iSrcH   );

            $this->_createMaskWord($this->hDst);
        }
        
        /* 图片水印 */
        if( $this->sMaskImg ){
            $this->_loadMaskImg();//加载时，取得宽高

            if($this->_isFull()){
                /* 将水印生成在原图上再拷 */
                $this->_createMaskImg($this->hSrc);

                imagecopyresampled( $this->hDst    , $this->hSrc    ,
                                    $this->iStartX , $this->iStartY ,
                                    0              , 0              ,
                                    $this->iEndX   , $this->iEndY   ,
                                    $this->iSrcW   , $this->iSrcH   );
            }else{
                imagecopyresampled( $this->hDst    , $this->hSrc    ,
                                    $this->iStartX , $this->iStartY ,
                                    0              , 0              ,
                                    $this->iEndX   , $this->iEndY   ,
                                    $this->iSrcW   , $this->iSrcH   );

                $this->_createMaskImg($this->hDst);
            }
        }

        /* 无水印 */
        if( empty($this->sMaskWord) && empty($this->sMaskImg) ){
            imagecopyresampled( $this->hDst    , $this->hSrc    ,
                                $this->iStartX , $this->iStartY ,
                                0              , 0              ,
                                $this->iEndX   , $this->iEndY   ,
                                $this->iSrcW   , $this->iSrcH   );
        }
    }

    /**
     * 画边框
     */
    function _drawBorder()
    {
        if( !empty($this->iImgBorderSize) ){
            $c = $this->_parseColor($this->sImgBorderColor);

            $color = ImageColorAllocate($this->hSrc, $c[0], $c[1], $c[2]);

            imagefilledrectangle($this->hDst, 0, 0, $this->iDstW, $this->iDstH, $color);// 填充背景色
        }
    }

    /**
     * 生成水印文字
     */
    function _createMaskWord( $src )
    {
        $this->_countMaskPos();
        $this->_checkMaskValid();

        $c = $this->_parseColor($this->sMaskFontColor);
        $color = imagecolorallocatealpha($src, $c[0], $c[1], $c[2], $this->iMaskTxtPct);

        if( is_numeric($this->mMaskFont) ){
            imagestring( $src             , $this->mMaskFont ,
                         $this->iMaskPosX , $this->iMaskPosY ,
                         $this->sMaskWord , $color);
        }else{
            imagettftext($src, $this->iFontSize , 0,
                        $this->iMaskPosX , $this->iMaskPosY,
                        $color, $this->mMaskFont, $this->sMaskWord);
        }
    }

    /**
     * 生成水印图
     */
    function _createMaskImg($src)
    {
        $this->_countMaskPos();
        $this->_checkMaskValid();

        imagecopymerge( $src, $this->hMask, $this->iMaskPosX, 
                        $this->iMaskPosY, 0, 0, $this->iMaskW, 
                        $this->iMaskH, $this->sMaskImgPct);

        imagedestroy($this->hMask);
    }

    /**
     * 加载水印图
     */
    function _loadMaskImg()
    {
        $mask_type = $this->_getPostfix($this->sMaskImg);

        $this->_checkValid($mask_type);

        $func_name = $this->aImgTypes[$mask_type]['create'];

        if( function_exists($func_name) ){
            $this->hMask  = $func_name($this->sMaskImg);
            $this->iMaskW = $this->getImgWidth($this->hMask);
            $this->iMaskH = $this->getImgHeight($this->hMask);
        }else{
            die($func_name."函数不被支持");
        }
    }

    /**
     * 图片输出
     */
    function _output()
    {
        $img_type  = $this->sImgType;
        $func_name = $this->aImgTypes[$img_type]['output'];

        if( function_exists($func_name) ){
           // 判断浏览器,若是IE就不发送头
           if( isset($_SERVER['HTTP_USER_AGENT']) ){
               $ua = strtoupper($_SERVER['HTTP_USER_AGENT']);

               if( !preg_match('/^.*MSIE.*\)$/i',$ua) ){
                   header("HTTP/1.1 202 Accepted");
                   header("Content-type:$img_type");
               }
           }

           $func_name($this->hDst, $this->sDstImg, $this->iImgDisplayQuality);
        }else{
            return false;
        }
    }

    /**
     * 分析颜色
     *
     * @params str  $color  十六进制颜色
     */
    function _parseColor( $color )
    {
        $arr = array();

        for( $i = 1; $i < strlen($color); $i++ ){
            $arr[] = hexdec( substr($color,$i++,2) );
        }

        return $arr;
    }

    /**
     * 计算出位置坐标
     */
    function _countMaskPos()
    {
        if( $this->_isFull() ){
           switch( $this->iMaskPosition ){
               case 1:
                   // 左上
                   $this->iMaskPosX = $this->iMaskOffsetX + $this->iImgBorderSize;
                   $this->iMaskPosY = $this->iMaskOffsetY + $this->iImgBorderSize;
                   break;

               case 2:
                   // 左下
                   $this->iMaskPosX = $this->iMaskOffsetX + $this->iImgBorderSize;
                   $this->iMaskPosY = $this->iSrcH - $this->iMaskH - $this->iMaskOffsetY;
                   break;


               case 3:
                   // 右上
                   $this->iMaskPosX = $this->iSrcW - $this->iMaskW - $this->iMaskOffsetX;
                   $this->iMaskPosY = $this->iMaskOffsetY + $this->iImgBorderSize;
                   break;

               case 4:
                   // 右下
                   $this->iMaskPosX = $this->iSrcW - $this->iMaskW - $this->iMaskOffsetX;
                   $this->iMaskPosY = $this->iSrcH - $this->iMaskH - $this->iMaskOffsetY;
                   break;

               default:
                   // 默认将水印放到右下,偏移指定像素
                   $this->iMaskPosX = $this->iSrcW - $this->iMaskW - $this->iMaskOffsetX;
                   $this->iMaskPosY = $this->iSrcH - $this->iMaskH - $this->iMaskOffsetY;
                   break;
           }
        }else{
            switch( $this->iMaskPosition ){
               case 1:
                   // 左上
                   $this->iMaskPosX = $this->iMaskOffsetX + $this->iImgBorderSize;
                   $this->iMaskPosY = $this->iMaskOffsetY + $this->iImgBorderSize;
                   break;

               case 2:
                   // 左下
                   $this->iMaskPosX = $this->iMaskOffsetX + $this->iImgBorderSize;
                   $this->iMaskPosY = $this->iDstH - $this->iMaskH - $this->iMaskOffsetY - $this->iImgBorderSize;
                   break;

               case 3:
                   // 右上
                   $this->iMaskPosX = $this->iDstW - $this->iMaskW - $this->iMaskOffsetX - $this->iImgBorderSize;
                   $this->iMaskPosY = $this->iMaskOffsetY + $this->iImgBorderSize;
                   break;

               case 4:
                   // 右下
                   $this->iMaskPosX = $this->iDstW - $this->iMaskW - $this->iMaskOffsetX - $this->iImgBorderSize;
                   $this->iMaskPosY = $this->iDstH - $this->iMaskH - $this->iMaskOffsetY - $this->iImgBorderSize;
                   break;

               default:
                   // 默认将水印放到右下,偏移指定像素
                   $this->iMaskPosX = $this->iDstW - $this->iMaskW - $this->iMaskOffsetX - $this->iImgBorderSize;
                   $this->iMaskPosY = $this->iDstH - $this->iMaskH - $this->iMaskOffsetY - $this->iImgBorderSize;
                   break;
           }       
        }
    }

    /**
     * 设置字体信息
     */
    function _setFontInfo()
    {
        if( is_numeric($this->mMaskFont) ){
            $this->iMaskFontW = imagefontwidth($this->mMaskFont);
            $this->iMaskFontH = imagefontheight($this->mMaskFont);

            // 计算水印字体所占宽高
            $word_length  = strlen($this->sMaskWord);
            $this->iMaskW = $this->iMaskFontW*$word_length;
            $this->iMaskH = $this->iMaskFontH;
        }else{
            $arr = imagettfbbox ($this->iFontSize, 0, $this->mMaskFont, $this->sMaskWord);
            $this->iMaskW = abs($arr[0] - $arr[2]);
            $this->iMaskH = abs($arr[7] - $arr[1]);
        }
    }

    /**
     * 设置新图尺寸
     *
     * @params mix  $img_w  目标宽度，宽度类型为float时，将采用百分比缩放图片
     * @params int  $img_h  目标高度，如果为null，那么将采用宽度的缩放比例（仅当$img_w类型为int时）
     */
    function _setNewImgSize( $img_w, $img_h = null )
    {
        if( is_float($img_w) ){
            $this->iImgScale = $img_w;  // 宽度作为比例

            $this->iFillW = round($this->iSrcW * $this->iImgScale / 1) - $this->iImgBorderSize*2;
            $this->iFillH = round($this->iSrcH * $this->iImgScale / 1) - $this->iImgBorderSize*2;
        }
        elseif( $img_h === null ){
            $fill_w = intval($img_w) - $this->iImgBorderSize*2;

            if( $fill_w < 0 ) die("图片边框过大，已超过了图片的宽度");

            $scale = $fill_w/$this->iSrcW;

            $this->iFillW = round($this->iSrcW * $scale);
            $this->iFillH = round($this->iSrcH * $scale);
        }
        else{
            $fill_w = intval($img_w) - $this->iImgBorderSize*2;
            $fill_h = intval($img_h) - $this->iImgBorderSize*2;

            if( $fill_w < 0 || $fill_h < 0 ) die("图片边框过大，已超过了图片的宽度");            

            $this->iFillW = intval($img_w);
            $this->iFillH = intval($img_h);
        }

        $this->iDstW = $this->iFillW + $this->iImgBorderSize*2;
        $this->iDstH = $this->iFillH + $this->iImgBorderSize*2;

        $this->iStartX = $this->iImgBorderSize;
        $this->iStartY = $this->iImgBorderSize;
        $this->iEndX   = $this->iFillW;
        $this->iEndY   = $this->iFillH;
    }

    /**
     * 检查水印图是否大于生成后的图片宽高
     */
    function _isFull()
    {
        return ($this->iMaskW + $this->iMaskOffsetX > $this->iFillW || $this->iMaskH + $this->iMaskOffsetY > $this->iFillH ) ? true : false;
    }

    /**
     * 检查水印图是否超过原图
     */
    function _checkMaskValid()
    {
        if( $this->iMaskW + $this->iMaskOffsetX > $this->iSrcW || $this->iMaskH + $this->iMaskOffsetY > $this->iSrcH ){ 
            die("水印图片尺寸大于原图，请缩小水印图"); 
        }
    }

    /**
     * 取得文件后缀，作为类成员
     */
    function _getPostfix( $filename )
    {
        return substr( strrchr(trim(strtolower($filename)), "."), 1 );
    }

    /**
     * 检查图片类型是否合法,调用了array_key_exists函数，此函数要求
     * php版本大于4.1.0
     *
     * @params str  $img_type  文件类型
     */
    function _checkValid( $img_type )
    {
        if( !array_key_exists($img_type, $this->aImgTypes) ){
            return false;
        }
    }

    /**
     * 按指定路径生成目录
     *
     * @params str  $path  路径
     */
    function _mkdirs( $path )
    {
        /* 无效参数 */
        if( !trim($path) ) return;

        /* 分解路径 */
        $adir = explode('/', $path);
        
        $dirlist = '';
        $rootdir = array_shift($adir);

        if( $rootdir != '' && $rootdir != '.' && $rootdir != '..' && !file_exists($rootdir) ){
            mkdir($rootdir);
        }

        foreach( $adir as $key => $val ){
            if( $val == '.' || $val == '..' ) continue;

            $dirlist .= '/'.$val;
            $dirpath  = $rootdir.$dirlist;

            if( !file_exists($dirpath) ){
                mkdir($dirpath);
                chmod($dirpath,0777);
            }
        }
    }
   
   /**
    * 水平翻转
    *
    * @params str  $path  路径
    */
    function _flipV( $src )
   {
       $src_x = $this->getImgWidth($src);
       $src_y = $this->getImgHeight($src);

       $new_im = imagecreatetruecolor($src_x, $src_y);

       for( $x = 0; $x < $src_x; $x++ ){
           for( $y = 0; $y < $src_y; $y++ ){
               imagecopy($new_im, $src, $x, $src_y - $y - 1, $x, $y, 1, 1);
           }
       }

       $this->hSrc = $new_im;
   }

   function _flipH($src)
   {
       $src_x = $this->getImgWidth($src);
       $src_y = $this->getImgHeight($src);

       $new_im = imagecreatetruecolor($src_x, $src_y);

       for( $x = 0; $x < $src_x; $x++ ){
           for($y = 0; $y < $src_y; $y++){
               imagecopy($new_im, $src, $src_x - $x - 1, $y, $x, $y, 1, 1);
           }
       }

       $this->hSrc = $new_im;
   }

   /**
    * 取得某一点的颜色值
    */
   function _getPixColor($src, $x, $y)
   {
       $rgb   = @ImageColorAt($src, $x, $y);
       $color = imagecolorsforindex($src, $rgb);
       
       Return $color;
   }
}
?>