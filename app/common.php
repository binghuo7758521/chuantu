<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function mkpath($path)
{
    if (!is_dir($path) && $path != './' && $path != '../') {
        if (!mkdir($path, 0777)) {
            return false;
        }
        chmod($path, 0777);
        return true;
    }
    return true;
}


function img2jpg($img_url)
{
    $img_info = explode('.', $img_url);
    $format_false = end($img_info);
    $info = getimagesize($img_url);
    if ($mime = $info['mime']) {
        $format = explode('/', $mime)[1];
    } else {
        return false;
    }
    if (in_array($format, ['jpg', 'JPG', 'jpeg', 'JPEG'])) {
        $key = key($img_info);
        $img_info[$key] = 'jpg';
        $new_img_url = implode('.', $img_info);
        if (!in_array($format_false, ['jpg'])) {
            $a = @copy($img_url, $new_img_url);
            if (!$a) {
                return false;
            }
        }
        return true;
    } else {
        $key = key($img_info);
        $format_arr = [
            'gif' => 'imagecreatefromgif',
            'GIF' => 'imagecreatefromgif',
            'jpg' => 'imagecreatefromjpeg',
            'JPG' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng',
            'PNG' => 'imagecreatefrompng',
            'bmp' => 'imagecreatefromwbmp',
            'BMP' => 'imagecreatefromwbmp',
            'jpeg' => 'imagecreatefromjpeg',
            'JPEG' => 'imagecreatefromjpeg',
        ];
        if (!array_key_exists($format, $format_arr)) {
            return false;
        }
        if (!file_exists($img_url)) {
            return false;
        }
        $im = @$format_arr[$format]($img_url);

        $srcW = @ImageSX($im);
        $srcH = @ImageSY($im);
        $ni = @imageCreateTrueColor($srcW, $srcH);
        @ImageCopyResampled($ni, $im, 0, 0, 0, 0, $srcW, $srcH, $srcW, $srcH);

        $img_info[$key] = 'jpg';
        $new_img_url = implode('.', $img_info);
        $res = imagejpeg($ni, $new_img_url);
        imagedestroy($im);
        imagedestroy($ni);
        unset($im);
        unset($ni);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }
}

//压缩文件,传入文件夹全路径
function zipDown($files, $upload_url, $dir_name)
{
    //$files = array('upload/qrcode/1/1.jpg');
    $zipName = $upload_url . $dir_name . '.zip';
    $zip = new \ZipArchive;//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释

    /*
     * 通过ZipArchive的对象处理zip文件
     * $zip->open这个方法如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
     * $zip->open这个方法第一个参数表示处理的zip文件名。
     * 这里重点说下第二个参数，它表示处理模式
     * ZipArchive::OVERWRITE 总是以一个新的压缩包开始，此模式下如果已经存在则会被覆盖。
     * ZIPARCHIVE::CREATE 如果不存在则创建一个zip压缩包，若存在系统就会往原来的zip文件里添加内容。
     *
     * 这里不得不说一个大坑。
     * 我的应用场景是需要每次都是创建一个新的压缩包，如果之前存在，则直接覆盖，不要追加
     * so，根据官方文档和参考其他代码，$zip->open的第二个参数我应该用 ZipArchive::OVERWRITE
     * 问题来了，当这个压缩包不存在的时候，会报错：ZipArchive::addFile(): Invalid or uninitialized Zip object
     * 也就是说，通过我的测试发现，ZipArchive::OVERWRITE 不会新建，只有当前存在这个压缩包的时候，它才有效
     * 所以我的解决方案是 $zip->open($zipName, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE)
     *
     * 以上总结基于我当前的运行环境来说
     * */


    if ($zip->open($zipName, \ZipArchive::CREATE | \ZIPARCHIVE::OVERWRITE) !== TRUE) {
        exit('无法打开文件，或者文件创建失败');
    }
    foreach ($files as $val) {
        //$attachfile = $attachmentDir . $val['filepath']; //获取原始文件路径
        if (file_exists($val)) {
            //addFile函数首个参数如果带有路径，则压缩的文件里包含的是带有路径的文件压缩
            //若不希望带有路径，则需要该函数的第二个参数

            $zip->addFile($val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }
    }
    $a = $zip->close();//关闭

    if (!file_exists($zipName)) {
        exit("无法找到文件"); //即使创建，仍有可能失败
    }
    $dir_name = mb_convert_encoding($dir_name, "utf-8", "gbk");
    $zip_url = __DOWNLOAD__ . 'zip/' . $dir_name . '.zip';
    return $zip_url;

//    $filesize = filesize($zipName);
//    //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
//    header("Cache-Control: public");
//    header("Content-Description: File Transfer");
//    header('Content-disposition: attachment; filename='.mb_convert_encoding(basename($zipName),"utf-8","gbk")); //文件名
//    header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
//    header('Content-Length: '. $filesize); //告诉浏览器，文件大小
//    header('content-type:application/octet-stream');
//    header('accept-ranges: bytes');
//    ob_clean();
//    flush();
//    $read_buffer = 1024;
//    $sum_buffer = 0;
//    $handle = fopen($zipName,'rb');
//    while(!feof($handle) && $sum_buffer<$filesize){
//        echo fread($handle,$read_buffer);
//        $sum_buffer+=$read_buffer;
//        flush(); //输出缓冲
//        ob_flush();
//    }
//    fclose($handle);
    exit();
}


function getFiles($dir)
{
    $file_arr = [];
    if (is_dir($dir)) {
        //打开
        if ($dh = @opendir($dir)) {
            //读取

            while (($file = readdir($dh)) !== false) {

                if ($file != '.' && $file != '..') {
                    $file_arr[] = $file;
                }

            }
            //关闭
            closedir($dh);
        }
    }
    return $file_arr;
}

//删除目录下的文件：
function deldir($dir)
{
    //先删除目录下的文件：
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != "." && $file != "..") {
            $fullpath = $dir . "/" . $file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }

    closedir($dh);
    //删除当前文件夹：
    if (rmdir($dir)) {
        return true;
    } else {
        return false;
    }
}


