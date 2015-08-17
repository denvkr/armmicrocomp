<?php

/**
 * Класс Category - совершает все возможные операции с категориями товаров.
 * - Создает новую категорию;
 * - Удаляет категорию; 
 * - Редактирует  категорию;
 * - Возвращает список id всех вложенных категорий;
 * - Возвращает древовидный список категорий, пригодный для использования в меню;
 * - Возвращает массив id категории и ее заголовок;
 * - Возвращает иерархический массив категорий;
 * - Возвращает отдельные пункты списка заголовков категорий.
 * - Генерирует UL список категорий для вывода в меню.
 * - Экземпляр класса категорий хранится в реестре класс MG
 * <code>
 * //пример вызова метода getCategoryListUl() из любого места в коде.
 * MG::get('category')->getCategoryListUl()
 * </code>
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Category {

  // Массив категрорий.
  private $categories;
  private $listCategoryId;

  public function __construct() {
    // получаем список категорий    

    $result = DB::query('SELECT id, title, url, parent, parent_url, sort, meta_title, meta_keywords, meta_desc,invisible FROM `'.PREFIX.'category` ORDER BY id');
    $listId = "";
    while ($cat = DB::fetchAssoc($result)) {
      $listId .= ','.$cat['id'];
      $this->categories[$cat['id']] = $cat;     
      $this->categories[$cat['id']]['userProperty'] = array();
    }
    if($listId){
      $listId = "in (".ltrim($listId,',').")";    
    }
   
    // для каждой категории получаем массив пользовательских характеристик
    $res = DB::query("
        SELECT p.*, c.category_id
        FROM `".PREFIX."category_user_property` AS c, `".PREFIX."property` AS p
        WHERE c.category_id ".$listId."
        AND (p.id = c.property_id OR p.all_category = 1)"
    );

    while ($prop = DB::fetchAssoc($res)) {
      $this->categories[$prop['category_id']]['userProperty'][$prop['id']] = $prop;
    }

    if(!empty($this->categories)){
      usort($this->categories, array(__CLASS__, "sort"));      
    }
  }
  
 /**
   * Возвращает url родительской категорию по ее id.
   * @param $parentId - id категории для которой нужно найти UR родителя.
   * @return string
   */
  public function getParentUrl($parentId) {
    $cat = $this->getCategoryById($parentId);
    $res = !empty($cat) ? $cat['parent_url'].$cat['url'] : '';
    return $res ? $res.'/' : '';
  }

  /**
   *  Создает новую категорию.
   *
   * @param array $array массив с данными о категории.
   * @return bool|int в случае успеха возвращает id добавленной категории.
   */
  public function addCategory($array) {
    
    unset($array['id']);
    $result = array();
    if(!empty($array['url'])){
      $array['url'] = URL::prepareUrl($array['url']); 
    }
    // Исключает дублирование.
    $dublicatUrl = false;
    
    $tempArray = $this->getCategoryByUrl($array['url'],$array['parent_url']);
    if (!empty($tempArray)) {
      $dublicatUrl = true;
    }
    $array['sort'] = $array['id'];
    if (DB::buildQuery('INSERT INTO `'.PREFIX.'category` SET ', $array)) {
      $id = DB::insertId();    
      // Если url дублируется, то дописываем к нему id продукта.
      if ($dublicatUrl) {
        $arr = array('id' => $id,'sort'=>$id, 'url' => $array['url'].'_'.$id);
      } else{
        $arr = array('id' => $id,'sort'=>$id, 'url' => $array['url']);    
      }
      $this->updateCategory($arr);     
      $array['id'] = $id;
      $result = $array;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Изменяет данные о категории.
   *
   * @param array $array массив с данными о категории.
   * @param int $id  id изменяемой категории.
   * @return bool
   */
  public function updateCategory($array) {
 
    
    $id = $array['id'];
    $result = false;
    if(!empty($array['url'])){
     $array['url'] = URL::prepareUrl($array['url']);     
    }
    
    // Если назначаемая категория, является тойже.
    if ($array['parent']===$id) {
      $this->messageError = 'Нельзя назначить выбраную категорию родительской!';
      return false;
    }

    $childsCaterory = $this->getCategoryList($id);
    // Если есть вложенные, и одна из них назначена родительской.
    if (!empty($childsCaterory)) {
      foreach ($childsCaterory as $cateroryId) {
        if ($array['parent']===$cateroryId) {
          $this->messageError = 'Нельзя назначить выбраную категорию родительской!';
          return false;
        }
      }
    }

    if ($_POST['parent']===$id && !isset($array['parent'])) {
      $this->messageError = 'Нельзя назначить выбраную категорию родительской!';
      return false;
    }

  
    if (!empty($id)) {
      // обновляем выбраную категорию
      if (DB::query('
        UPDATE `'.PREFIX.'category`
        SET '.DB::buildPartQuery($array).'
        WHERE id = %d
      ', $id)) {
        $result = true;
      }

      // находим список всех вложенных в нее категорий
      $arrayChildCat = $this->getCategoryList($array['parent']);
      if (!empty($arrayChildCat)) {
        // обновляем parent_url у всех вложенных категорий, т.к. корень поменялся
        foreach ($arrayChildCat as $childCat) {
          $childCat = $this->getCategoryById($childCat);
          $upParentUrl = $this->getParentUrl($childCat['parent']);
          if (DB::query('
            UPDATE `'.PREFIX.'category`
            SET parent_url='.DB::quote($upParentUrl).'
            WHERE id = %d
          ', $childCat['id']));
        }
      }
    } else {
   
      $result = $this->addCategory($array);
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Удаляет категорию.
   *
   * @param int $id id удаляемой категории.
   * @return bool
   */
  public function delCategory($id) {
    $categories = $this->getCategoryList($id);
    $categories[] = $id;

    foreach ($categories as $categoryID) {
      DB::query('
        DELETE FROM `'.PREFIX.'category`
        WHERE id = %d
      ', $categoryID);
    }

    $args = func_get_args();
    $result = true;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает древовидный список категорий, пригодный для использования в меню.
   *
   * @param int $parent id категории, для которой надо вернуть список.
   * @param int $type тип списка (для публичной части, либо для админки).
   * @return string
   */
  public function getCategoryListUl($parent = 0, $type = 'public') {
    // получаем данные об открытых категориях из куков  
    if(empty($this->openedCategory)){
      if ('admin'==$type){
        $this->openedCategory= json_decode($_COOKIE['openedCategoryAdmin']);    
      } else {
        $this->openedCategory= json_decode($_COOKIE['openedCategory']);  
      }
      if(empty($this->openedCategory)){
        $this->openedCategory=array();
      }    
    }

    $print = '';
    if (empty($this->categories)) {
      $print = '';
    } else {
      $lang = MG::get('lang');
      $gategoryArr = $this->categories;
      
      //для публичной части убираем из меню закрытые категории
      if('public'==$type){
        foreach ($gategoryArr as $key=>$val) {
           if($val['invisible']==1){
             unset($gategoryArr[$key]);
           } 
        }
      }
      
      foreach ($gategoryArr as $category) {
        if(!isset($category['id'])){break;}//если категории неceotcndetn
        if ($parent==$category['parent']) {

          $flag = false;
          
          $mover = '';

          if ('admin'==$type) {
             $class='active';  
             $title = $lang['ACT_V_CAT'];
             if ($category['invisible']==1) {
                $class = '';          
                $title = $lang['ACT_UNV_CAT'];
             } 
                
             $mover .= '<div class="visible tool-tip-bottom '.$class.'" title="'.$title.'" data-category-id="'.$category['id'].'"></div><div class="mover"></div><div class="link-to-site tool-tip-bottom" title="'.$lang['MOVED_TO_CAT'].'"  data-href="'.SITE.'/'.$category['parent_url'].$category['url'].'"></div>';
       
          }

          $slider = '>'.$mover.$link;

          foreach ($this->categories as $sub_category) {             
            if ($category['id']==$sub_category['parent']) {
              $slider = ' class="slider">'.$mover.'<div class="slider_btn"></div>';
              $style = "";
              $opened = "";

              if(in_array( $category['id'],$this->openedCategory)){
                $opened = " opened ";
                $style=' style="background-position: 0 0"';
              }
              
              $slider = ' class="slider">'.$mover.'<div class="slider_btn '.$opened.'" '.$style.'></div>';
              $flag = true;
              break;
            }
          }

          if ('admin'==$type) {
            $print.= '<li'.$slider.'<a href="javascript:void(0);" onclick="return false;" class="CategoryTree" rel="CategoryTree" id="'.$category['id'].'" parent_id="'.$category["parent"].'">'.$category['title'].'</a>
			<span style="display:none"> [id='.$category['id'].'] </span>';
          } else {
            if ($category['invisible']!=1) {             
              $active = '';     
              if(URL::isSection($category['parent_url'].$category['url'])){
                $active = 'class="active"';              
              }
              $category['title'] = MG::contextEditor('category', $category['title'], $category["id"],"category");              
              $print.= '<li'.$slider.'<a href="'.SITE.'/'.$category['parent_url'].$category['url'].'"><span '.$active.'>'.$category['title'].'</span></a>';
            }
          }

          if ($flag) {
            $display = "display:none";
            if(in_array( $category['id'],$this->openedCategory)){
              $display = "display:block";
            }
            
            $sub_menu = '
              <ul class="sub_menu" style="'.$display.'">
                [li]
              </ul>';    
            
            // если категория скрыта то не идем в глубь
                
            $li = $this->getCategoryListUl($category['id'], $type);         
            $print .= strlen($li)>0 ? str_replace('[li]', $li, $sub_menu) : "";
       
           $print .= '</li>'; 
        
          } else {            
            $print .= '</li>';
          }
        }
      }
    }

    $args = func_get_args();
    $result = $print;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив вложенных категорий первого уровня.
   *
   * @param int $parent  id родительской категории.
   * @return string.
   */
  public function getChildCategoryIds($parentId = 0) {
    $result = array();

    $res = DB::query('
      SELECT id
      FROM `'.PREFIX.'category`
      WHERE parent = %d
      ORDER BY id
    ', $parentId);

    while ($row = DB::fetchArray($res)) {
      $result[] = $row['id'];
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список только id всех вложеных категорий.
   *
   * @param int $parent id родительской категории
   * @return array
   */
  public function getCategoryList($parent = 0) {
  
    if (!empty($this->categories))
      foreach ($this->categories as $category) {
      
        if(!isset($category['id'])){break;}//если категории неceotcndetn
        
        if ($parent==$category['parent']) {
          $this->listCategoryId[] = $category['id'];          
          $this->getCategoryList($category['id']);
        }
      }
    $args = func_get_args();
    if (!empty($this->listCategoryId)) {
      $this->listCategoryId = array_flip(array_flip($this->listCategoryId)); //удаление дублей
    }
    $result = $this->listCategoryId;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив id категории и ее заголовок.
   *
   * @return array
   */
  public function getCategoryTitleList() {
    $titleList[0] = 'Корень каталога';
    if (!empty($this->categories))
      foreach ($this->categories as $category) {
        $titleList[$category['id']] = $category['title'];
      }

    $args = func_get_args();
    $result = $titleList;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
  /**
   * Возвращает иерархический массив категорий.
   *
   * @param int $parent id родительской категории.
   * @return array
   */
  public function getHierarchyCategory($parent = 0) {
    $catArray = array();
    if (!empty($this->categories))
      foreach ($this->categories as $category) {
        if(!isset($category['id'])){break;}//если категории неceotcndetn
        if ($parent==$category['parent']) {
          $child = $this->getHierarchyCategory($category['id']);
         
          if (!empty($child)) {
            $array = $category;          
            usort($child, array(__CLASS__, "sort"));        
            $array['child'] = $child;
          } else {
            $array = $category;
          }
          
          $catArray[] = $array;
        }
      }
    $args = func_get_args();
    $result = $catArray;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
  /**
   * Возвращает отдельные пункты списка заголовков категорий.
   *
   * @param array $arrayCategories массив с категориями.
   * @param array $modeArray - если установлен этот флаг, то  результат вернет массив а не HTML список
   * 
   * @return string
   */
  public function getTitleCategory($arrayCategories, $selectCaegory = 0, $modeArray=false) {
    if($modeArray){
      global $catArr;
    }
    global $lvl;
    $option = '';
    foreach ($arrayCategories as $category) {
      $select = '';
      if ($selectCaegory==$category['id']) {
        $select = 'selected = "selected"';
      }
      $option .= '<option value='.$category['id'].' '.$select.' >';
      $option .= str_repeat('-', $lvl);
      $option .= $category['title'];
      $option .= '</option>';
      $catArr[$category['id']]=str_repeat('-', $lvl).$category['title'];
      if (isset($category['child'])) {
        $lvl++;       
        $option .= $this->getTitleCategory($category['child'],$selectCaegory,$modeArray);
        $lvl--;
      }
    }
    $args = func_get_args();
    
    $result = $option;  
    if($modeArray){
      $result = $catArr;      
    }
  
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает параметры категори по его URL.
   *
   * @param string $url запрашиваемой  категории.
   * @param string $parentUrl родительской категории.
   * @return array массив с данными о категории.
   *
   */
  public function getCategoryByUrl($url, $parentUrl="") {
    $result = array();

    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'category`
      WHERE url = "%s" AND parent_url = "%s"
    ', $url, $parentUrl);

    if (!empty($res)) {
      if ($cat = DB::fetchAssoc($res)) {
        $result = $cat;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает параметры категори по его Id.
   *
   * @param string $id запрашиваемой  категории.
   * @return array массив с данными о категории.
   *
   */
  public function getCategoryById($id) {
    $result = array();
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'category`
      WHERE id = "%s"
    ', $id);

    if (!empty($res)) {
      if ($cat = DB::fetchArray($res)) {
        $result = $cat;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив пользовательских характеристик для заданой категории.
   *
   * @param string $id запрашиваемой  категории.
   * @return array массив пользовательских характеристик для данной категории.
   *
   */
  public function getUserPropertyCategoryById($id) {
    return $this->categories[$id]['userProperty'];
  }

  /**
   * Получает описание категории.
   * @param type $id - номер категории
   * @return type
   */
  public function getDesctiption($id) {
    $result = null;
    $res = DB::query('
      SELECT html_content
      FROM `'.PREFIX.'category`
      WHERE id = "%d"
    ', $id);

    if (!empty($res)) {
      if ($cat = DB::fetchArray($res)) {
        $result = $cat['html_content'];
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /** 
   *  Упорядочивает категорию по сортировке.
   */
  public function sort($a, $b) {
    return $a['sort'] - $b['sort'];
  }
  
  
  /** 
   * Меняет местами параметры сортировки двух категории.
   * @param int $oneId - id первой категории.
   * @param int $twoId - id второй категории.
   * @return boolean
   */
  public function changeSortCat($oneId, $twoId) {
    $cat1 = $this->getCategoryById($oneId); 
    $cat2 = $this->getCategoryById($twoId); 
    if(!empty($cat1)&&!empty($cat2)){
      
     $res = DB::query('
       UPDATE `'.PREFIX.'category` 
       SET  `sort` = '.DB::quote($cat1['sort']).'  
       WHERE  `id` ='.DB::quote($cat2['id']).'
     ');
     
     $res = DB::query('
       UPDATE `'.PREFIX.'category` 
       SET  `sort` = '.DB::quote($cat2['sort']).'  
       WHERE  `id` ='.DB::quote($cat1['id']).'
     ');  
      return true;
    }
    return false;
  }
  
  /**
   * @return boolean Делает все категории видимыми в меню.
   */
  public function  refreshVisibleCat() {
     $res = DB::query('
       UPDATE `'.PREFIX.'category` 
       SET  `invisible` = 0  
       WHERE  1 = 1
     ');
     return true;
  }
  
}