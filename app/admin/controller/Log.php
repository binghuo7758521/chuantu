<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;


    class Log extends Base
{
    public function log_list(){
        $size_arr = Db::table('photo_size')->column('name','id');
        $this->assign('size_arr', $size_arr ?: []);

//        $datestart = input('param.start','');
//        $dateend = input('param.end','');
//        $username = input('param.username','');
        $mobile = input('param.mobile','');
        $photo_size = input('param.photo_size','');
        $where = [];
//        if($datestart){$where[] = ['create_time','>',date('Y-m-d H:i:s',strtotime($datestart))];}
//        if($dateend){$where[] = ['create_time','<',date('Y-m-d H:i:s',strtotime($dateend))];}
//        if($username){$where[] = ['name','like','%'.$username.'%'];}
        if($mobile){$where[] = ['mobile','like','%'.$mobile.'%'];}
        if($photo_size){$where[] = ['photo_size','=',$photo_size];}

        if($where){
            $log_arr = Db::table('photo_log')->where($where)->order('create_time','desc')->paginate(20);
        }else{
            $log_arr = Db::table('photo_log')->order('create_time','desc')->paginate(20);
        }

        $page = $log_arr->render();
        $log_arr = $log_arr->toArray()['data'];

        $this->assign('page',$page);
        $this->assign('log_arr',$log_arr);
        return $this->fetch();
    }




}