# php代码备份

## 工具类

放在ToolClass目录下

- Muticurl PHP多线程Curl

  使用方法 :
  
  ```php
  $muti_curl = new MutiCurl();
  //请求参数
  $target = [
                //请求头信息
                'head' => ['Content-Type:application/json'],
                //请求方式
                'method' => 'POST',
                //请求地址
                'url' => 'http://www.example.com/',
                //post提交参数
                'param' => [
                    'type' => 1,
                ],
            ];
  //回调函数
  $callback = function ($response) {
    //处理返回值逻辑代码
    ...
  };
  //线程池数量
  $threads = 10;
  $muti_curl->setTargets($target)->setThreads($threads)->setCallback($callback);
  ```

