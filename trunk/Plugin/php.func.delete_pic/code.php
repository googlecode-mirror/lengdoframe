<?php
/**
 * ɾ��ͼƬ
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