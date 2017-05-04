<?php
/**
 * 框架单入口
 * User: Dean.Lee
 * Date: 16/9/12
 */

// 检测环境
if(version_compare(PHP_VERSION,'7.0.0','<'))
    die('php version must be >= v7.0.0!' . PHP_EOL);
if((substr(swoole_version(),0,1) == 1 && version_compare(swoole_version(),'1.9.6','<'))
    || (substr(swoole_version(),0,1) == 2 && version_compare(swoole_version(),'2.0.7','<')))
    die('swoole version must be >= v1.9.6 or >= v2.0.7!' . PHP_EOL);

require './libs/root.php';
\Root::run();
