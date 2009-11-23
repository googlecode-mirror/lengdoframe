<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 数据库导出类
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


class DumpSql
{
    /* Mysql类对象 */
    var $oDb = null;

    /* 导出的SQL */
    var $sDumpSql = '';

    /* 文件大小限制 */
    var $iMaxSize = 0;

    /* 每次取记录的条数  */
    var $iOffset = 300;

    /* SQL 计数 */
    var $iSqlNum = 0;

    /* 数据插入选项 */
    var $bColumns  = 1;  //数据完整插入(即加上字段名)
    var $bExtended = 1;  //数据扩展插入(即INSERT多个VALUES)

    /**
     * 类的构造函数
     *
     * @params obj  $db    数据库对象
     * @params int  $size  默认2M(字节为单位)
     */
    function __construct( &$db, $size = 2097152 )
    {
        $this->DumpSql($db, $size);
    }

    function DumpSql( &$db, $size = 2097152 )
    {
        $this->oDb = &$db;
        $this->iMaxSize = $size;
    }

    /**
     *  取的指定表定义的SQL
     *
     * @params str  $table  数据表名
     * @params bol  $drop   是否加入 DROP TABLE
     *
     * @return str  SQL语句
     */
    function getTableDefine( $table, $drop = false )
    {
        /* 分隔线 */
        $sql = "-- --------------------------------------------------------";
        $sql.= "\r\n\r\n";

        /* 标题部分 */
        $sql.= "-- \r\n".
               "-- 表的结构 `$table` \r\n".
               "-- \r\n\r\n";

        /* DROP SQL部分 */
        $sql .= $drop ? "DROP TABLE IF EXISTS `$table`;\r\n" : '';

        $arr = $this->oDb->getRow("SHOW CREATE TABLE `$table`");

        /* 换行符 */
        if( strpos($arr['Create Table'], "\r\n") === false ){
            $sql.= str_replace("\n", "\r\n", $arr['Create Table']) .';';
        }else{
            $sql.= $arr['Create Table'] .';';
        }

        return $sql;
    }

    /**
     * 生成指定表数据的插入SQL
     *
     * @params str  $table  表名
     * @params int  $pos    备份开始位置(即记录条数)
     *
     * @return int  记录位置，-1表示数据已经全部写完，否则返回未写完的位置(即记录所在条数)
     */
    function makeTableDate( $table, $pos )
    {
        $move_pos = $pos;

        /* 获取数据表记录总数 */
        $total = $this->oDb->getOne("SELECT COUNT(*) FROM `$table`");

        /* 所有数据已经写完 */
        if( $total == 0 || $pos >= $total ){
            return -1;
        }

        /* 标题部分 */
        $this->sDumpSql .= "\r\n\r\n".
                           "-- \r\n".
                           "-- 表的数据 `{$table}` \r\n".
                           "-- \r\n\r\n";

        /* 确定循环次数 */
        $cycle = ceil( ($total-$pos)/$this->iOffset ); //每次取offset条数。需要取的次数

        /* 循环查数据表 */
        for( $i = 0; $i < $cycle; $i++ ){
            /* 获取数据库数据 */
            $data = $this->oDb->getAll("SELECT * FROM `$table` LIMIT ". ($this->iOffset * $i + $pos) .', '. $this->iOffset);
            $data_count = count($data);

            /* 构建 INSERT SQL 的前部分 */
            $fields = array_keys($data[0]);

            if( $this->bColumns ){
                $start_sql = "INSERT INTO `$table` ( `" . implode("`, `", $fields) . "` ) VALUES";
            }else{
                $start_sql = "INSERT INTO `$table` VALUES";
            }


            /* 循环将数据写入 */
            for( $j=0; $j < $data_count; $j++ ){
                /* 过滤非法字符 */
                $record = array_map("mysql_real_escape_string", $data[$j]);

                /* 是否构建简短的SQL */
                if( $this->bExtended ){
                    /* 是否是最后一条记录 */
                    if( $move_pos == $total-1 ){
                        $temp_sql = " ( '". implode("', '" , $record) ."' );\r\n";
                    }else{
                        $temp_sql = " ( '". implode("', '" , $record) ."' ),\r\n";
                    }

                    /* 第一次插入数据 */
                    if( $move_pos == $pos ){
                        $temp_sql = $start_sql . "\r\n" . $temp_sql;
                    }
                }else{
                    $temp_sql = $start_sql ." ( '". implode("','",$record) ."' );\r\n";
                }

                /* 数据量超过指定文件大小限制 */
                if( strlen($this->sDumpSql) + strlen($temp_sql) > $this->iMaxSize - 32 ){
                    /* 当是第一条记录时强制写入 */
                    if( $this->iSqlNum == 0 ){
                        $this->sDumpSql .= $temp_sql;
                        $this->iSqlNum++; //记录sql条数
                        $move_pos++;
                    }

                    /* 上个数据结束收尾 */
                    if( substr($this->sDumpSql, -4) == "),\r\n" ){
                        $this->sDumpSql = substr($this->sDumpSql, 0, -4) .");";
                    }

                    /* 所有数据已经写完，否则返回未写完的位置 */
                    return $move_pos == $total ? -1 : $move_pos;
                }else{
                    $this->sDumpSql .= $temp_sql;
                    $this->iSqlNum++; //记录sql条数
                    $move_pos++;
                }
            }
        }

        /* 所有数据已经写完 */
        return -1;
    }

