<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 表单控件类
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


class FormControl{
    /**
     * 表单控件 - 下拉框
     *
     * @params str  $name   下拉框NAME
     * @params arr  $items  下拉框OPTION项
     *         mix  $items[]['text']
     *         mix  $items[]['value']
     *         str  $items[]['style']
     * @params str  $attribs  下拉框属性
     *         str  $attribs['id']         下拉框ID - 默认使用$name
     *         int  $attribs['width']      下拉框宽度
     *         str  $attribs['style']      下拉框样式
     *         mix  $attribs['selected']   下拉框选默认选中值(字符或整型，程序最后自动转为字符比较)
     *         str  $attribs['onchange']   下拉框改变时事件
     */
    public function ddl( $name, $items, $attribs = array() )
    {
        if( !is_array($items) || empty($items) ) return '' ;
        
        /* HTML开始 */
        $html = '<select name="'. $name .'" id="'. ($attribs['id'] ? $attribs['id'] : $name) .'"';

        /* 样式和宽度 */
        $html .= ' style="'. ($attribs['style'] ? ($attribs['style'].';') : '');
        $html .= is_numeric($attribs['width']) ? ('width:'.intval($attribs['width']).'px"') : '"';

        /* onchange 事件 */
        $html .= $attribs['onchange'] ? (' onchange="'.$attribs['onchange'].'"') : '';

        /* HTML闭合 */
        $html .= '>';

        /* SELECT 项 */
        foreach( $items AS $item ){
            /* 选项值 */
            $html .= '<option value="'. $item['value'] .'"';

            /* 样式值 */
            $html .= isset($item['style']) ? ('style="'.$item['style'].'"') : '';

            /* 是否选中 */
            $html .= strval($item['value']) == strval($attribs['selected']) ? ' selected' : '';

            /* 选项名 */
            $html .= '>'. $item['text'] .'</option>';
        }

        /* HTML闭合 */
        $html .= '</select>';

        return $html;
    }

    /**
     * 表单控件 - 下拉框(含禁选功能)
     *
     * @params ...
     * @params arr  $forbids  禁选的值
     * @params arr  $act      JS禁选的处理代码
     */
    public function ddl_forbid( $name, $items, $attribs = array(), $forbids = array(), $act = '' )
    {
        if( is_array($forbids) ){
            foreach( $forbids AS $key=>$value ){
                $fbs[$key] = $value.':1';
            }

            $onchange = 'if({'. implode(',', $fbs) .'}[this.value]==1){'. $act .'}';
        }

        $attribs['onchange'] = $onchange;

        return $this->ddl($name, $items, $attribs);
    }

    /**
     * 表单控件 - 复选框
     *
     * @params str  $name  控件名称
     * @params arr  $item  复选框项属性
     *         str  $item['id']        复选框ID
     *         mix  $item['text']      复选框文本
     *         mix  $item['value']     复选框值
     *         str  $item['class']     复选框类名
     *         str  $item['onclick']   复选框点击事件
     *         bol  $item['checked']   复选框是否选中
     *         str  $item['lbclass']   复选框标签类名
     */
    public function cb( $name, $item )
    {
        if( !isset($item['value']) || !isset($item['text']) ) return '';

        $id   = empty($item['id']) ? ($name.$item['value']) : trim($item['id']);

        $html = '<input type="checkbox"';
        $html.= ' id="'   . $id            .'"';
        $html.= ' name="' . $name          .'"';
        $html.= ' value="'. $item['value'] .'"';

        /* class样式. onclilck事件. checked是否选中 */
        $html.= empty($item['class'])     ? ' class="checkbox"' : (' class="'.trim($item['class']).'"');
        $html.= empty($item['onclick'])   ? '' : (' onclick="'.trim($item['onclick']).'"');
        $html.= $item['checked'] === true ? ' checked' : '';

        /* 闭合和显示的文本 */
        $html.= '><label for="'. $id .'" class="'. $item['lbclass'] .'">';
        $html.= $item['text'] .'</label>&nbsp;';

        return $html;
    }

