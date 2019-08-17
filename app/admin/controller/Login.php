<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;
use think\validate;

class Login extends Controller
{

    public function login()
    {
        return $this->fetch();
    }

    public function login_check()
    {
        $backcode = 1;
        $msg = '';
        $json = file_get_contents("php://input");
        $post = json_decode($json,true);
        $username = $post['username'];
        $password = $post['password'];
        if(!$username || !$password){
            $backcode = 0;
            $msg = '请输入账号密码';
        }
        if($backcode){
            $user_info = Db::table('user')->where(['name'=>$username])->find();
            if($user_info && $user_info['password'] === md5($password)){
                session('user',$user_info);
            }else{
                $backcode = 0;
                $msg = '账号或密码有误';
            }
        }

        return json(['code'=>$backcode,'msg'=>$msg]);

    }





}



