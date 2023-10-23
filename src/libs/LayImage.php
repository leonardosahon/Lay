<?php
declare(strict_types=1);
namespace Lay\libs;

use Lay\core\Exception;
use Lay\core\LayConfig;
use Lay\core\sockets\IsSingleton;

final class LayImage{
    use IsSingleton;

    /**
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param int $width resample image width
     * @param int $height resample image height
     * @param int $w_orig original image width
     * @param int $h_orig original image height
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return LayImage
     */
    private function create(string $tmpImage, string $newImage, int $width, int $height, int $w_orig, int $h_orig, int $quality) : self {

        $ext = image_type_to_extension(exif_imagetype($tmpImage),false);
        $img = call_user_func("imagecreatefrom$ext", $tmpImage);
        $tci = imagecreatetruecolor($width, $height);
        
        imagefill($tci, 0, 0, imagecolorallocate($tci, 255, 255, 255));
        imagealphablending($tci, TRUE);
        imagecopyresampled($tci, $img, 0, 0, 0, 0, $width, $height, $w_orig, $h_orig);
        imagewebp($tci,$newImage,$quality);
        imagedestroy($tci);
        return $this;
    }
    
    /**
     * Check image width and height size
     * @param $imageFile string file to be checked for size
     * @return array [width,height]
     */
    public function get_size(string $imageFile) : array {
        list($w_orig,$h_orig) = getimagesize($imageFile);
        
        if(!$w_orig || !$h_orig)
            $this->exception("An invalid image file was sent for upload");

        return [$w_orig,$h_orig,"width" => $w_orig,"height" => $h_orig];
    }
    
    /**
     * Resize Image
     * @param int $width resample image width
     * @param int $height resample image height
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return LayImage
     */
    public function resize(int $width, int $height, string $tmpImage, string $newImage, int $quality = 80) : self {
        $quality = min($quality, 100);
        $quality = max($quality, 0);

        $x = $this->get_size($tmpImage);
        $w_orig = $x['width']; 
        $h_orig = $x['height'];

        $scale_ratio = $w_orig/$h_orig;

        if(($width/$height) > $scale_ratio)
            $width = (int) ceil($height * $scale_ratio);
        else
            $height = (int) ceil($width / $scale_ratio);

        return $this->create($tmpImage, $newImage, $width, $height, $w_orig, $h_orig, $quality);
    }
    
    /**
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return LayImage
     */
    public function convert(string $tmpImage,string $newImage, int $quality = 84) : self {
        $x = $this->get_size($tmpImage);
        $w_orig = $x['width']; $h_orig = $x['height'];
        return $this->create($tmpImage, $newImage, $w_orig, $h_orig, $w_orig, $h_orig, $quality);
    }
    
    /**
     * Watermark Image (Watermark is always centered)
     * @param string $watermark_img location to watermark image file
     * @param string $tmpImage location to temporary file or file to be handled
     * @param string $newImage location to new image file
     * @param int $quality image result quality [max value = 100 && min value = 0]
     * @return LayImage
     */
    public function watermark(string $watermark_img, string $tmpImage,string $newImage, int $quality=100) : self {
        $ext = image_type_to_extension(exif_imagetype($watermark_img),false);
        $watermark = call_user_func("imagecreatefrom$ext", $watermark_img);
        
        imagealphablending($watermark, false);
        imagesavealpha($watermark, true);
        
        $ext = image_type_to_extension(exif_imagetype($tmpImage),false);
        $img = call_user_func("imagecreatefrom$ext", $tmpImage);
        
        $img_w = imagesx($img);
        $img_y = imagesy($img);
        
        $wmark_w = (int)ceil(imagesx($watermark));
        $wmark_h = (int)ceil(imagesy($watermark));
        
        $dst_x = ($img_w/2) - ($wmark_w/2); // for centering watermark on image
        $dst_y = ($img_y/2) - ($wmark_h/2); // for centering watermark on image
        
        $dst_x = (int)ceil($dst_x); // for centering watermark on image
        $dst_y = (int)ceil($dst_y); // for centering watermark on image
        
        imagecopy($img, $watermark, $dst_x, $dst_y, 0, 0, $wmark_w, $wmark_h);
        imagewebp($img,$newImage,$quality);
        imagedestroy($img);
        imagedestroy($watermark);
        return $this;
    }
    
    /**
     * ### @$options
     * - **post_name (string):** $_FILES[post_name] *(REQUIRED)*
     * - **new_name (string):** The name you wish to call this newly uploaded file (REQUIRED)*
     * - **directory (string):** The directory where the file should be uploaded to (REQUIRED)*
     * - **permission (int):** The permission to apply to the directory and file *(default: 0755)*
     * - **dimension (array[int,int]):** [Max Width, Max Height] *(default: [800,800])*
     * - **copy_tmp_file (bool):** On true, function copies the upload temp file instead of moving it in case the developer wants to further process it *(default: false)*
     *
     * This function moves your uploaded image, creates the directory,
     * resizes the image and returns the image name and extension (image.webp)
     * @param array $options
     * @return string|bool filename and extension on success or false on fail
     */
    public function move(array $options): bool|string {
        extract($options);
        $copy_tmp_file = $copy_tmp_file ?? false;
        $permission = $permission ?? 0755;
        $dimension = $dimension ?? null;

        if(!isset($_FILES[$post_name]))
            return false;

        $directory = rtrim($directory,DIRECTORY_SEPARATOR);

        $operation = function ($imgName, $tmp_name) use ($directory, $post_name, $new_name, $dimension, $copy_tmp_file){
            $lay = LayConfig::instance();

            $tmpFolder = $lay::mk_tmp_dir();
            $file_name = $lay::get_orm()->clean($new_name,6) . ".webp";

            $tmpImg = $tmpFolder . DIRECTORY_SEPARATOR . "temp.tmp";
            $directory = $directory . DIRECTORY_SEPARATOR . $file_name;

            if (!extension_loaded("gd"))
                $this->exception("GD Library not installed, please install php-gd extension and try again");

            if($copy_tmp_file && !copy($tmp_name,$tmpImg))
                $this->exception("Failed to copy temporary image <b>FROM</b; $tmp_name <b>TO</b> $tmpImg <b>USING</b> (\$_FILES['$post_name']), ensure location exists, or you have permission");

            if(!$copy_tmp_file && @!move_uploaded_file($tmp_name, $tmpImg))
                $this->exception("Could not create temporary image from; (\$_FILES['$post_name']) in location: ($tmpFolder), ensure location exists or check permission");

            $this->convert($tmpImg, $directory);

            if($dimension)
                $this->resize($dimension[0], $dimension[1], $tmpImg, $directory);

            unlink($tmpImg);
            return $file_name;
        };

        if(!is_dir($directory)) {
            umask(0);
            if(!@mkdir($directory, $permission, true))
                $this->exception("Failed to create directory on location: ($directory); access denied; modify permissions and try again");
        }

        $files = $_FILES[$post_name];

        if(empty($files['tmp_name']))
            return false;

        return $operation($files["name"], $files["tmp_name"]);
    }

    private function exception(string $message) : void {
        Exception::throw_exception($message, "IMG-SERVICE");
    }
}