    /**
     * 表单控件 - 复选框组(包裹Table)
     *
     * @params str  $name     控件名称
     * @params arr  $items    复选框项
     * @params arr  $attribs  复选框属性
     *         int  $attribs['len']      几个复选框为一行
     *         arr  $attribs['checked']  被选中的值（数组）
     */
    public function cbg( $name, $items, $attribs = array() )
    {
        /* 参数初始化 */
        if( !is_array($items[0]) ) return '';
        if( !is_array($attribs['checked']) ) $attribs['checked'] = array();

        $attribs['len'] = intval($attribs['len']) ? intval($attribs['len']) : 4;

        /* 开始构建复选框组HTML */
        $tr    = array();
        $width = 100/$attribs['len']. '%';
        $html  = '<table cellspacing="0" cellpadding="0" border="0">';

        foreach( $items AS $i=>$item ){
            /* 表格行控制 */
            if( $i % $attribs['len'] == 0 ){
                if( !empty($tr) ){
                    $html .= array_pop($tr);
                }

                $html .= '<tr>';
                array_push($tr, '</tr>');
            }

            /* 选中状态设置 */
            if( !isset($item['checked']) ){
                $item['checked'] = in_array($item['value'],$attribs['checked']) ? true : false;
            }

            /* 表格TD */
            $html .= '<td width="'. $width .'">'. $this->cb($name.'[]', $item) .'</td>';
        }

        /* TR未结束 */
        if( !empty($tr) ){
            /* TD填充 */
            while( ++$i % $attribs['len'] != 0 ){
                $html .= '<td width="'. $width .'"></td>';
            }

            $html .= array_pop($tr);
        }

        return $html.'</table>';
    }

    /**
     * 表单控件 - 单选框
     *
     * @params str  $name  控件名称
     * @params arr  $item  单选框项属性
     *         str  $item['id']        单选框ID
     *         mix  $item['text']      单选框文本
     *         mix  $item['value']     单选框值
     *         str  $item['class']     单选框类名
     *         str  $item['onclick']   单选框点击事件
     *         bol  $item['checked']   单选框是否选中
     *         str  $item['lbclass']   单选框标签类名
     */
    public function radio( $name, $item )
    {
        if( !isset($item['value']) || !isset($item['text']) ) return '';

        $id   = empty($item['id']) ? ($name.$item['value']) : trim($item['id']);

        $html = '<input type="radio" class="radio"';
        $html.= ' id="'   . $id            .'"';
        $html.= ' name="' . $name          .'"';
        $html.= ' value="'. $item['value'] .'"';

        /* class样式. onclilck事件. checked是否选中 */
        $html.= empty($item['class'])     ? ' class="radio"' : (' class="'.trim($item['class']).'"');
        $html.= empty($item['onclick'])   ? '' : (' onclick="'.trim($item['onclick']).'"');
        $html.= $item['checked'] === true ? ' checked' : '';

        /* 闭合和显示的文本 */
        $html.= '><label for="'. $id .'" class="'. $item['lbclass'] .'">';
        $html.= $item['text'] .'</label>&nbsp;';

        return $html;
    }

    /**
     * 表单控件 - 单选框组(包裹Table)
     *
     * @params str  $name     控件名称
     * @params arr  $items    单选框项
     * @params arr  $attribs  单选框属性
     *         int  $attribs['len']     几个单选框为一行
     *         str  $attribs['checked'] 被选中的值
     */
    public function radiog( $name, $items, $attribs = array() )
    {
        /* 参数初始化 */
        if( !is_array($items[0]) ) return '';

        $attribs['len'] = intval($attribs['len']) ? intval($attribs['len']) : 4;

        /* 开始构建复选框组HTML */
        $tr    = array();
        $width = 100/$attribs['len']. '%';
        $html  = '<table cellspacing="0" cellpadding="0" border="0">';

        foreach( $items AS $i=>$item ){
            /* 表格行控制 */
            if( $i % $attribs['len'] == 0 ){
                if( !empty($tr) ){
                    $html .= array_pop($tr);
                }

                $html .= '<tr>';
                array_push($tr, '</tr>');
            }

            /* 选中状态设置 */
            if( !isset($item['checked']) ){
                $item['checked'] = $item['value'] == $attribs['checked'] ? true : false;
            }

            /* 表格TD */
            $html .= '<td width="'. $width .'">'. $this->radio($name, $item) .'</td>';
        }

        /* TR未结束 */
        if( !empty($tr) ){
            /* TD填充 */
            while( ++$i % $attribs['len'] != 0 ){
                $html .= '<td width="'. $width .'"></td>';
            }

            $html .= array_pop($tr);
        }

        return $html.'</table>';
    }
}
?>