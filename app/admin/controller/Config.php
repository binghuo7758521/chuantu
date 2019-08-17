<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;


    class Config extends Base
{
    public function size_list(){
        $size_arr = Db::table('photo_size')->field('id,name')->where('delete',0)->paginate();
        $page = $size_arr->render();
        $this->assign('size_arr',$size_arr);
        $this->assign('page',$page);

        return $this->fetch();
    }

    public function size_add(){
        $json = file_get_contents("php://input");
        $post = json_decode($json,true);

        if($post){
            $size_name = $post['size_name'];

            if(!$size_name){
                return json(['succ'=>0,'msg'=>'参数错误']);
            }

            $exist = Db::table('photo_size')->where('name',$size_name)->where('delete',0)->find();
            if($exist){
                return json(['succ'=>0,'msg'=>'尺寸已存在']);
            }

            $res = Db::table('photo_size')->insert(['name'=>$size_name]);

            if($res){
                return json(['succ'=>1,'msg'=>'添加成功']);
            }else{
                return json(['succ'=>0,'msg'=>'添加失败']);
            }
        }else{
            return $this->fetch();
        }

    }


     public function change_size(){
         $json = file_get_contents("php://input");
         $post = json_decode($json,true);

         if($post){
             $newsizename = $post['newsizename'];
             $id = $post['id'];

             if(!$newsizename || !$id){
                 return json(['succ'=>0,'msg'=>'参数错误']);
             }

             $exist = Db::table('photo_size') ->where('name', $newsizename)->find();

             if($exist){
                 return json(['succ'=>0,'msg'=>'已存在']);
             }

             $res = Db::table('photo_size') ->where('id', $id)->update(['name' => $newsizename]);

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

     public function delsize(){
         $id = input('param.id',0);

         $res = Db::table('photo_size') ->where('id', $id)->update(['delete' => 1]);

         if($res){
            return json(['succ'=>1,'msg'=>'删除成功']);
         }else{
             return json(['succ'=>0,'msg'=>'删除失败']);
         }

     }




}