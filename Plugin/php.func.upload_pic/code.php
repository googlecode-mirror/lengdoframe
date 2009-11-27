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
?>