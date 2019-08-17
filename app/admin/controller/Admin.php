<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;
use think\validate;

class Admin extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public function admin()
    {
        return $this->fetch();
    }

    public function welcome()
    {
        if(DIRECTORY_SEPARATOR=='\\'){
            $is_win = true;
        }else{
            $is_win = false;
        }
        $sys_arr = get_spec_disk('all');
        $this->assign('sys_arr',$sys_arr);

        $name = session('user')['name'];
        $this->assign('name',$name);
        $this->assign('time',date('Y-m-d H:i:s'));
        return $this->fetch();
    }

    public function orderlist()
    {
        $datestart = input('param.start','');
        $dateend = input('param.end','');
        $username = input('param.username','');
        $mobile = input('param.mobile','');
        $down_tag = input('param.down_tag','');
        $where = [];
        if($datestart){$where[] = ['create_time','>',date('Y-m-d H:i:s',strtotime($datestart))];}
        if($dateend){$where[] = ['create_time','<',date('Y-m-d H:i:s',strtotime($dateend))];}
        if($username){$where[] = ['name','like','%'.$username.'%'];}
        if($mobile){$where[] = ['mobile','like','%'.$mobile.'%'];}
        if($down_tag>0){$where[] = ['down_tag','=',$down_tag-1];}

        if($where){
            $zip_arr = Db::table('photo p')->field('p.*,m.name,m.mobile')->leftJoin('member m','m.id = p.member')->where($where)->order('create_time','desc')->group('img_dir')->paginate(20);
        }else{
            $zip_arr = Db::table('photo')->group('img_dir')->order('create_time','desc')->paginate(20);
        }

        $page = $zip_arr->render();
        $zip_arr = $zip_arr->toArray()['data'];
        foreach($zip_arr as &$zip){
            $zip['bg'] = $zip['down_tag']?(__STATICURL__.'admin/images/213126.png'):(__STATICURL__.'admin/images/213125.png');
        }


        $sys_d = get_spec_disk('d')['d'];
        $point_d = $sys_d[2];
        $int_d = (int)$point_d;
        if($int_d<20){
            $this->assign('show_del_bat',1);
        }else{
            $this->assign('show_del_bat',0);
        }

        $this->assign('page',$page);
        $this->assign('zip_arr',$zip_arr);
        return $this->fetch();
    }

    public function imglist(){
        $id = (int)input('param.id',0);
        $photo_info = Db::table('photo')->where('id',$id)->find();
        if(!$photo_info){
            return json(['succ'=>0,'msg'=>'数据错误']);
        }
        $img_res = Db::name('photo')->where('img_dir', $photo_info['img_dir'])->select();

        $img_arr = [];
        foreach($img_res as $img_info){
            $img['thumb_url'] = __DOWNLOAD__.$img_info['img_dir'].'/thumb/'.$img_info['img_name'];
            $img['url'] = __DOWNLOAD__.$img_info['img_dir'].'/'.$img_info['img_name'];
            $img['name'] = $img_info['img_name'];
            $img['id'] = $img_info['id'];
            $img_arr[] = $img;
        }

        $this->assign('img_num',count($img_arr));
        $this->assign('img_arr',$img_arr);
        return $this->fetch();
    }


    public function downZip()
    {
        session_write_close();
        $id = input('param.id',0);
        $photo_info = Db::table('photo')->where('id',$id)->find();
        $down_url = __UPLOAD__.$photo_info['img_dir'].'/';
        $zip_url = mb_convert_encoding(__UPLOAD__,"gbk", "utf-8").'zip/';
        $down_url = mb_convert_encoding($down_url,"gbk", "utf-8");
        if(!mkpath($zip_url)){
            $data['succ']=0;
            $data['code']='文件夹创建失败';
            return json($data);
        }
//        $files_arr = getFiles($down_url);
        $photo_arr = Db::table('photo')->where('img_dir',$photo_info['img_dir'])->column('img_name');
        foreach($photo_arr as &$img){
            $img = mb_convert_encoding($img,"gbk", "utf-8");
            if(!is_file($down_url.$img)){
                $data['succ']=0;
                $data['code']='部分图片缺失'.$down_url.$img;
                return json($data);
            }
        }

        $files = [];
        foreach($photo_arr as $file){
            $files[] = $down_url.$file;
        }
        if(!$files){
            return json(['succ'=>0,'msg'=>'文件夹为空']);
        }
        $photo_info['img_dir'] = mb_convert_encoding($photo_info['img_dir'],"gbk", "utf-8");

        $this->downedZip($id);
        $zip_url = zipDown($files,$zip_url,$photo_info['img_dir']);
        $zip_url = 'http://'.$_SERVER['SERVER_NAME'].$zip_url;
        $zip_url=Download($zip_url);
        $url_thunder="thunder://".base64_encode("AA".$zip_url."ZZ");//base64加密，下面的2也一样

        $result['zipurl'] = $url_thunder;
        return json($result);
    }

    public function downedZip($id = 0){
        $id = $id?:(int)input('param.id',0);
        $photo_info = Db::table('photo')->where('id',$id)->find();
        if(!$photo_info){
            return json(['succ'=>0,'msg'=>'数据错误']);
        }
        Db::name('photo')->where('img_dir', $photo_info['img_dir'])->update(['down_tag' => 1]);
        return json(['succ'=>1,'msg'=>'成功']);
    }
    public function nodownedZip($id = 0){
        $id = $id?:(int)input('param.id',0);
        $photo_info = Db::table('photo')->where('id',$id)->find();
        if(!$photo_info){
            return json(['succ'=>0,'msg'=>'数据错误']);
        }
        Db::name('photo')->where('img_dir', $photo_info['img_dir'])->update(['down_tag' => 0]);
        return json(['succ'=>1,'msg'=>'成功']);
    }

    public function delZip($id = 0){
        $id = $id?:(int)input('param.id',0);
        $photo_info = Db::table('photo')->where('id',$id)->find();
        if(!$photo_info){
            return json(['succ'=>0,'msg'=>'数据错误']);
        }
        $dir_path = __UPLOAD__.$photo_info['img_dir'];
        $dir_path = mb_convert_encoding ($dir_path,'GBK','UTF-8');
        $is_succ = deldir($dir_path);
        if($is_succ){
            Db::table('photo')->where('img_dir',$photo_info['img_dir'])->delete();
            return json(['succ'=>1,'msg'=>'成功']);
        }else{
            return json(['succ'=>0,'msg'=>'删除失败']);
        }
    }







}



