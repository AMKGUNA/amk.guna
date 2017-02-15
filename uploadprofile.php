<?php

include "php/class/query.php";
include "php/session/session_start.php";
$IMAGICK_LOADED = false;
if (extension_loaded("imagick")) {
    $IMAGICK_LOADED = true;
}
if (!isset($_SESSION['userid'])) {
    header("Location: /login");
    exit;
}
$userid = $_SESSION['userid'];
if (!isset($_FILES["file"]["name"])) {
    header("Location: /");
    exit;
}

function LoadJpeg($imgname) {
    $imgArray = explode(".", $imgname);
    $type = end($imgArray);

    if ($type == "png") {
        $im = @imagecreatefrompng($imgname);
    } else if ($type == "jpg" || $type == "jpeg") {
        $im = @imagecreatefromjpeg($imgname);
    } else if ($type == "gif") {
        $im = @imagecreatefromgif($imgname);
    }
    if (!$im) {
        $im = @imagecreatefrompng("http://initedit.com/img/nfi.png");
    }
    return $im;
}

$allowedExts = array("gif", "jpeg", "jpg", "png");
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);
$uploadpicname = "picture." . $temp[count($temp) - 1];
if ((($_FILES["file"]["type"] == "image/gif") || ($_FILES["file"]["type"] == "image/jpeg") || ($_FILES["file"]["type"] == "image/jpg") || ($_FILES["file"]["type"] == "image/pjpeg") || ($_FILES["file"]["type"] == "image/x-png") || ($_FILES["file"]["type"] == "image/png")) && ($_FILES["file"]["size"] < 2000000) && in_array($extension, $allowedExts)) {
    if ($_FILES["file"]["error"] > 0) {
        echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
    } else {
        echo "Upload: " . $_FILES["file"]["name"] . "<br>";
        echo "Type: " . $_FILES["file"]["type"] . "<br>";
        echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
        echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";
        $quality = 100;
        if (($_FILES["file"]["size"] / 1024) > 1000) {
            $quality = 50;
        }
        $target_dir = "uploads/profile/original/";
        $rand_number = md5(time() . rand() * 1000000);
        $target_file = $target_dir . $rand_number . "." . $extension;
        move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
        echo "Stored in: " . $target_file;
        $imgname = "./" . $target_file;
        $img = LoadJpeg($imgname);
        $oldImageName = QUERY::c("select img from usersignup where userid=$userid");
        if ($oldImageName != "default.png" || $oldImageName != "default.jpg") {
            unlink("./uploads/profile/original/" . $oldImageName);
            unlink("./uploads/profile/thumb/" . $oldImageName);
        }
        list($width, $height, $type, $attr) = getimagesize("./uploads/profile/original/" . $rand_number . "." . $extension);
        $newWidth = $width;
        $newHeight = $height;
        $fact = 0.9;
        while ($newHeight > 300 || $newWidth > 300) {
            $newWidth = $newWidth * $fact;
            $newHeight = $newHeight * $fact;
        }
        if($IMAGICK_LOADED) {
            $imagicObject = new Imagick("./uploads/profile/original/" . $rand_number . "." . $extension);
            $imagicObject->thumbnailimage($newWidth, $newHeight, true);
            $imagicObject->setImageCompressionQuality(0);
            $imagicObject->writeImage("./uploads/profile/thumb/" . $rand_number . "." . $extension);
            $imagicObject->clear();
        }else{
            copy("./uploads/profile/original/" . $rand_number . "." . $extension,"./uploads/profile/thumb/" . $rand_number . "." . $extension)
        }

        $imageName = $rand_number . "." . $extension;

        QUERY::query("update usersignup set img='{$imageName}' where userid=$userid");
    }
} else {
    echo "Invalid file";
}
header("Location: /");
?>