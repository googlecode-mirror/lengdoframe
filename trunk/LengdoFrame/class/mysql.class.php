<?php
// +----------------------------------------------------------------------
// | LengdoFrame - Mysql数据库类
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


class Mysql
{
    /* 数据库连接句柄 */
    var $hLink = null;

    /* SQL语句执行信息 */
    var $aQueryLog   = array();
    var $iQueryTime  = null;
    var $iQueryCount = 0;

    /* Mysql版本 */
    var $sVersion = '';

    /* 最后的错误消息 */
    var $aError = array();


    /**
     * 构造函数
     *
     * @params str  $dbhost    数据库主机
     * @params str  $dbuser    数据库登陆帐号
     * @params str  $dbpass    数据库登陆密码
     * @params str  $dbname    数据库名
     * @params str  $charset   字符集， 默认'utf8'
     * @params bol  $pconnect  持续连接，默认false
     */
    function __construct( $dbhost, $dbuser, $dbpass, $dbname = '', $charset = 'utf8', $pconnect = false )
    {
        $this->MySql($dbhost, $dbuser, $dbpass, $dbname, $charset, $pconnect, $link);
    }

    function MySql( $dbhost, $dbuser, $dbpass, $dbname = '', $charset = 'utf8', $pconnect = false )
    {
        $this->connect($dbhost, $dbuser, $dbpass, $dbname, $charset, $pconnect);
    }

    /**
     * 数据库连接
     *
     * @params str  $dbhost    数据库主机
     * @params str  $dbuser    数据库登陆帐号
     * @params str  $dbpass    数据库登陆密码
     * @params str  $dbname    数据库名
     * @params str  $charset   字符集， 默认'utf8'
     * @params bol  $pconnect  持续连接，默认false
     *
     * @return bol  true表示连接成功，否则中断显示错误
     */
    function connect( $dbhost, $dbuser, $dbpass, $dbname = '', $charset = 'utf8', $pconnect = false )
    {
        /* 连接数据库 */
        if( $pconnect ){
            if( !($this->hLink = @mysql_pconnect($dbhost,$dbuser,$dbpass)) ){
                $this->halt("Can't pConnect MySQL Server($dbhost)!");
            }
        }else{
            if( PHP_VERSION >= '4.2' ){
                $this->hLink = @mysql_connect($dbhost, $dbuser, $dbpass, true);
            }else{
                $this->hLink = @mysql_connect($dbhost, $dbuser, $dbpass);
            }
            if( !$this->hLink ){
                $this->halt("Can't Connect MySQL Server($dbhost)!");
            }
        }

        /* 获取Mysql版本 */
        $this->sVersion = mysql_get_server_info($this->hLink);

        /* 如果Mysql版本是 4.1+ 以上，需要对字符集进行初始化 */
        if( $this->sVersion > '4.1' ){
            if( $charset != 'latin1' ){
                mysql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->hLink);
            }
            if( $this->sVersion > '5.0.1' ){
                mysql_query("SET sql_mode=''", $this->hLink);
            }
        }

        /* 选择数据库 */
        if( $dbname ){
            if( mysql_select_db($dbname, $this->hLink) === false ){
                $this->halt("Can't select MySQL database($dbname)!");
            }
        }

