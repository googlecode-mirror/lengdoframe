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


class Formc{
    /**
     * 表单控件 - 下拉列表
     *
     * @params str  $name     下拉列表名称
     * @params arr  $items    下拉列表项
     *         mix            $items[]['...']      - 下拉列表项的HTML标签属性
     *         str            $items[]['text']     - 下拉列表项的文本内容
     * @params str  $attribs  下拉列表项属性
     *         mix            $attribs['...']      - 下拉列表项的HTML标签属性
     *         str            $attribs['selected'] - 下拉列表项默认选中值(字符或整型，程序最后自动转为字符比较)
     */
    public function ddl( $name, $items, $attribs = array() )
    {
        /* 无效参数 */
        if( !is_array($items) || empty($items) ) return '' ;

        /* SELECT初始化 */
        $attribs['id'] = $attribs['id'] != '' ? $attribs['id'] : $name;
        $attribs['name'] = $name;

        /* SELECT的非属性项, 单一属性项 */
        $natt = array('selected');
        $batt = array('disabled');

        /* SELECT HTML */
        $html = '<select';
        foreach( $attribs AS $key => $val ){
            /* 非属性项 */
            if( in_array($key, $natt) ) continue;

            /* 属性项和单一属性项 */
            $html .= in_array($key, $batt) ? ($val?$key:'') : (' '.$key.'="'.$val.'"');
        }
        $html .= '>';


        /* OPTION的非属性项 */
        $natt = array('text');

        /* OPTION HTML */
        foreach( $items AS $item ){
            $html .= '<option';
            foreach( $item AS $key => $val ){
                /* 非属性项 */
                if( in_array($key, $natt) ) continue;

                /* 属性项 */
                $html .= ' '.$key.'="'.$val.'"';

                /* 选中项 */
                $html .= strval($item['value']) == strval($attribs['selected']) ? ' selected' : '';
            }
            $html .= '>'. $item['text'] .'</option>';
        }


        /* SELECT HTML */
        $html .= '</select>';

        /* 返回 */
        return $html;
    }

    /**
     * 表单控件 - 复选框
     *
     * @params str  $name  复选框名称
     * @params arr  $item  复选框项属性
     *         mix         $item['...']   - 复选框的HTML标签属性
     *         str         $item['text']  - 复选框文本
     *         arr         $item['label'] - 复选框标签
     *         mix                          $item['label']['...'] - 复选框标签的HTML标签属性
     */
    public function cb( $name, $item )
    {
        /* 无效参数 */
        if( !isset($item['value']) || !isset($item['text']) ) return '';

        /* CHECKBOX初始化 */
        $item['id']    = $item['id'] != '' ? $item['id'] : ($name.$item['value']);
        $item['name']  = $name;
        $item['class'] = $item['class'] != '' ? $item['class'] : 'checkbox';

        /* CHECKBOX的非属性项, 单一属性项 */
        $natt = array('text', 'label');
        $batt = array('checked', 'disabled');

        /* CHECKBOX HTML */
        $html = '<input type="checkbox"';
        foreach( $item AS $key => $val ){
            /* 非属性项 */
            if( in_array($key, $natt) ) continue;

            /* 属性项和单一属性项 */
            $html .= in_array($key, $batt) ? ($val?$key:'') : (' '.$key.'="'.$val.'"');
        }
        $html .= '/>';


        /* LABEL初始化 */
        $item['label']['for'] = $item['id'];

        /* LABEL HTML */
        $html .= '<label';
        foreach( $item['label'] AS $key => $val ){
            $html .= ' '. $key .'="'. $val .'"';
        }
        $html .= '>'. $item['text'] .'</label>&nbsp;';


        /* 返回 */
        return $html;
    }

