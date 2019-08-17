<?php 

 
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

//压缩文件,传入文件夹全路径
function zipDown($files, $upload_url, $dir_name)
{
    //$files = array('upload/qrcode/1/1.jpg');
  echo "开始zipdown";
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
	
    $zipName =iconv('UTF-8','GB2312',$zipName);
   

    if ($zip->open($zipName, \ZipArchive::CREATE | \ZIPARCHIVE::OVERWRITE) !== TRUE) {
        exit('无法打开文件，或者文件创建失败');
    } 
 
    foreach ($files as $val) {
        //$attachfile = $attachmentDir . $val['filepath']; //获取原始文件路径
  

          echo "val:".$val;
        
         $val =iconv('UTF-8','GB2312',$val);
          
        if (file_exists($val)) {
            //addFile函数首个参数如果带有路径，则压缩的文件里包含的是带有路径的文件压缩
            //若不希望带有路径，则需要该函数的第二个参数        	

            $zip->addFile($val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }else
        {
        	echo "文件不存在:".$val;	
        }
    }
    $a = $zip->close();//关闭
    
    if (!file_exists($zipName)) {
        exit("无法找到文件"); //即使创建，仍有可能失败
    }
    //$dir_name = mb_convert_encoding($dir_name, "utf-8", "gbk");
    $zip_url = '/upload/'. 'zip/' . $dir_name . '.zip';
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


ini_set('display_errors', '0');
/**
 * Created by PhpStorm.
 * User:  旅途
 * Date: 2018/11/5
 * Time: 2:30
 */
ini_set('date.timezone','Asia/Shanghai');



$con = mysqli_connect("localhost","root","123456","photo");//数据库账号密码

if (mysqli_connect_errno($con))
{
    die('Could not connect: ' . mysql_error());
}
 //session_write_close();
mysqli_select_db($con,"photo");
//mysqli_query($con,"set names gbk");
$result = mysqli_query($con,"select * from photo where down_tag=0 and  timestampdiff(hour,create_time, now()) >24 order by create_time   limit 1 ");

while($row = mysqli_fetch_assoc($result))
{
    $file_date = substr($row['img_dir'],0,8);
    $file_time = strtotime($file_date);
    $img_dir = $row['img_dir'];
    //if($file_time < (time()-(10*86400))){  //10天
        $fullpath = __DIR__.'/upload/'.$img_dir;
        //deldir($fullpath);
       // file_put_contents('delete_log.txt','LOG:'.mb_convert_encoding($fullpath, "utf-8", "gbk").PHP_EOL,FILE_APPEND);
        //$sql = "DELETE FROM photo WHERE img_dir='".$img_dir."'";
       // $res = mysqli_query($con,$sql);
       // file_put_contents('delete_log.txt','sql:'.mb_convert_encoding($sql, "utf-8", "gbk").PHP_EOL,FILE_APPEND);
    //}
     //echo  $fullpath ;

        $down_url = __DIR__.'/upload/'.$row['img_dir'].'/';
        $zip_url = mb_convert_encoding(__DIR__,"gbk", "utf-8").'/upload/'.'zip/';
        //$zip_url = mb_convert_encoding(__DIR__,"gbk", "utf-8").'zip/';
        //$down_url = mb_convert_encoding($down_url,"gbk", "utf-8");
        if(!mkpath($zip_url)){
            $data['succ']=0;
            $data['code']='文件夹创建失败';
            echo  '文件夹创建失败';
            return json($data);
        }

        

       $photo_sql =mysqli_query($con,"select img_name from photo where img_dir='".$row[img_dir]."'");


        while($photo_arr = mysqli_fetch_assoc($photo_sql)){
        	$files[]=$down_url.$photo_arr['img_name'];

        }
        

        if(!$files){

        	
            return json(['succ'=>0,'msg'=>'文件夹为空']);
        }
      //  $photo_arr['img_dir'] = mb_convert_encoding($photo_info['img_dir'],"gbk", "utf-8");
        

		$zip_url = zipDown($files,$zip_url,$row['img_dir']);
        $zip_url = 'http://'.$_SERVER['SERVER_NAME'].$zip_url;	    
        echo  'zip_ok:<'.$zip_url.'>' ;
        $res_update=mysqli_query($con,"update photo set down_tag=1 where img_dir='".$row[img_dir]."'");
        if (!$res_update){
        	//echo "更新下载状态失败";
        }
     
}


mysqli_close($con);











 ?>