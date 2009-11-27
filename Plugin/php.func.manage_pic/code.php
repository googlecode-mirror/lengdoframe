<?php
/**
 * �ϴ�ͼƬ v1.0.0
 *
 * @params str  $folder  Ҫ�ϴ����ļ����ڵ��ļ���
 * @params arr  $fcname  �ļ����������
 *
 * @return str  �����ļ���(�����$folder��·��)
 */
function upload_pic( $folder, $fcname )
{
    /* �ļ�������� */
    if( !is_array($_FILES[$fcname]) || $_FILES[$fcname]['error'] != 0 || !$_FILES[$fcname]['size'] ) return '';

    /* �����ļ���·��ĩβб�� */
    $folder = rtrim(str_replace("\\",'/',$folder), '/') . '/';

    /* ����ļ���ʽ */
    $ext = strtolower( substr($_FILES[$fcname]['name'],-4) );
    if( !in_array($ext, array('.jpg','.gif')) ){
        sys_msg('��ЧͼƬ��ʽ');
    }

    /* �ϴ����ļ�Ȩ�޼��,���Խ����¼�Ŀ¼. Ŀ¼��ʽ YYYYMM/DD */
    $d  = date('d', time());
    $ym = date('Ym', time());
    if( !file_exists($folder.$ym) ){
        @mkdir($folder.$ym);
    }
    if( !file_exists($folder.$ym.'/'.$d) ){
        @mkdir($folder.$ym.'/'.$d);
    }
    $mask = file_privilege($folder.$ym.'/'.$d);

    /* �����ļ� */
    if( $mask >= 7 ){
        $filename = $ym .'/'. $d .'/'. date( 'His', time() ) .$ext;
    }else{
        $filename = date( 'Ym_d_His', time() ). $ext;
    }

    /* �ƶ��ļ� */
    if( move_uploaded_file($_FILES[$fcname]['tmp_name'], $folder.$filename) ){
        return $filename;
    }else{
        return '';
    }
}

/**
 * ����ͼƬ v1.0.0
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

/**
 * ɾ��ͼƬ v1.0.0
 *
 * @params str  $folder    Ҫɾ�����ļ����ڵ��ļ���
 * @params str  $filename  Ҫɾ�����ļ���(�����$folder��·��)
 *
 * @return bol  true��ʾɾ���ɹ���false��ʾɾ��ʧ��
 */
function delete_pic( $folder, $filename )
{
    /* �����ļ���·��ĩβб�� */
    $folder = rtrim(str_replace("\\","/",$folder), '/').'/';

    /* ɾ���ļ� */
    return @unlink($folder.$filename);
}
?>