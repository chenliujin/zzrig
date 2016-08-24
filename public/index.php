<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

require('chenliujin/mysql/Model.class.php');

/*
function zzrig_model($class)
{

    $file = 'zzrig/model/' . $class . '.php';

    require($file);
}

spl_autoload_register('zzrig_model', FALSE, FALSE);
 */

define('ORDER_STATUS_PENDING', 0);

require_once('/opt/php/lib/php/zzrig/model/product.php');
require_once('/opt/php/lib/php/zzrig/model/product_description_en.php');
require_once('/opt/php/lib/php/zzrig/model/shopping_cart.php');

Model::$dbo = new DB;

// [ 应用入口文件 ]

define('APP_DEBUG',True);

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';


