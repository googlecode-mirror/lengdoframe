// +----------------------------------------------------------------------
// | LengdoFrame - Ajax对象
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


var Ajax = {
    /**
     * 传输完毕后自动调用的方法，优先级比用户从run()方法中传入的回调函数高
     */
    onComplete : function(){},

    /**
     * 传输过程中自动调用的方法
     */
    onRunning : function(){},

    /**
     * 调用此方法发送HTTP请求。
     *
     * @params str  url           请求的URL地址
     * @params str  params        发送参数 (字符串)
     * @params fun  callback      回调函数
     * @params str  ransferMode   请求的方式，有"GET"和"POST"(默认)两种 
     * @params str  responseType  响应类型，有"JSON"、"XML"和"TEXT"(默认)三种
     * @params bol  asyn          是否异步等待(默认是)
     * @params bol  quiet         是否安静模式请求(默认false)
     */
    call : function( url, params, callback, transferMode, responseType, asyn, quiet ){
        /* 初始化参数 */
        transferMode = typeof(transferMode) === 'string' && transferMode.toUpperCase() === 'GET' ? 'GET' : 'POST';

        if( transferMode === 'GET' ){
            url += params ? (url.indexOf("?") === -1 ? '?' : '&') + params : '';
            url  = encodeURI(url) + (url.indexOf('?') === -1 ? '?' : '&') + Math.random();
        }

        asyn = asyn === false ? false : true;
        responseType = typeof(responseType) === 'string' && ((responseType = responseType.toUpperCase()) === 'JSON' || responseType === 'XML') ? responseType : 'TEXT';

        /* 处理HTTP请求和响应 */
        var xhr = this.createXMLHttpRequest();

        try{
            var self = this;

            if( typeof(self.onRunning) === 'function' && !quiet ){
                self.onRunning();
            }

            xhr.open(transferMode, url, asyn);

            if( transferMode === 'POST' ){
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            }

            /* 异步请求 - 异步等待 */
            if( asyn ){
                xhr.onreadystatechange = function (){
                    /**
                     * 0 "未初始化"状态 : 已经创建一个XMLHttpRequest对象，但是还没有初始化
                     * 1 "发送"状态     : 代码已经调用了XMLHttpRequest open()方法并且XMLHttpRequest已经准备好把一个请求发送到服务器
                     * 2 "发送"状态     : 已经通过send()方法把一个请求发送到服务器端，但是还没有收到一个响应
                     * 3 "正在接收"状态 : 已经接收到HTTP响应头部信息，但是消息体部分还没有完全接收结束
                     * 4 "已加载"状态   : 响应已经被完全接收
                     **/
                    if( xhr.readyState == 4 ){
                        switch( xhr.status ){
                            case 0:
                            case 200: // OK!
                                if( typeof(self.onComplete) === 'function' ){
                                    self.onComplete();
                                }

                                if( typeof(callback) === 'function' ){
                                    callback.call(self, self.parseResult(responseType, xhr), xhr.responseText);
                                }
                            break;

                            default:
                                /* 请求异常 */
                                wnd_alert('XmlHttpRequest error status: ['+ xhr.status +']');

                                /* 请求完成 */
                                Ajax.onComplete();
                        }

                        xhr = null;
                    }
                }

                if( xhr != null ) xhr.send(params);
            }

            /* 异步请求 - 同步等待 */
            else{                
                if( typeof(self.onRunning) === 'function' && !quiet ){
                    self.onRunning();
                }

                xhr.send(params); // ...停顿，直至send结束再执行下面代码。

                var result = self.parseResult(responseType, xhr);

                if( typeof(self.onComplete) === 'function' ){
                    self.onComplete();
                }
                if( typeof(callback) === 'function' ){
                    callback.call(self, result, xhr.responseText);
                }              

                return result;
            }
        }catch( ex ){
            if( typeof(self.onComplete) === 'function' ){
                self.onComplete();
            }

            alert(this.filename +'/call() error:'+ ex.description);
        }
    },

    /**
     * 创建XMLHttpRequest对象的方法。
     *
     * @return obj  返回一个XMLHttpRequest对象
     */
    createXMLHttpRequest : function (){
        var xhr = null;

        if( window.ActiveXObject ){
            var versions = ['Microsoft.XMLHTTP', 'MSXML6.XMLHTTP', 'MSXML5.XMLHTTP', 'MSXML4.XMLHTTP', 'MSXML3.XMLHTTP', 'MSXML2.XMLHTTP', 'MSXML.XMLHTTP'];

            for( var i=0,len=versions.length; i < len; i++ ){
                try{
                    xhr = new ActiveXObject(versions[i]); break;
                }catch(ex){
                    continue;
                }
            }
        }else{
            xhr = new XMLHttpRequest();
        }

        return xhr;
    },

    /**
     * 当传输过程发生错误时将调用此方法。
     *
     * @params obj  xhr  XMLHttpRequest对象
     * @params str  url  HTTP请求的地址
     */
    onXMLHttpRequestError : function( xhr, url ){
        alert( 'URL: '+ url +"\n"+
               'state: '+ xhr.status +"\n"+
               'headers: '+ xhr.getAllResponseHeaders()+
               'readyState: '+ xhr.readyState +"\n");
    },

    /**
     * 对返回的HTTP响应结果进行过滤。
     *
     * @params mix  result  HTTP响应结果
     * @return str  返回过滤后的结果
     */
    preFilter : function( result ){
        return result.replace(/\xEF\xBB\xBF/g, '');
    },

    /**
     * 对返回的结果进行格式化。
     *
     * @return mix  返回特定格式的数据结果
     */
    parseResult : function( responseType, xhr ){
        var result = null;

        switch( responseType ){
            case 'JSON' :
                result = this.preFilter(xhr.responseText);

                try{
                    result = eval('('+result+')');
                }catch(ex){
                    alert(this.filename +"/parseResult() error: can't parse to JSON.\n\n"+ xhr.responseText);
                }
            break;

            case "XML" :
                result = xhr.responseXML; break;

            case "TEXT" :
                result = this.preFilter(xhr.responseText); break;

            default :
                alert(this.filename +"/parseResult() error: unknown response type:"+ responseType);
        }

        return result;
    }
}


/* 加载状态显示 */
Ajax.onRunning = function(){
    var obj = document.getElementById('aloading-div'); 

    try{
        /* 定位 */
        if( obj.style.top == '' ){
            obj.style.top  = (document.body.offsetHeight - 9)/2  +'px';
            obj.style.left = (document.body.offsetWidth - 140)/2 +'px';
        }

        /* 显示 */
        obj.style.display = '';
    }catch(e){}
}

/* 完成状态显示 */
Ajax.onComplete = function(){
    try{
       document.getElementById('aloading-div').style.display = 'none';
    }catch(e){}
}