<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 图像验证码类
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


class VCode{
    /* 图像句柄 */
    var $hImg;

    /* 生成的验证码 */
    var $sCode;

    /* 验证图片的宽度和高度 */
    var $iWidth;
    var $iHeight;

    /* 字体个数，字体文件，字体大小，字体类型 */
    var $iFontLen;
    var $sFontFile;
    var $iFontSize;
    var $sFontType;


    /**
     * 构造函数
     *
     * @params int   $width   图像宽度
     * @params int   $height  图像高度
     * @params int   $fonts   字体属性
     *               $fonts['len']   字体个数
     *               $fonts['type']  字体类型( ENL小写，ENU大写，EN单词，NUM数字 )
     *               $fonts['size']  字体大小
     */
    function VCode( $width = 60, $height = 20, $fonts = array() )
    {
        /* 验证码 */
        $this->sCode = '';

        /* 配置图片信息 */
        $this->iWidth  = $width;
        $this->iHeight = $height;

        /* 字体信息 */
        $this->iFontLen  = intval($fonts['len'])  ? intval($fonts['len'])  : 4;
        $this->iFontSize = intval($fonts['size']) ? intval($fonts['size']) : 15;
        $this->sFontType = in_array($fonts['type'], array('ENL','ENU','EN','NUM')) ? $fonts['type'] : 'NUM';
        $this->sFontFile = DIR_ROOT . 'includes/font/candarai.ttf';
    }

    /**
     * 创建图像并输出
     */
    function image()
    {
        /* 初始化图像 */
        $this->initImg();

        /* 渲染图像 */
        $this->addNoises(100);
        $this->addDashed(150);

        /* 验证码嵌入 */
        $this->setCode();
        $this->setSession();
        $this->drawCode();

        /* 增加边框 */
        $this->addBorder();

        /* 输出 */
        ImagePNG($this->hImg);
        ImageDestroy($this->hImg);
    }

    /**
     * 验证码检查
     */
    function check( $code )
    {
        if( empty($code) ) return false;

        return $code == $_SESSION['VCODE_RAND_CODE'];
    }

    /**
     * 初始化图像
     *
     */
    function initImg()
    {
        $this->hImg = imagecreate($this->iWidth, $this->iHeight);

        imagecolorallocate($this->hImg, 255, 255, 255);
    }

    /**
     * 为图像增加噪点
     *
     * @params int  $num  噪点个数
     */
    function addNoises( $num )
    {
        $width  = imagesx($this->hImg);
        $height = imagesy($this->hImg);
        $color  = imagecolorallocate($this->hImg, 99, 131, 125);

        for( $i=0; $i < $num; $i++ ){
            imagesetpixel($this->hImg, mt_rand(0,$width), mt_rand(0,$height), $color);
        }
    }

    /**
     * 增加边框
     *
     * @params int  $num  噪点个数
     */
    function addBorder(){
        $border = imagecolorallocate($this->hImg, 127, 157, 185);

        imagerectangle($this->hImg, 0, 0, $this->iWidth-1, $this->iHeight-1, $border);
    }

    /**
     * 为图像增加干扰虚线
     *
     * @params int  $num  干扰线条数
     */
    function addDashed( $num )
    {
        $width  = imagesx($this->hImg);
        $height = imagesy($this->hImg);

        for( $i=0; $i < $num; $i++ ){
            $color = imagecolorallocate( $this->hImg, rand(100,255), rand(100,255), rand(100,255) );

            imagedashedline($this->hImg, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $color);
        }
    }

    /**
     * 验证码字符输出到图片
     */
    function drawCode()
    {
        $len = strlen($this->sCode);

        for( $i = 0; $i < $len; $i++ ){
            /* 字体随机大小 */
            $font_size =rand($this->iFontSize-2, $this->iFontSize+3);

            /* 字体随机颜色 */
            $font_color = imagecolorallocate( $this->hImg, rand(15, 100), rand(15, 100), rand(15, 100) );

            /* 字体随机角度 */
            $angle = rand(-20, 20);

            /* 取得每次的位置 */
            $x = ceil($this->iWidth/20) + ($this->iWidth-$this->iWidth/8)/$len*$i;

            /* 取得每次的高度 */
            $y = rand($this->iHeight-5, $this->iHeight-7);

            imagettftext($this->hImg, $font_size, $angle, $x + 5, $y, $font_color, $this->sFontFile, $this->sCode{$i});
        }
    }

    /**
     * 设置验证码的随机字符
     */
    function setCode()
    {
        switch( strtoupper($this->sFontType) ){
            case 'EN' :     $str_seed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';           break;
            case 'NUM':     $str_seed = '0123456789';                                                     break;
            case 'ENL':     $str_seed = 'abcdefghijklmnopqrstuvwxyz';                                     break;
            case 'ENU':     $str_seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';                                     break;
            case 'EN_NUM':  $str_seed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; break;
            case 'ENL_NUM': $str_seed = 'abcdefghijklmnopqrstuvwxyz0123456789';                           break;
            case 'ENU_NUM': $str_seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';                           break;
        }

        $len = strlen($str_seed);

        for( $i = 0; $i < $this->iFontLen; $i++ ) {
           $this->sCode .= substr($str_seed, mt_rand(0, $len-1), 1);
        }
    }

    /**
     * 设置SESSION
     */
    function setSession()
    {
        @session_start();

        $_SESSION['VCODE_RAND_CODE'] = $this->sCode;
    }

    /**
     * 销毁SESSION
     */
    function destroySession()
    {
        unset($_SESSION['VCODE_RAND_CODE']);
    }
}
?>