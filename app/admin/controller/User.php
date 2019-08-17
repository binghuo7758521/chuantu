<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;


    class User extends Base
{
    public function user_list(){
        $user_arr = Db::table('user')->field('id,name')->paginate();
        $page = $user_arr->render();
        $this->assign('user_arr',$user_arr);
        $this->assign('page',$page);

        return $this->fetch();
    }

    public function user_add(){
        $json = file_get_contents("php://input");
        $post = json_decode($json,true);

        if($post){
            $username = $post['username'];
            $password = $post['pass'];
            $repass = $post['repass'];

            if(!$username && !$password && !$repass){
                return json(['succ'=>0,'msg'=>'参数错误']);
            }

            if($password !== $repass){
                return json(['succ'=>0,'msg'=>'两次密码不一致']);
            }

            $exist = Db::table('user')->where('name',$username)->find();
            if($exist){
                return json(['succ'=>0,'msg'=>'管理员已存在']);
            }

            $res = Db::table('user')->insert(['name'=>$username,'password'=>md5($password)]);

            if($res){
                return json(['succ'=>1,'msg'=>'添加成功']);
            }else{
                return json(['succ'=>0,'msg'=>'添加失败']);
            }
        }else{
            return $this->fetch();
        }

    }


     public function change_pass(){
         $json = file_get_contents("php://input");
         $post = json_decode($json,true);

         if($post){
             $password = $post['pass'];
             $newpass = $post['newpass'];
             $id = $post['id'];


             if(!$password && !$newpass &&!$id){
                 return json(['succ'=>0,'msg'=>'参数错误']);
             }

             if(md5($password) !== session('user')['password']){
                 return json(['succ'=>0,'msg'=>'密码错误']);
             }

             $res = Db::table('user') ->where('id', $id)->update(['password' => md5($newpass)]);

             if($res){
                 return json(['succ'=>1,'msg'=>'修改成功']);
             }else{
                 return json(['succ'=>0,'msg'=>'修改失败']);
             }
         }else{
             $id = input('param.id',0);

             $this->assign('id',$id);

             return $this->fetch();
         }

     }

     public function deluser(){
         $id = input('param.id',0);

         $res = Db::table('user')->where('id',$id)->delete();

         if($res){
            return json(['succ'=>1,'msg'=>'删除成功']);
         }else{
             return json(['succ'=>0,'msg'=>'删除失败']);
         }

     }




}