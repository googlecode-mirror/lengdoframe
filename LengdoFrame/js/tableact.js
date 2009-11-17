// +----------------------------------------------------------------------
// | LengdoFrame - 表格通用操作对象
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


var TableAct = {
    /**
     * 保护色
     * 表格单元着色最高等级颜色，可以防止随意被修改
     */
    aProtectColor : ['#FFFCC1','RGB(255, 252, 193)'],


    /* ------------------------------------------------------ */
    // - 表格行操作
    /* ------------------------------------------------------ */

    /**
     * 增加表格行
     *
     * @params mix  table      表格对象或者ID
     * @params arr  cells      单元格的内容集
     * @params obj  trattrib   表格行属性   - {attrib:val}
     * @params arr  tdattribs  表格列属性集 - [{attrib:val}]
     * @params int  index      插入的表格行的位置，默认为-1
     */
    addRow : function( table, cells, trattrib, tdattribs, index ){
        /* 初始化 */
        var i, j, cell;

        /* 初始化表格行插入位置 */
        index = typeof(index) == 'number' ? index : -1;

        /* 初始化表格对象 */
        table = typeof(table) == 'object' ? table : document.getElementById(table);

        /* 增加表格行 */
        if( table.tagName && table.tagName.toLowerCase() == 'table' ){
            var tr = table.insertRow(index);
        }else{
            return false;
        }

        /* 设置TR属性 */
        if( typeof(trattrib) == 'object' && trattrib ){
            for( i in trattrib ){
                tr[i] = trattrib[i];
            }
        }

        /* 初始化TDS的属性 */
        tdattribs = typeof(tdattribs) == 'object' && tdattribs ? tdattribs : {};

        /* 增加表格列 */
        for( i=0,len=cells.length; i < len; i++ ){
            /* 增加列 */
            cell = tr.insertCell(-1);

            /* 设置TD属性 */
            if( typeof(tdattribs[i]) == 'object' && tdattribs[i] ){
                for( j in tdattribs[i] ){
                    cell[j] = tdattribs[i][j];
                }
            }

            /* 设置TD内容 */
            cell.innerHTML = cells[i];
        }
    },

    /**
     * 更新表格行
     *
     * @params obj  tr     要更新的表格行或者行内元素对象
     * @params mix  cells  单元格的内容(数组或者下标为数字的对象)
     *                     当cells[i]为 undefined, null 时不更新单元格
     */
    updateRow : function( tr, cells ){
        /* 初始化表格行对象  */       
        if( !(tr=TableAct._rec(tr,'tr')) ) return false;

        for( var i in cells ){
            /* 不更新单元格数据 */
            if( typeof(cells[i]) == 'undefined' || cells[i] === null ) continue;

            /* 更新单元格数据 */
            tr.cells[i].innerHTML = cells[i];
        }
    },

    /**
     * 删除表格行
     *
     * @params obj  tr  要删除的表格行或者行内元素对象
     */
    delRow : function( tr ){
        /* 初始化表格行对象  */       
        if( !(tr=TableAct._rec(tr,'tr')) ) return false;

        /* 删除表格行 */
        tr.parentNode.removeChild(tr);
    },

    /**
     * 移动行
     *
     * @params obj  tr      要移动的表格行或者行内元素对象
     * @params str  updown  移动方向  - 默认'down'
     * @params int  ulimit  上移限制  - 默认0
     * @params int  dlimit  下移限制  - 默认0
     * @params bol  cyc     是否循环  - 默认false
     * @params int  step    移动步长  - 默认1
     */
    moveRow : function( tr, updown, ulimit, dlimit, cyc, step ){
        /* 初始化参数 */
        cyc    = cyc    == true ? true   : false;
        step   = step   > 0     ? step   : 1;
        ulimit = ulimit > 0     ? ulimit : 0;
        dlimit = dlimit > 0     ? dlimit : 0;
        updown = updown == 'up' ? 'up'   : 'down';

        /* 初始化表格行 */
        if( !(tr=TableAct._rec(tr,'tr')) ) return false;

        /* 初始化表格 */
        var tbl = TableAct._rec(tr, 'table');

        /* 移动行 */
        for( var i=0,len=tbl.rows.length; i < len; i++ ){
            /* 不处理当前行 */
            if( tbl.rows[i] != tr ) continue;

            /* 上移 */
            if( updown == 'up' ){
                if( ulimit <= i-step ){
                    tr.parentNode.insertBefore(tr, tbl.rows[i-step]);
                }
                else if( cyc == true ){
                    tr.parentNode.insertBefore(tr,tbl.rows[tbl.rows.length-step-dlimit+i-ulimit]);
                    tr.parentNode.insertBefore(tbl.rows[tbl.rows.length-step-dlimit+i-ulimit], tr);
                }
            }
            /* 下移 */
            else{
                if( tbl.rows.length-dlimit == i+step+1 ){
                    tbl.rows[i+step].parentNode.insertBefore(tr,tbl.rows[i+step]);
                    tr.parentNode.insertBefore(tbl.rows[i+step], tr);
                }
                else if( tbl.rows.length-dlimit > i+step+1 ){
                    tr.parentNode.insertBefore(tr, tbl.rows[i+step+1]);
                }
                else if( cyc == true ){
                    tr.parentNode.insertBefore(tr, tbl.rows[step+dlimit+i+ulimit-tbl.rows.length]);
                }
            }

            break;
        }
    },


    /* ------------------------------------------------------ */
    // - 表格单元着色
    /* ------------------------------------------------------ */

    /**
     * 为表格增加选择高亮(再次选择则取消高亮)。
     *
     * @params mix  table   需要添加移动高亮的表格对象或者ID
     * @params str  label   要高亮的标签，  默认为tr，通过事件源向上对递归到该标签对象
     * @params str  elabel  触发高亮的标签，默认为td，事件源的标签，只有该标签可以触发高亮
     * @params str  color   高亮的颜色，默认为#FFFDD7
     */
    choiceHiLight : function( table, label, elabel, color ){
        /* 初始化参数 */
        label  = label == 'td' ? 'td' : 'tr';
        table  = typeof(table)  == 'object' ? table  : document.getElementById(table);
        color  = typeof(color)  == 'string' ? color  : '#FFFDD7';
        elabel = typeof(elabel) == 'string' ? elabel : 'td';

        table.onclick = function(e){
            /* 事件源 */
            var obj = window.ActiveXObject ? window.event.srcElement : e.target;;
            
            /* 无效的触发标签 */
            if( obj.tagName.toLowerCase() != elabel.toLowerCase() )return ;
            
            /* 高亮对象 */
            obj = TableAct._rec(obj, label);

            /* 设置背景色，过滤保护色 */
            if( !obj.style.backgroundColor || 
                !(obj.style.backgroundColor.toUpperCase() == TableAct.aProtectColor[0] || 
                  obj.style.backgroundColor.toUpperCase() == TableAct.aProtectColor[1] ) 
            ){
                obj.style.backgroundColor = obj.style.backgroundColor == '' ? color : '';
            }
        }
    },

    /**
     * 为表格增加移动高亮
     *
     * @params mix  table  需要添加移动高亮的表格对象或者ID
     * @params str  label  要高亮的标签，通过事件源向上对递归到该标签对象。默认高亮tr
     * @params str  color  高亮的颜色，默认为#FAFAFA
     */
    moveHiLight : function( table, label, color ){
        /* 初始化参数 */
        label = label == 'td' ? 'td' : 'tr';
        color = typeof(color) == 'string' ? color : '#FAFAFA';
        table = typeof(table) == 'object' ? table : document.getElementById(table);

        table.onmousemove = function(e){
            var obj = window.ActiveXObject ? window.event.srcElement : e.target;
            try{ TableAct._rec(obj,label).bgColor = color; }catch(e){}
        }

        table.onmouseout = function(e){
            var obj = window.ActiveXObject ? window.event.srcElement : e.target;
            try{ TableAct._rec(obj,label).bgColor = ''; }catch(e){}
        }
    },

    /**
     * 高亮指定的对象所在的节点
     *
     * @params mix  obj    事件源
     * @params str  label  要高亮的标签，通过obj向上递归到该标签，然后高亮。默认高亮tr
     * @params str  color  高亮的颜色，默认为#FFFCC7。'protect'表示使用保护色
     */
    hiLight : function( obj, label, color ){
        /* 初始化对象 */
        obj = typeof(obj) == 'object' ? obj : document.getElementById(obj);

        /* 初始化参数 */
        label = label == 'td' ? 'td' : 'tr';
        color = typeof(color) == 'string' ? (color=='protect'?this.aProtectColor[0]:color) : '#FFFCC7';

        try{ TableAct._rec(obj,label).style.backgroundColor = color; }catch(e){}
    },


    /* ------------------------------------------------------ */
    // - 私有函数
    /* ------------------------------------------------------ */

    /**
     * 向上递归寻找指定标签的对象
     *
     * @params obj  obj    源对象
     * @params str  label  目标对象标签
     *
     * @return obj  成功返回目标对象，失败返回NULL
     */
    _rec : function( obj, label ){
        /* 无效对象 */
        if( !obj || typeof(obj) != 'object' ) return null;

        /* 递归查找标签为label的对象 */
        while( obj && obj.tagName && obj.tagName.toLowerCase() != label ){
            obj = obj.parentNode;
        }

        /* 返回目标对象 */
        if( obj && obj.tagName && obj.tagName.toLowerCase() == label ){
            return obj;
        }

        return null;
    }
}