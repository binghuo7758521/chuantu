<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;
use think\validate;

class Index extends Controller
{

    public function index()
    {
        //判断是否服务器内存不足
       /* $sys_d = get_spec_disk('d')['d'];
        $point_d = $sys_d[2];
        $int_d = (int)$point_d;
        if($int_d<20){
            curlget($_SERVER['HTTP_HOST'].'/del_10file.php');
        }

        $sys_c = get_spec_disk('c')['c'];
        $point_c = $sys_c[2];
        $int_c = (int)$point_c;
        if($int_c<20){
            curlget($_SERVER['HTTP_HOST'].'/del_10file.php');
        }*/

        session(null);

        $size_arr = Db::table('photo_size')->where('delete',0)->select();
//        $type_arr = Db::table('photo_type')->select();

        $this->assign('size_arr', $size_arr ?: []);
//        $this->assign('type_arr',$type_arr?:[]);
        return $this->fetch();
    }

    public function checkUp()
    {
        $backcode = 1;
        $msg = '上传图片';

//        $type = input('param.type','');
        $size = input('param.size', '');
        $username = input('param.username', '');
        $mobile = input('param.phone', '');

//        if(!$size || !$username ||!$mobile || !$type){
        if (!$size || !$username || !$mobile) {
            $backcode = 0;
//            $msg = '参数不完整(相纸,尺寸,姓名,手机号)';
            $msg = '参数不完整(尺寸,姓名,手机号)';
        }
        if ($backcode) {
            session('username', $username);
            session('mobile', $mobile);
            session('size', $size);
//            session('type',$type);
        }

        $res['code'] = $backcode;
        $res['msg'] = $msg;

        return json($res);
    }

    public function upCheck()
    {
        $backcode = 1;
        $msg = '';

        $username = session('username');
        $mobile = session('mobile');
        $size = session('size');
//        $type = session('type');
//        if(!$size || !$username ||!$mobile || !$type){
        if (!$size || !$username || !$mobile) {
            return redirect('index/index');
        }

        $member_arr = Db::table('member')->where(['mobile' => $mobile, 'name' => $username])->find();

        $image_url_arr = [];
        if ($member_arr) {
            session('mem_id', $member_arr['id']);
            $mem_images = Db::table('photo')->where(['member' => $member_arr['id'], 'photo_size' => $size])->field('id,img_dir,img_name')->select();

            $image_url_arr = [];
            foreach ($mem_images as $image) {
                $image_url_arr[$image['id']]['img_name'] = 'LVTUOLD' . $image['img_name'];
                $image_url_arr[$image['id']]['img_name_url'] = __DOWNLOAD__ . $image['img_dir'] . '/thumb/' . $image['img_name'];
            }
        } else {
            $member_id = $this->regist_member(['username' => $username, 'mobile' => $mobile]);
            session('mem_id', $member_id);
        }
        if (!$image_url_arr) {
            $image_url_arr = [];
        }
        $old_imgs_str = json_encode($image_url_arr, JSON_UNESCAPED_UNICODE);
        $this->assign('old_imgs', $old_imgs_str);

        return $this->fetch('upload');
    }

