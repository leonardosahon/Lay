<?php
declare(strict_types=1);
namespace Lay\libs;

use Lay\AutoLoader;
use Lay\orm\SQL;

/**
 * ImgHandler
 */
final class ImgHandler{
    /**
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param int $width resample image width
     * @param int $height resample image height
     * @param string $fileExtension file extension [.jpg]
     * @param int $w_orig original image width
     * @param int $h_orig original image height
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return ImgHandler
     */
    private function create(string $tmpImage, string $newImage, int $width, int $height, string $fileExtension, int $w_orig, int $h_orig, int $quality) : self {

        if(exif_imagetype($tmpImage) == IMAGETYPE_JPEG) {
            $img = imagecreatefromjpeg($tmpImage);
            // $newImage = str_replace([".png",".PNG",".gif",".GIF"],".jpg",$newImage);
        }
        else{
            $ext = image_type_to_extension(exif_imagetype($tmpImage),false);
            $img = call_user_func("imagecreatefrom$ext",$tmpImage);
        }

        $tci = imagecreatetruecolor($width, $height);
        imagefill($tci, 0, 0, imagecolorallocate($tci, 255, 255, 255));
        imagealphablending($tci, TRUE);
        imagecopyresampled($tci, $img, 0, 0, 0, 0, $width, $height, $w_orig, $h_orig);
        imagejpeg($tci,$newImage,$quality);
        imagedestroy($tci);
        return $this;
    }
    /**
     * Check image width and height size
     * @param $imageFile string file to be checked for size
     * @return array [width,height]
     */
    public function img_size(string $imageFile) : array {
        list($w_orig,$h_orig) = getimagesize($imageFile);
        
        if(!$w_orig || !$h_orig)
            SQL::instance()->use_exception("Upload Error","An invalid image file was sent for upload");

        return [$w_orig,$h_orig,"width" => $w_orig,"height" => $h_orig];
    }
    /**
     * Resize Image
     * @param int $width resample image width
     * @param int $height resample image height
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param string $fileExtension file extension [.jpg]
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return ImgHandler
     */
    public function resize(int $width, int $height, string $tmpImage, string $newImage, string $fileExtension = "jpg", int $quality = 80) : self {
        if($quality > 100)
            $quality = 100;
        elseif($quality < 0)
            $quality = 0;

        $x = $this->img_size($tmpImage);
        $w_orig = $x['width']; $h_orig = $x['height'];

        $scale_ratio = $w_orig/$h_orig;

        if(($width/$height) > $scale_ratio)
            $width = (int) ceil($height * $scale_ratio);
        else
            $height = (int) ceil($width / $scale_ratio);

        return $this->create($tmpImage, $newImage, $width, $height, $fileExtension, $w_orig, $h_orig, $quality);
    }
    /**
     * Convert image to jpeg file (.jpg)
     * @param string $fileExtension file extension [.jpg]
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return ImgHandler
     */
    public function convert(string $fileExtension, string $tmpImage,string $newImage, int $quality = 84) : self {
        $x = $this->img_size($tmpImage);
        $w_orig = $x['width']; $h_orig = $x['height'];
        return $this->create($tmpImage, $newImage, $w_orig, $h_orig, strtolower($fileExtension), $w_orig, $h_orig, $quality);
    }
    /**
     * Watermark Image (Watermark is always centered)
     * @param string $wmark_img location to watermark image file
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return ImgHandler
     */
    public function watermark(string $wmark_img, string $tmpImage,string $newImage, int $quality=100) : self {
        $watermark = imagecreatefrompng($wmark_img);
        imagealphablending($watermark, false);
        imagesavealpha($watermark, true);
        $img = imagecreatefromjpeg($tmpImage);
        $img_w = imagesx($img);
        $img_y = imagesy($img);
        $wmark_w = (int)ceil(imagesx($watermark));
        $wmark_h = (int)ceil(imagesy($watermark));
        $dst_x = ($img_w/2) - ($wmark_w/2); // for centering watermark on image
        $dst_y = ($img_y/2) - ($wmark_h/2); // for centering watermark on image
        $dst_x = (int)ceil($dst_x); // for centering watermark on image
        $dst_y = (int)ceil($dst_y); // for centering watermark on image
        imagecopy($img, $watermark, $dst_x, $dst_y, 0, 0, $wmark_w, $wmark_h);
        imagejpeg($img,$newImage,$quality);
        imagedestroy($img);
        imagedestroy($watermark);
        return $this;
    }
    /**
     * This function fetches you image file, manipulates it and moves it to the specified path passed
     * @param string $post_name name used to send the image file ove the $_FILES array
     * @param string $directory full path to image's new location without image new name
     * @param string $img_name image's new name without file extension
     * @param int|null $index if multiple image files, pass the current image index
     * @param int $permission default = 0755
     * @param array|null $dimension optional max width and height of the image
     * @param string $env optional development environment which determines if error file is splashed on screen or written to log file
     * @return string|bool filename and extension on success or false on fail
     */
    public function move_img(string $post_name, string $directory, string $img_name, ?int $index = null, int $permission = 0755, ?array $dimension = null, string $env = "DEV", bool $copy_tmp = false) {
        if(isset($_FILES[$post_name])){
            $directory = rtrim($directory,DIRECTORY_SEPARATOR);
            $osai = SQL::instance();
            $osai->set_env($env);
            $operation = function ($imgName,$tmp_name) use ($directory,$post_name,$img_name,$dimension,$osai, $copy_tmp){
                $x = explode(".", $imgName);
                $ext = end($x);
                $tmpFolder = AutoLoader::get_root_dir() . "Lay" . DIRECTORY_SEPARATOR ."temp" . DIRECTORY_SEPARATOR . "IMG";
                $file_name = $osai->clean($img_name,6) . "." . $ext;

                if(!is_dir($tmpFolder)) {
                    umask(0);
                    if(@!mkdir($tmpFolder, 0777, true))
                        $osai->use_exception("Img Handler Error",
                            "Failed to create temporary folder at location [$tmpFolder], not enough permission");
                }
                $tmpImg = $tmpFolder . DIRECTORY_SEPARATOR . "temp.tmp";
                $directory = $directory . DIRECTORY_SEPARATOR . $file_name;

                if (extension_loaded("gd")) {
                    if($copy_tmp && !copy($tmp_name,$tmpImg))
                        $osai->use_exception("Img Handler Error",
                            "Failed to copy temporary image <b>FROM</b; $tmp_name <b>TO</b> $tmpImg <b>USING</b> (\$_FILES['$post_name']), ensure location exists or you have permission");

                    if(!$copy_tmp && @!move_uploaded_file($tmp_name, $tmpImg))
                        $osai->use_exception("Img Handler Error",
                            "Could not create temporary image from; (\$_FILES['$post_name']) in location: ($tmpFolder), ensure location exists or check permission");

                    $this->convert($ext, $tmpImg, $directory);
                    if($dimension) $this->resize($dimension[0],$dimension[1],$tmpImg, $directory,$ext);
                    unlink($tmpImg);
                    return $file_name;
                }

                if($copy_tmp && @!copy($tmp_name, $directory))
                    $osai->use_exception("Img Handler Error",
                        "Failed to move image; (\$_FILES['$post_name']) to location: ($directory); location may not exist or permission not enough");

                if(!$copy_tmp && @!move_uploaded_file($tmp_name, $directory))
                    $osai->use_exception("Img Handler Error",
                        "Failed to move image; (\$_FILES['$post_name']) to location: ($directory); location may not exist or permission not enough");

                return $file_name;
            };

            if(!is_dir($directory)) {
                umask(0);
                if(@!mkdir($directory, $permission, true))
                    $osai->use_exception("Img Handler Error",
                        "Failed to create directory on location: ($directory); access denied; modify permissions and try again");
            }

            $files = $_FILES[$post_name];

            if($index === null) {
                if(empty($files['tmp_name'])) return false;
                return $operation($files["name"], $files["tmp_name"]);
            }

            if(empty($files['tmp_name'][$index])) return false;


            return $operation($files['name'][$index],$files['tmp_name'][$index]);
        }
        return false;
    }
}