        return true;
    }

    /**
     * 执行 SQL 语句
     *
     * @params str  $sql   SQL语句
     * @params bol  $halt  错误时中断
     *
     * @return bol  true表示执行成功，flase表示执行失败
     */
    function query( $sql, $halt = true )
    {
        /* SQL语句的执行信息 */
        if( $this->iQueryCount++ <= 99 ){
            $this->aQueryLog[] = $sql;
        }
        if( $this->iQueryTime == null ){
            if( PHP_VERSION >= '5.0.0' ){
                $this->iQueryTime = microtime(true);
            }else{
                $this->iQueryTime = microtime();
            }
        }

        /* 执行 SQL */
        if( !($result = mysql_query($sql, $this->hLink)) ){
            $this->aError['SQL']   = $sql;
            $this->aError['MSG']   = 'MySQL Query Error';
            $this->aError['ERROR'] = mysql_error($this->hLink);
            $this->aError['ERRNO'] = mysql_errno($this->hLink);

            $halt ? $this->halt() : '';
        }

        return $result;
    }

    /**
     * Mysql 版本
     */
    function version()
    {
        return $this->sVersion;
    }

    /**
     * 取得上一步 INSERT 操作产生的 ID
     */
    function insertId()
    {
        return mysql_insert_id($this->hLink);
    }

    /**
     * 取得前一次操作所影响的记录行数
     */
    function affectedRows()
    {
        return mysql_affected_rows($this->hLink);
    }

    /**
     * 中断
     */
    function halt( $message = '' )
    {
        /* 自定义消息 */
        if( $message ){
            echo '<div style="font-size:12px; font-family:Courier New"><b>info</b>: ', $message, '</div>';
        }

        /* 系统消息 */
        else{
            echo '<table style="font-size:12px; font-family:Courier New">';

            foreach( $this->aError AS $k=>$v ){
                echo "<tr><td valign='top'><b>{$k}: </b><td>{$v}<td></tr>";
            }

            echo "</table>";
        }

        exit;
    }


    /* ------------------------------------------------------ */
    // - 仿真 Adodb 函数
    /* ------------------------------------------------------ */

	/**
	 * 取得第一条记录的第一个字段的值
     *
     * @params str  $sql  查询的SQL
     *
     * @return str  失败返回空字符
	 */
    function getOne( $sql )
    {
        $result = $this->query($sql);

        if( $result !== false ){
            $row = mysql_fetch_row($result);

            if( $row ){
                return $row[0];
            }
        }

        return '';
    }

	/**
	 * 返回所有的记录
     *
     * @params str  $sql  查询的SQL
     *
     * @return arr  失败返回空数组
	 */
    function getAll( $sql )
    {
        $result = $this->query($sql);

        if( $result !== false ){
            $rows = array();

            while( $row = mysql_fetch_assoc($result) ){
                $rows[] = $row;
            }

            return $rows;
        }

        return array();
    }

	/**
	 * 取得记录集第一条记录
     *
     * @params str  $sql  查询的SQL
     *
     * @return arr  失败返回空数组
	 */
    function getRow( $sql )
    {
        $result = $this->query($sql);

        if( $result !== false ){
            $row = mysql_fetch_assoc($result);

            if( $row ){
                return $row;
            }
        }

        return array();
    }
	
	/**
	 * 取得所有记录中第一个列的值
     *
     * @params str  $sql  查询的SQL
     *
     * @return arr  失败返回空数组
	 */
    function getCol( $sql )
    {
        $result = $this->query( $sql );

        if( $result !== false ){
            $arr = array();

            while( $row = mysql_fetch_row($result) ){
                $arr[] = $row[0];
            }

            return $arr;
        }

        return array();
    }


    /**
     * 插入
     *
     * @params str  $table          表名
     * @params arr  $fields_values  字段值
     *
     * @return bol  true表示执行成功，flase表示执行失败
     */
    function insert( $table, $field_values )
    {
        /* 取得表的所有字段名称 */
        $field_names = $this->getCol('DESC `'. $table .'`');

        /* 初始化 */
        $fields = array();
        $values = array();

        /* 过滤$fields_values中无效的字段，提取有效字段和字段值 */
        foreach( $field_names AS $field ){
            if( array_key_exists($field, $field_values) == true ){
                $fields[] = '`'. $field .'`';
                $values[] = '"'. $field_values[$field] .'"';
            }
        }

        /* 构建SQL */
        if( !empty($fields) ){
            $sql = 'INSERT INTO `'. $table .'` ('. implode(',',$fields) .') VALUES ('. implode(',',$values) .')';
        }

        /* 执行 SQL */
        if( $sql ){
            return $this->query($sql);
        }

        return false;
    }

    /**
     * 更新
     */
    function update( $table, $field_values, $where = '' )
    {
        /* 取得表的所有字段名称 */
        $field_names = $this->getCol('DESC `'. $table .'`');

        /* 初始化 */
        $sets = array();

        /* 过滤 $fields_values 中无效的字段，提取有效字段和字段值 */
        foreach( $field_names AS $field ){
            if( array_key_exists($field, $field_values) == true ){
                $sets[] = '`'. $field. '` = "'. $field_values[$field] .'"';
            }
        }

        /* 构建SQL */
        if( !empty($sets) ){
            $sql = 'UPDATE `'. $table .'` SET '. implode(',',$sets) .' WHERE '. $where;
        }

        /* 执行SQL */
        if( $sql ){
            return $this->query($sql);
        }

        return false;
    }

    /**
     * 删除
     */
    function delete( $table, $where )
    {
        return $this->query('DELETE FROM `'. $table . '` WHERE '. $where);
    }
}
?>