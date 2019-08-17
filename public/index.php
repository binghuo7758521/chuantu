<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

ini_set ('gd.jpeg_ignore_warning', 1);


// 加载基础文件
require dirname(__DIR__) . '/thinkphp/base.php';

// 定义应用目录
define('APP_PATH', dirname(__DIR__) . '/app/');
define('__STATIC__', '/static/');
define('__UPLOAD__', __DIR__ . '/upload/');
define('__DOWNLOAD__', '/upload/');
define('ImageMagick', dirname(__DIR__).'/exe/ImageMagick-7.0.8-Q16/magick.exe');

// 支持事先使用静态方法设置Request对象和Config对象

// 执行应用并响应
Container::get('app', [APP_PATH])->run()->send();
