<?php
/**
 * �ļ��б��ݹ��ļ���
 *
 * @params str  $path     �ļ��о���·��
 * @params arr  $ext      ָ����չ���ļ�
 * @params str  $filter   ���˵���ָ���ʵ��ļ���(������� | �ָ�)��Ĭ�ϲ�����
 * @params str  $contain  ѡ��ָ���ʵ��ļ���(������� | �ָ�)��Ĭ�ϲ�����
 * @params bol  $page     �Ƿ�ʹ�÷�ҳ
 *
 * @return arr  �б�����
 */
function list_file( $path, $ext = array(), $filter = '', $contain = '', $page = true )
{
    /* ��ʼ�� */
    $p = $list = array();

    /* Ŀ¼��Ч�Լ�� */
    if( !is_dir($path) ){
        return $list;
    }

    /* ��ʼ��·����ʽ,��·��ĩβ����б�� */
    $path  = str_replace("\\", '/', $path);
    $path .= preg_match('/[\/]$/',$path) ? '' : '/';

    /* ��ʼ�����˴� */
    $filters  = trim($filter)  ? explode('|', $filter)  : array();
    $contains = trim($contain) ? explode('|', $contain) : array();

    /* �����ļ����µ��ļ���Ϣ */
    $files  = array();
    $folder = opendir($path);
    while( $file = readdir($folder) ){
        /* ������ļ� */
        if( is_file($path.'/'.$file) ){
            /* ���˵���ָ���ʵ��ļ��� */
            if( !empty($filters) ){
                $finded = false;

                foreach( $filters AS $v ){
                    if( strpos($file,$v) !== false ){
                        $finded = true; break;
                    }
                }

                if( $finded === true ) continue;
            }
            /* ѡ��ָ���ʵ��ļ��� */
            if( !empty($contains) ){
                $finded = false;

                foreach( $contains AS $v ){
                    if( strpos($file,$v) !== false ){
                        $finded = true; break;
                    }
                }

                if( $finded === false ) continue;
            }

            /* ûָ����չ�� */
            if( empty($ext) ){
                $files[] = array( 'path'=>$path.$file, 'file'=>$file, 'ctime'=>filectime($path.$file) );
            }

            /* ָ����չ�� */
            elseif( in_array(substr($file,strrpos($file,'.')+1), $ext) ){
                $files[] = array( 'path'=>$path.$file, 'file'=>$file, 'ctime'=>filectime($path.$file) );
            }
        }

        /* ������ļ��� */
        elseif( !preg_match('/^\./',$file) && is_dir($path.$file) ){
            $tlist = list_file($path.$file, $ext, $filter, $contain, false);
            $files = array_merge($files, $tlist['data']);
        }
    }
    closedir($folder);

    /* ���÷�ҳ���ݺ���Ϣ */
    if( $page ){
        $p['rows_page']  = intval($_REQUEST['rows_page']) ? intval($_REQUEST['rows_page']) : 15;
        $p['rows_total'] = count($files);
        $p['html']       = pager( $p['rows_page'], $p['rows_total'] );
        $p['cur_page']   = cur_page( $p['rows_page'], $p['rows_total'] );
        $p['row_start']  = ($p['cur_page']-1) * $p['rows_page'];

        $list['data']    = array_slice($files, $p['row_start'], $p['rows_page']);
        $list['pager']   = $p;
    }else{
        $list['data']    = $files;
    }

    /* ���� */
    return $list;
}

/**
 * �ļ��б� - ͼ��
 *
 * @params str  $rpath    �ļ��о���·��
 * @params str  $upath    �ļ������·��
 * @params str  $filter   ���˵���ָ���ʵ��ļ�����Ĭ�ϲ�����
 * @params str  $contain  ѡ��ָ���ʵ��ļ�����Ĭ�ϲ�����
 * @params bol  $page     �Ƿ�ʹ�÷�ҳ
 *
 * @params arr  �б�����
 */
function list_file_img( $rpath, $upath, $filter = '', $contain = '', $page = true )
{
    /* ��ʼ��ͼ����չ�� */
    $ext = array('jpg', 'gif', 'png');

    /* ��ʼ��·����ʽ,��·��ĩβ����б�� */
    $rpath  = str_replace("\\", '/', $rpath);
    $rpath .= preg_match( '/[\/]$/', $rpath ) ? '' : '/';
    $upath  = str_replace("\\", '/', $upath);
    $upath .= preg_match( '/[\/]$/', $upath ) ? '' : '/';

    $list = list_file($rpath, $ext, $filter, $contain, $page);

    /* ���ͼƬ��URL����·�� */
    foreach( $list['data'] AS $i=>$file ){
        $list['data'][$i]['upath'] = str_replace($rpath, $upath, $file['path']); //URL·��
        $list['data'][$i]['bpath'] = str_replace($rpath, '', $file['path']);     //����·���������$rpath��
    }

    /* ���� */
    return $list;
}
?>