    /**
     * 表单控件 - 复选框组(包裹Table)
     *
     * @params str  $name     复选框名称
     * @params arr  $items    复选框项属性
     *         mix            $items[]['...']   - 复选框的HTML标签属性
     *         str            $items[]['text']  - 复选框文本
     *         arr            $items[]['label'] - 复选框标签
     *         mix                                $items[]['label']['...'] - 复选框标签的HTML标签属性
     * @params arr  $attribs  复选框组属性
     *         int            $attribs['len']     - 几个复选框为一行
     *         arr            $attribs['checked'] - 被选中的值
     */
    public function cbg( $name, $items, $attribs = array() )
    {
        /* 无效参数 */
        if( !is_array($items[0]) ) return '';

        /* 初始化 */
        $attribs['len']     = intval($attribs['len'])       ? intval($attribs['len']) : 4;
        $attribs['checked'] = is_array($attribs['checked']) ? $attribs['checked']     : array();

        /* 初始化 */
        $tr    = array();
        $html  = '<table cellspacing="0" cellpadding="0" border="0">';
        $width = 100/$attribs['len']. '%';

        /* CBG HTML */
        foreach( $items AS $i=>$item ){
            /* 表格行控制 */
            if( $i % $attribs['len'] == 0 ){
                $html .= empty($tr) ? '' : array_pop($tr);

                $html .= '<tr>';
                array_push($tr, '</tr>');
            }

            /* 选中状态设置 */
            if( !isset($item['checked']) ){
                $item['checked'] = in_array($item['value'], $attribs['checked']) ? true : false;
            }

            /* 表格TD */
            $html .= '<td width="'. $width .'">'. $this->cb($name, $item) .'</td>';
        }

        /* TR未结束 */
        if( !empty($tr) ){
            /* TD填充 */
            while( ++$i % $attribs['len'] != 0 ){
                $html .= '<td width="'. $width .'"></td>';
            }

            $html .= array_pop($tr);
        }

        /* 返回 */
        return $html.'</table>';
    }

    /**
     * 表单控件 - 单选框
     *
     * @params str  $name  单选框称
     * @params arr  $item  单选框项属性
     *         mix         $item['...']   - 单选框的HTML标签属性
     *         str         $item['text']  - 单选框文本
     *         arr         $item['label'] - 单选框标签
     *         mix                          $item['label']['...'] - 单选框标签的HTML标签属性
     */
    public function radio( $name, $item )
    {
        /* 无效参数 */
        if( !isset($item['value']) || !isset($item['text']) ) return '';

        /* RADIO初始化 */
        $item['id']    = $item['id'] != '' ? $item['id'] : ($name.$item['value']);
        $item['name']  = $name;
        $item['class'] = $item['class'] != '' ? $item['class'] : 'radio';

        /* RADIO的非属性项, 单一属性项 */
        $natt = array('text', 'label');
        $batt = array('checked', 'disabled');

        /* RADIO HTML */
        $html = '<input type="radio"';
        foreach( $item AS $key => $val ){
            /* 非属性项 */
            if( in_array($key, $natt) ) continue;

            /* 属性项和单一属性项 */
            $html .= in_array($key, $batt) ? ($val?$key:'') : (' '.$key.'="'.$val.'"');
        }
        $html .= '/>';


        /* LABEL初始化 */
        $item['label']['for'] = $item['id'];

        /* LABEL HTML */
        $html .= '<label';
        foreach( $item['label'] AS $key => $val ){
            $html .= ' '. $key .'="'. $val .'"';
        }
        $html .= '>'. $item['text'] .'</label>&nbsp;';

        /* 返回 */
        return $html;
    }

    /**
     * 表单控件 - 单选框组(包裹Table)
     *
     * @params str  $name     单选框名称
     * @params arr  $items    单选框项
     *         mix            $items[]['...']   - 单选框的HTML标签属性
     *         str            $items[]['text']  - 单选框文本
     *         arr            $items[]['label'] - 单选框标签
     *         mix                                $items[]['label']['...'] - 单选框标签的HTML标签属性
     * @params arr  $attribs  单选框属性
     *         int            $attribs['len']     - 几个单选框为一行
     *         str            $attribs['checked'] - 被选中的值(字符或整型，程序最后自动转为字符比较)
     */
    public function radiog( $name, $items, $attribs = array() )
    {
        /* 无效参数 */
        if( !is_array($items[0]) ) return '';

        /* 初始化 */
        $attribs['len'] = intval($attribs['len']) ? intval($attribs['len']) : 4;

        /* 初始化 */
        $tr    = array();
        $html  = '<table cellspacing="0" cellpadding="0" border="0">';
        $width = 100/$attribs['len']. '%';

        /* RADIOG HTML */
        foreach( $items AS $i=>$item ){
            /* 表格行控制 */
            if( $i % $attribs['len'] == 0 ){
                $html .= empty($tr) ? '' : array_pop($tr);

                $html .= '<tr>';
                array_push($tr, '</tr>');
            }

            /* 选中状态设置 */
            if( !isset($item['checked']) ){
                $item['checked'] = strval($item['value']) == strval($attribs['checked']) ? true : false;
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

        /* 返回 */
        return $html.'</table>';
    }
}
?>