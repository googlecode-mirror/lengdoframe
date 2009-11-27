<?php
/**
 * ����ͼƬ
 *
 * @params str  $folder     Դ�ļ������ļ���·��
 * @params str  $sfilename  Դ�ļ���(���$folder��·��)
 * @params str  $dfilename  Ŀ���ļ���(���$folder��·�� - ���Ϊ����ʹ��$sfile����Ŀ���ļ������ļ���׷��'_thumb')
 * @params mix  $width      Ŀ���ȣ��������Ϊfloatʱ�������ðٷֱ�����ͼƬ
 * @params int  $height     Ŀ��߶ȣ����Ϊnull����ô�����ÿ�ȵ����ű���������$img_w����Ϊintʱ��
 *
 * @return str  �����ļ���(�����$folder��·��)
 */
function thumb_pic( $folder, $sfilename, $dfilename = '', $width = 0.5, $height = null )
{
    /* �����ļ���·��ĩβб�� */
    $folder = rtrim(str_replace("\\","/",$folder), '/').'/';

    /* Դ�ļ����ڼ�� */
    if( !is_file($folder.$sfilename) ){
        return '';
    }

    /* ��ʼ��Ŀ���ļ�·�� */
    if( !trim($dfilename) ){
        $dfilename = thumb_pic_fname_append($sfilename, '_thumb');
    }

    /* ���ظ����� */
    require_once(DIR_ROOT . 'class/image.class.php');

    $img = new Image();

    $img->setSrcImg($folder.$sfilename);
    $img->setDstImg($folder.$dfilename);
    $img->createImg($width, $height);

    return $dfilename;
}

/**
 * ͼƬ�ļ���׷���ַ�
 *
 * @params str  $fname   �ļ����ƻ����ļ�·��������չ��
 * @params str  $append  Ҫ׷�ӵ��ַ�
 *
 * @return str  
 */
function thumb_pic_fname_append( $fname, $append )
{
    /* ��ʼ���ļ��� */
    $fname = trim($fname);

    /* ��Ч���ļ��� */
    if( !preg_match('/\.[a-zA-Z0-9]+$/', $fname) ) return $fname;

    /* �ֽ��ļ��� */
    $arr = explode('.', $fname);
    $arr[count($arr)-2] .= $append;

    /* �����ļ��������� */
    return implode('.', $arr);
}
?>