# Api数据统计中心
基于swoole开发，UDP上报数据，对业务无影响

##实现功能：
* Api接口调用统计
* 日志分析
* 实时报警

##php接口调用方法

```php
//统计开始
$instance = StatisticClient::instance('192.168.1.202:9603');
Statistic:tick();
```

```php
//上报数据
Statistic:report($module, $interface, $success, $msg);
```