    /**
     * 备份一个数据表
     *
     * @params str  $path  保存数据表备份位置的文件路径
     * @params int  $vol   卷标号
     *
     * @return arr  未备份完的表列表，false表示文件有错误。
     */
    function dumpTables( $path, $vol )
    {
        /* 数据表备份位置 */
        $tables = $this->getTablesList($path);

        /* 数据表定位文件打开失败 */
        if( $tables === false ) return false;

        /* 文件头 */
        $this->sDumpSql = $this->makeHeader($vol);

        /* 备份结束 */
        if( empty($tables) ) return array();

        /* 备份表和表数据 */
        foreach( $tables as $table => $pos ){
            /* 表未备份过 */
            if( $pos == -1 ){
                /* 获取表定义，如果没有超过限制则保存 */
                $table_df = "\r\n\r\n". $this->getTableDefine($table, true);

                /* 数据量超过指定文件大小限制 */
                if( strlen($this->sDumpSql) + strlen($table_df) > $this->iMaxSize - 32 ){
                    if( $this->iSqlNum == 0 ){
                        /* 第一条记录，强制写入 */
                        $this->sDumpSql .= $table_df;
                        $this->iSqlNum  += 2;
                        $tables[$table]  = 0;
                    }
                    break;
                }else{
                    $this->sDumpSql .= $table_df;
                    $this->iSqlNum  +=2;
                    $pos = 0;
                }
            }

            /* 尽可能多获取数据表数据 */
            $move_pos = $this->makeTableDate($table, $pos);

            /* 该表已经完成，清除该表 */
            if( $move_pos == -1 ){
                unset( $tables[$table] );
            }

            /* 该表未完成。说明将要到达上限,记录备份数据位置 */
            else{
                $tables[$table] = $move_pos; break;
            }
        }

        $this->putTablesList($path, $tables);

        return $tables;
    }

    /**
     * 生成备份文件头
     *
     * @params int  $vol  文件卷标号
     *
     * @return str  备份文件头部字符串
     */
    function makeHeader( $vol )
    {
        /* 系统信息 */
        $os        = PHP_OS;
        $date      = date('Y-m-d H:i:s');
        $php_ver   = PHP_VERSION;
        $mysql_ver = $this->oDb->version();

        $header = "-- LengdoFrame SQL Dump\r\n".
                  "-- \r\n".
                  "-- date: {$date}\r\n".
                  "-- php: {$php_ver}\r\n".
                  "-- mysql: {$mysql_ver}\r\n".
                  "-- vol: {$vol}";

        return $header;
    }

    /**
     * 获取并解析备份文件头信息
     *
     * @params str  $path  备份文件路径
     *
     * @return arr  信息数组
     */
    function getHeader( $path )
    {
        /* 获取sql文件头部信息 */
        $sql_info = array('date'=>'', 'mysql'=> '', 'php'=>0, 'vol'=>0);

        /* 读取sql文件头字符串 */
        $fp  = fopen($path, 'rb');
        $str = fread($fp, 250);

        fclose($fp);

        /* 解析为数组 */
        $arr = explode("\n", $str);

        /* 解析sql文件头字符串 */
        foreach( $arr AS $val ){
            $pos = strpos($val, ':');

            if( intval($pos) == 0 ) continue;

            $type  = trim( substr($val,0,$pos), "-\n\r\t " );
            $value = trim( substr($val,$pos+1), "/\n\r\t " );

            switch( $type ){
                case 'php'  : $sql_info['php']   = $value; break;
                case 'vol'  : $sql_info['vol']   = $value; break;
                case 'date' : $sql_info['date']  = $value; break;
                case 'mysql': $sql_info['mysql'] = $value; break;
            }
        }

        return $sql_info;
    }

    /**
     * 将文件中数据表定位数据取出
     *
     * @params str  $path  文件路径
     *
     * @return arr  数据表列表. false表示文件有错误。
     */
    function getTablesList( $path )
    {
        /* 无效文件 */
        if( !is_file($path) ) return false;

        /* 初始化 */
        $arr = array();
        $str = @file_get_contents($path);

        /* 无效文件 */
        if( @file_put_contents($path, $str) === false ){
            return false;
        }

        /* 无数据 */
        if( empty($str) ) return array();

        /* 解析为数组 */
        $tmp_arr = explode("\n", $str);

        /* 重构数组数据 */
        foreach( $tmp_arr as $val ){
            $val = trim($val, "\r;");

            if( !empty($val) ){
                list($table, $pos) = explode(':', $val);
                $arr[$table] = $pos;
            }
        }

        return $arr;
    }

    /**
     * 将数据表定位数据写入指定文件
     *
     * @params  str  $path  文件路径
     * @params  arr  $arr   要写入的数据
     *
     * @return  bol  true表示写入成功，false表示写入失败
     */
    function putTablesList( $path, $arr )
    {
        /* 无效参数 */
        if( !is_array($arr) ) return false;

        /* 构建数据 */
        $str = '';
        foreach( $arr as $key => $val ){
            $str .= $key .':'. $val .";\r\n";
        }

        /* 写入数据 */
        if( @file_put_contents($path, $str) === false ){
            return false;
        }

        return true;
    }

    /**
     *  返回一个随机的名字
     *
     * @return str  随机名称
     */
    function getRandName()
    {
        return date('Ymd[His]');
    }
}
?>