    public function upload()
    {

        ini_set('max_execution_time', '0');
        ini_set('max_execution_time', '0');
        ini_set('upload_max_filesize ', '100M');
        ini_set('memory_limit   ', '100M');
        ini_set('post_max_size  ', '100M');
        ini_set('max_execution_time  ', 0);

        $runable = 1;


        $username = session('username');
        $mobile = session('mobile');
        $size = session('size');
//        $type = session('type');
//        if(!$username || !$mobile || !$size || !$type){
        if (!$username || !$mobile || !$size) {
            $runable = false;
            $data['succ'] = 0;
            $data['code'] = '用户信息无效';
        }

        if($runable){
            $has_session_dir_name = session('?dir_name');
            if ($has_session_dir_name) {
                $dir_name = session('dir_name');
            } else {
                $size_name = Db::table('photo_size')->where('id', $size)->value('name');
                $dir_name = date('Ymd') . '-' . $username . '-' . $mobile . '-' . $size_name . '-jpg';
                session('dir_name', $dir_name);
            }
            $dir_url = iconv('utf-8', 'gbk', __UPLOAD__ . $dir_name . '/');
            if (!mkpath($dir_url)) {
                $runable = false;
                $data['succ'] = 0;
                $data['code'] = '文件夹创建失败';
            }
        }
        $path = __UPLOAD__;
        $maxSize = '5242880000';

        // 去除两边的/
        $path = trim($path, '/');
        if ($runable && !empty($_FILES)) {
            $photo_name = substr($_FILES['file']['name'], 0, 7);
            if ($photo_name == 'LVTUOLD') {
                $_FILES['file']['name'] = substr($_FILES['file']['name'], 7);
            }

            $exist_img = Db::table('photo')
                ->where('photo_size', session('size'))
                ->where('img_name', $_FILES['file']['name'])
                ->where('member', session('mem_id'))
                ->find();
            if (!$exist_img) {

                $_FILES['file']['name'] = mb_convert_encoding($_FILES['file']['name'], "gbk", "utf-8");
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('file');

                // 移动到框架应用根目录/uploads/ 目录下
                $dir_url = __UPLOAD__ . $dir_name . '/';
                $dir_url = mb_convert_encoding($dir_url, "gbk", "utf-8");
                // $info = $file->validate(['size'=>$maxSize,'ext'=>'bmp'])->move($dir_url,'');
                $info = $file->move($dir_url, $_FILES['file']['name']);
                //var_dump($info);
                 //console.log('文件名：'.$_FILES['file']['name'])   ;
                if ($info) {
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    // echo $info->getExtension();
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    // echo $info->getSaveName();

                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    $file_name = $info->getFilename();

                    //存储新增张数
                    if (session('?upload_num')) {
                        session('upload_num', session('upload_num') + 1);
                    } else {
                        session('upload_num', 1);
                    }

                    //生成缩略图
                    if (!mkpath($dir_url . 'thumb/')) {
                        $runable = false;
                        $data['succ'] = 0;
                        $data['code'] = '文件夹创建失败';
                    }

                    if($runable){
                        $thumb_url = $dir_url . 'thumb/' . $file_name;
                        $image = \think\Image::open($dir_url . $file_name);
                        // 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.png
                        $image->thumb(150, 150, 6)->save($thumb_url);

                        $new_file_name = mb_convert_encoding($file_name, "utf-8", "gbk");

                        $photo_data = [
                            'create_time' => date('Y-m-d H:i:s'),
                            'member' => session('mem_id'),
                            //                        'photo_type'=>$type,
                            'photo_size' => $size,
                            'img_dir' => $dir_name,
                            'img_name' => $new_file_name
                        ];
                        $photo_id = Db::name('photo')->insertGetId($photo_data);
                        //格式转换
                        if (!$photo_id) {
                            $runable = false;
                            $data['succ'] = 0;
                            $data['code'] = '存储记录失败';
                        }
                    }

                    if($runable){
                        $data['succ'] = 1;
                        $data['code'] = $photo_id;
                    }

                } else {
                    // 上传失败获取错误信息
                    $data['succ'] = 0;
                    $data['code'] = $file->getError();
                }
            } else {
                $data['succ'] = 1;
                $data['code'] = '';
            }
        }
        if(!$data['succ']){
            $log_data = ['mobile' => $mobile, 'photo_size' => $size, 'msg' => $data['code'], 'create_time' => time()];
            Db::table('photo_log')->insert($log_data);
        }

        return json($data);
    }

    public function succ()
    {
        $username = session('username');
        $size = session('size');
        $size_name = Db::table('photo_size')->where('id', $size)->value('name');
        $num = session('upload_num');

        $this->assign('order', $username);
        $this->assign('size', $size_name);
        $this->assign('num', $num);

        return $this->fetch();
    }


    public function del_photo()
    {
        $photo_name = input('param.photo_name', 0);
        if (!$photo_name) {
            $data['succ'] = 0;
            $data['code'] = '参数错误';
            return json($data);
        }
        $photo_name = substr($photo_name, 7);
        $photo_info = Db::table('photo')->where(['img_name' => $photo_name, 'photo_size' => session('size'), 'member' => session('mem_id')])->find();
        $succ = Db::table('photo')->where(['img_name' => $photo_name, 'photo_size' => session('size'), 'member' => session('mem_id')])->delete();
        unlink(__UPLOAD__ . $photo_info['img_dir'] . '/' . $photo_info['img_name']);
        unlink(__UPLOAD__ . $photo_info['img_dir'] . '/thumb/' . $photo_info['img_name']);
        if (!$succ) {
            $data['succ'] = 0;
            $data['code'] = '删除失败';
            return json($data);
        } else {
            $data['succ'] = 1;
            $data['code'] = '删除成功';
            return json($data);
        }
    }


    private function regist_member($info)
    {
        $name = $info['username'];
        $mobile = $info['mobile'];
        $data['name'] = $name;
        $data['mobile'] = $mobile;
        $userId = Db::name('member')->insertGetId($data);
        return $userId;
    }


}