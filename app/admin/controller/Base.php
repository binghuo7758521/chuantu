<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;
use think\validate;

class Base extends Controller
{

    public function __construct()
    {
        if(!session('?user')){
            session(null);
            $this->error('当前账号登录过期','login/login');
        }
        parent::__construct();
    }





}



