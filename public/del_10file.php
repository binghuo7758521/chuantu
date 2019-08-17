<?php
ini_set('display_errors', '0');
/**
 * Created by PhpStorm.
 * User: 旅途
 * Date: 2018/9/27
 * Time: 2:30
 */
ini_set('date.timezone','Asia/Shanghai');

$con = mysqli_connect("localhost","root","123456","photo");//数据库账号密码

if (mysqli_connect_errno($con))
{
    die('Could not connect: ' . mysql_error());
}

mysqli_select_db($con,"photo");

mysqli_query($con,"set names gbk");
$result = mysqli_query($con,"SELECT	* FROM	photo GROUP BY	img_dir order by id  LIMIT 10");

while($row = mysqli_fetch_assoc($result))
{
    $file_date = substr($row['img_dir'],0,8);
    $file_time = strtotime($file_date);
    $img_dir = $row['img_dir'];
    if($file_time < (time()-(10*86400))){  //10天
        $fullpath = __DIR__.'/upload/'.$img_dir;
        deldir($fullpath);
        file_put_contents('delete_log.txt','LOG:'.mb_convert_encoding($fullpath, "utf-8", "gbk").PHP_EOL,FILE_APPEND);
              echo mb_convert_encoding($fullpath, "utf-8", "gbk");
        $sql = "DELETE FROM photo WHERE img_dir='".$img_dir."'";
         echo mb_convert_encoding($sql, "utf-8", "gbk");
        $res = mysqli_query($con,$sql);
        file_put_contents('delete_log.txt','sql:'.mb_convert_encoding($sql, "utf-8", "gbk").PHP_EOL,FILE_APPEND);
    }
}

mysqli_close($con);

deldir(__DIR__.'/upload/zip');
deldir(__DIR__.'/upload/temp');
deldir('c:/windows/temp');
echo json_encode(['succ'=>1]);

//删除目录下的文件：
function deldir($dir) {
    //先删除目录下的文件：
    $dh=opendir($dir);
    while ($file=readdir($dh)) {
        if($file!="." && $file!="..") {
            $fullpath=$dir."/".$file;
            if(!is_dir($fullpath)) {
                $create_time = filemtime($fullpath);
                if($create_time < (time()-7200)){
                    unlink($fullpath);
                }
            } else {
                deldir($fullpath);
            }
        }
    }

    closedir($dh);
    //删除当前文件夹：
    if(rmdir($dir)) {
        return true;
    } else {
        return false;
    }
}