function byte_format($size, $dec = 2)
{
    $a = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec) . " " . $a[$pos];
}

/**
 * 取得单个磁盘信息
 * @param $letter
 * @return array
 */
function get_disk_space($letter)
{
    //获取磁盘信息
    $diskct = 0;
    $disk = array();
    /*if(@disk_total_space($key)!=NULL) *为防止影响服务器，不检查软驱
 {
 $diskct=1;
 $disk["A"]=round((@disk_free_space($key)/(1024*1024*1024)),2)."G / ".round((@disk_total_space($key)/(1024*1024*1024)),2).'G';
 }*/
    $diskz = 0; //磁盘总容量
    $diskk = 0; //磁盘剩余容量

    $is_disk = $letter . ':';
    if (@disk_total_space($is_disk) != NULL) {
        $diskct++;
        $disk[$letter][0] = byte_format(@disk_free_space($is_disk));
        $disk[$letter][1] = byte_format(@disk_total_space($is_disk));
        $disk[$letter][2] = round(((@disk_free_space($is_disk) / (1024 * 1024 * 1024)) / (@disk_total_space($is_disk) / (1024 * 1024 * 1024))) * 100, 2) . '%';
        $diskz += byte_format(@disk_total_space($is_disk));
    }
    return $disk;
}

/**
 * 取得磁盘使用情况
 * @return
 */
function get_spec_disk($type = 'system')
{
    $disk = array();
    switch ($type) {
        case 'system':
            //strrev(array_pop(explode(':',strrev(getenv_info('SystemRoot')))));//取得系统盘符
            $disk = @get_disk_space(strrev(array_pop(explode(':', strrev(getenv('SystemRoot'))))));
            break;
        case 'all':
            foreach (range('b', 'z') as $letter) {
                $disk = array_merge($disk, get_disk_space($letter));
            }
            break;
        default:
            $disk = get_disk_space($type);
            break;
    }

    return $disk;
}

function Download($url)
{
    $urlodd = explode('//', $url, 2);//把链接分成2段，//前面是第一段，后面的是第二段
    $head = strtolower($urlodd[0]);//PHP对大小写敏感，先统一转换成小写，不然 出现HtTp:或者ThUNDER:这种怪异的写法不好处理
    $behind = $urlodd[1];
    if ($head == "thunder:") {
        $url = substr(base64_decode($behind), 2, -2);//base64解密，去掉前面的AA和后面ZZ
    } elseif ($head == "flashget:") {
        $url1 = explode('&', $behind, 2);
        $url = substr(base64_decode($url1[0]), 10, -10);//base64解密，去掉前面后的[FLASHGET]
    } elseif ($head == "qqdl:") {
        $url = base64_decode($behind);//base64解密
    } elseif ($head == "http:" || $head == "ftp:" || $head == "mms:" || $head == "rtsp:" || $head == "https:") {
        $url = $url;//常规地址仅支持http,https,ftp,mms,rtsp传输协议，其他地貌似很少，像XX网盘实际上也是基于base64，但是有的解密了也下载不了
    } else {
        echo "本页面暂时不支持此协议";
    }
    return $url;
}


function curlget($url,$header=array()){
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 10);
    if($header){
        $header_ary=array();
        foreach ($header as $k=>$v){
            $header_ary[]="$k: $v";
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_ary );
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $res = curl_exec($ch);
    curl_close($ch);
    $json=json_decode($res,true);
    return $json?:$res;
}

