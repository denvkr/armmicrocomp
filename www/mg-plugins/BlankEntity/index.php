<?php

/*
  Plugin Name: blank-entity
  Description: Плагин является заготовкой для разработчиков плагинов определяется шорткодом [blank-entity], имеет страницу настроек, создает в БД таблицу для дальнейшей работы, использует собственный файл локали, свой  CSS и JS скрипы.
  Author: Avdeev Mark
  Version: 1.0
 */

new BlankEntity;

class BlankEntity {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddShortcode('blank-entity', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [blank-entity] - доступен в любом HTML коде движка.    

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;

    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
    }
  }

  
  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    self::createDateBase();
  }

  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />     
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }

  
  /**
   * Создает таблицу плагина в БД
   */
  static function createDateBase() {
    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',
      `type` varchar(255) NOT NULL COMMENT 'Тип записи',
	    `nameEntity` text NOT NULL COMMENT 'Название',
      `value` text NOT NULL COMMENT 'Значение',      
      `sort` int(11) NOT NULL COMMENT 'Порядок',
      `invisible` int(1) NOT NULL COMMENT 'Видимость',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

    // Запрос для проверки, был ли плагин установлен ранее.
    $res = DB::query("
      SELECT id
      FROM `".PREFIX.self::$pluginName."`
      WHERE id in (1,2,3) 
    ");

    // Если плагин впервые активирован, то задаются настройки по умолчанию 
    if (!DB::numRows($res)) {
     
      DB::query("
        INSERT INTO `".PREFIX.self::$pluginName."` (`id`, `type`, `nameEntity`, `value`, `sort`, `invisible`) VALUES
          (1, 'img', 'name1', 'src1', 1,1),
          (2, 'img', 'name2', 'src2', 2,1),
          (3, 'html', 'name3', 'src3', 3,1)
      ");
      
      $array = Array(
        'width' => '980',
        'height' => '300',
        'countRows' => '10',      
      );
      
      MG::setOption(array('option' => 'blank-entityOption', 'value' => addslashes(serialize($array))));
      
    }
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    
    //получаем опцию blank-entityOption в переменную option
    $option = MG::getSetting('blank-entityOption');
    $option = stripslashes($option);
    $options = unserialize($option);   
    
    $res = self::getEntity($options['countRows']);    
    $entity = $res['entity'];
    $pagination = $res['pagination'];  
    
    self::preparePageSettings(); 
    include('pageplugin.php');
  }

  
  /**
   * Получает из БД записи
   */
  static function getEntity($count=1) {
    $result = array();
    $sql ="SELECT * FROM `".PREFIX.self::$pluginName."` ORDER BY sort ASC";
    if ($_POST["page"]){
      $page = $_POST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    }
    $navigator = new Navigator($sql, $page, $count); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');
    $result = array(
      'entity' => $entity,
      'pagination' => $pagination
    );
    return $result;
  }

   /**
   * Получает количество активных записей
   */
  static function getEntityActive() {
    $result = array();
    $sql ="SELECT count(id) as count FROM `".PREFIX.self::$pluginName."` WHERE invisible = 1 ORDER BY sort ASC";
    $res = DB::query($sql);
    if($count = DB::fetchAssoc($res)){
      return $count['count'];
    }
    return 0;
  }
  
  /**
   * Обработчик шотркода вида [blank-entity] 
   * выполняется когда при генерации страницы встречается [blank-entity] 
   */
  static function handleShortCode() {

    if (!URL::isSection('mg-admin')) {
      $option = MG::getSetting('blank-entityOption');
    } else {
      $option = MG::getOption('blank-entityOption');
    }

    // преобразование строки опций в массив
    $option = stripslashes($option);
    $options = unserialize($option);

    $options["width"] = $options["width"] ? $options["width"].'px' : '100%';
    $options["height"] = $options["height"] ? $options["height"].'px' : '100%';

    if ($options["position"]=='right') {
      $options["position"] = "float:right;";
    };

    if ($options["position"]=='left') {
      $options["position"] = "float:left;";
    };

    if ($options["position"]=='center') {
      $options["position"] = "margin: 0 auto;";
    };

  
    $res = self::getEntity(2);    
    $entity = $res['entity'];
    $pagination = $res['pagination'];   

    $html = '<div style="width:'.$options["width"].'; height:'.$options["height"].'; '.$options["position"].'">';  
    $html .= print_r($entity, true);
    $html .= "Плагин blank-entity успешно обработал шорткод.";
		$html .="</div>
      <div class='clear fix-slider-block'></div>
    ";   
 
    return $html;
  }

}