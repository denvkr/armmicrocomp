<?php

/**
 * Класс для загрузки изображений на сервер, в том числе и через ckeditor.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Upload {

  public $lang = array();

  public function __construct($ckeditMode = true) {

    include('mg-admin/locales/'.MG::getOption('languageLocale').'.php');
    $this->lang = $lang;
    if ($ckeditMode) {
      $uploaddir = 'uploads';
      $arrData = $this->addImage();
      $msg = $arrData['msg'];
      if ($arrData['status'] == "error") {
        echo '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction('.$_REQUEST['CKEditorFuncNum'].',  "'.$full_path.'","'.$arrData['msg'].'" );</script>';
      } else {
        $full_path = SITE.'/uploads/'.$arrData['actualImageName'];
        echo '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction("'.$_REQUEST['CKEditorFuncNum'].'",  "'.$full_path.'","'.$arrData['msg'].'" );</script>';
      }
    }
  }

  /**
   * Загружает картинку из формы на сервер
   * @return boolean
   */
  public function addImage($productImg = false, $watermark = false) {

    $path = 'uploads/';


    $validFormats = array('jpeg', 'jpg', 'png', 'gif', 'JPG');
    if ($watermark) {
      $path.="watermark/";
      if (!file_exists('uploads/watermark/')) {
        if (is_writable('uploads/')) {
          chdir('uploads/'); //путь где создавать папку		
          mkdir('watermark', 755); //имя папки и атрибуты на папку	
          return array('msg' => "Папка для знака была восстановлена. Теперь можно загрузить картинку.", 'status' => 'success');
        }
      }
      $validFormats = array('png');
    }

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {

      if (!empty($_FILES['upload'])) {
        $file_array = $_FILES['upload'];
      } elseif (!empty($_FILES['photoimg'])) {
        $file_array = $_FILES['photoimg'];
      } else {
        $file_array = $_FILES['edit_photoimg'];
      }

      $name = $file_array['name'];
      $size = $file_array['size'];

      if (strlen($name)) {
        //list($txt, $ext) = explode('.', $name);
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        if (in_array(strtolower($ext), $validFormats)) {
          if ($size < (1024 * 5 * 1024) && !empty($file_array['tmp_name'])) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
            $name = str_replace(" ", "-", $name);
            $name = MG::translitIt($name);
            $actualImageName = $this->prepareName($name, $ext);
            if ($watermark) {
              $actualImageName = 'watermark.png';
            }
            $tmp = $file_array['tmp_name'];

            if (move_uploaded_file($tmp, $path.$actualImageName)) {

              if (MG::getSetting("waterMark") == "true" && !$watermark) {
                if (empty($_POST['noWaterMark'])) {
                  $this->addWatterMark($path.$actualImageName);
                }
              }

              //если картинка заливаются для продукта, то делаем две миниатюры
              if ($productImg && !$watermark) {
                //подготовка миниатюр с заданными в настройках размерами
                // preview по заданным в настройках размерам
                $widthPreview = MG::getSetting('widthPreview') ? MG::getSetting('widthPreview') : 200;
                $widthSmallPreview = MG::getSetting('widthSmallPreview') ? MG::getSetting('widthSmallPreview') : 50;
                $heightPreview = MG::getSetting('heightPreview') ? MG::getSetting('heightPreview') : 100;
                $heightSmallPreview = MG::getSetting('heightSmallPreview') ? MG::getSetting('heightSmallPreview') : 50;
                $this->_reSizeImage('70_'.$actualImageName, $path.$actualImageName, $widthPreview, $heightPreview);
                // миниатюра по размерам из БД (150*100)
                $this->_reSizeImage('30_'.$actualImageName, $path.$actualImageName, $widthSmallPreview, $heightSmallPreview);
              }

              return array('msg' => $this->lang['ACT_IMG_UPLOAD'], 'actualImageName' => $actualImageName, 'status' => 'success');
            } else {
              return array('msg' => $this->lang['ACT_IMG_NOT_UPLOAD'], 'status' => 'error');
            }
          } else {
            return array('msg' => $this->lang['ACT_IMG_NOT_UPLOAD1'], 'status' => 'error');
          }
        } else {
          return array('msg' => $this->lang['ACT_IMG_NOT_UPLOAD2'], 'status' => 'error');
        }
      } else {
        return array('msg' => $this->lang['ACT_IMG_NOT_UPLOAD3'], 'status' => 'error');
      }
    }
    return false;
  }

  /**
   * Проверяет существует ли уже в папке uploads файл с таким же именем.
   * Чтобы не перезатереть его  имя текущего файла будет дополненно индексом.
   * @return boolean
   */
  public function prepareName($name, $ext) {
    if (file_exists('uploads/'.$name.".".$ext)) {
      return $name.time().".".$ext;
    }
    return $name.".".$ext;
  }

  /**
   * Функция для ресайза картинки
   * @param string $name имя файла без расширения
   * @param string $tmp исходный временный файл
   * @param int $widthSet заданная ширина изображения
   * @param int $heightSet заданная высота изображения
   * @paramint $koef коэффициент сжатия изображения
   * @return void
   */
  private function _reSizeImage($name, $tmp, $widthSet, $heightSet, $dirUpload = 'uploads/thumbs/') {
    $fullName = explode('.', $name);
    $ext = array_pop($fullName);
    $name = implode('.', $fullName);
    list($width_orig, $height_orig) = getimagesize($tmp);

    if ($widthSet < $heightSet) {
      $ratio = $widthSet / $width_orig;
      $width = $widthSet;
      $height = $height_orig * $ratio;
    } else {
      $ratio = $heightSet / $height_orig;
      $width = $width_orig * $ratio;
      $height = $heightSet;
    }

    // ресэмплирование
    $image_p = imagecreatetruecolor($width, $height);


    imageAlphaBlending($image_p, false);
    imageSaveAlpha($image_p, true);


    // вывод
    switch ($ext) {
      case 'png':
        $image = imagecreatefrompng($tmp);
        //делаем фон изображения белым, иначе в png при прозрачных рисунках фон черный
        $black = imagecolorallocate($image, 0, 0, 0);

// Сделаем фон прозрачным
        imagecolortransparent($image, $black);

        imagealphablending($image_p, false);
        $col = imagecolorallocate($image_p, 0, 0, 0);
        imagefilledrectangle($image_p, 0, 0, $width, $height, $col);
//imagealphablending( $image_p, true );



        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        imagepng($image_p, $dirUpload.$name.'.'.$ext);
        break;

      case 'gif':
        $image = imagecreatefromgif($tmp);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        imagegif($image_p, $dirUpload.$name.'.'.$ext, 100);
        break;

      default:

        $image = imagecreatefromjpeg($tmp);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        //imagefilter($image_p, IMG_FILTER_BRIGHTNESS, 15); 
        imagejpeg($image_p, $dirUpload.$name.'.'.$ext, 100);
      // создаём новое изображение
    }
    imagedestroy($image_p);
    imagedestroy($image);
  }

  /**
   * Добавляет водяной знак к картинке
   * @param type $image - путь до картинки на сервере
   * @return boolean
   */
  public function addWatterMark($image) {
    $filename = $image;
    if (!file_exists('uploads/watermark/watermark.png')) {
      return false;
    }
    $size_format = getimagesize($image);
    $format = strtolower(substr($size_format['mime'], strpos($size_format['mime'], '/') + 1));

    // создаём водяной знак
    $watermark = imagecreatefrompng('uploads/watermark/watermark.png');
    imagealphablending($watermark, false);
    imageSaveAlpha($watermark, true);
    // получаем значения высоты и ширины водяного знака
    $watermark_width = imagesx($watermark);
    $watermark_height = imagesy($watermark);

    // создаём jpg из оригинального изображения
    $image_path = $image;



    switch ($format) {
      case 'png':
        $image = imagecreatefrompng($image_path);
        $w = imagesx($image);
        $h = imagesy($image);
        $imageTrans = imagecreatetruecolor($w, $h);
        imagealphablending($imageTrans, false);
        imageSaveAlpha($imageTrans, true);


        $col = imagecolorallocate($imageTrans, 0, 0, 0);
        imagefilledrectangle($imageTrans, 0, 0, $w, $h, $col);
        imagealphablending($imageTrans, true);


        break;
      case 'gif':
        $image = imagecreatefromgif($image_path);
        break;
      default:
        $image = imagecreatefromjpeg($image_path);
    }

    //если что-то пойдёт не так
    if ($image === false) {
      return false;
    }
    $size = getimagesize($image_path);
    // помещаем водяной знак на изображение
    $dest_x = (($size[0]) / 2) - (($watermark_width) / 2);
    $dest_y = (($size[1]) / 2) - (($watermark_height) / 2);

    imagealphablending($image, true);
    imagealphablending($watermark, true);

    imageSaveAlpha($image, true);
    // создаём новое изображение
    imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);

    $imageformat = 'image'.$format;
    if ($format = 'png') {
      $imageformat($image, $filename);
    } else {
      $imageformat($image, $filename, 100);
    }

    // освобождаем память
    imagedestroy($image);
    imagedestroy($watermark);
    return true;
  }

  /**
   * Загружает CSV файл для импорта каталога
   * @return boolean
   */
  public function addImportCatalogCSV() {

    $path = 'uploads/';
    $validFormats = array('csv');

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {

      if (!empty($_FILES['upload'])) {
        $file_array = $_FILES['upload'];
      }

      $name = $file_array['name'];
      $size = $file_array['size'];

      if (strlen($name)) {
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        if (in_array(strtolower($ext), $validFormats)) {
          if ($size < (1024 * 5 * 1024) && !empty($file_array['tmp_name'])) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
            $name = 'importCatalog.csv';
            $tmp = $file_array['tmp_name'];

            if (move_uploaded_file($tmp, $path.$name)) {
              return array('msg' => $this->lang['ACT_FILE_UPLOAD'], 'actualImageName' => $name, 'status' => 'success');
            } else {
              return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD'], 'status' => 'error');
            }
          } else {
            return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD1'], 'status' => 'error');
          }
        } else {
          return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD2'], 'status' => 'error');
        }
      } else {
        return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD3'], 'status' => 'error');
      }
    }
    return false;
  }

}