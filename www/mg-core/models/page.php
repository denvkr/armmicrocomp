<?php

/**
 * Модель: Page
 *
 * Класс Models_Page реализует логику взаимодействия со статическими страницами сайта в базе данных.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 */
class Models_Page {

  /**
   * Добавляет страницу в базу данных.
   * @param array $array массив с данными о странице.
   * @return bool|int в случае успеха возвращает id добавленного товара.
   */
  public function addPage($array) {

    unset($array['id']);
    $result = array();

    $array['url'] = empty($array['url']) ? MG::translitIt($array['title']) : $array['url'];

    if (strlen($array['url']) > 60) {
      $array['url'] = substr($array['url'], 0, 60);
    }

    // Исключает дублирование.
    $dublicatUrl = false;
    $tempArray = $this->getPageByUrl($array['url']);
    if (!empty($tempArray)) {
      $dublicatUrl = true;
    }

    if (DB::buildQuery('INSERT INTO `'.PREFIX.'page` SET ', $array)) {
      $id = DB::insertId();
      
      // Если url дублируется, то дописываем к нему id продукта.
      if ($dublicatUrl) {
        $this->updatePage(array('id' => $id, 'url' => $array['url'].'_'.$id));
      }

      $array['id'] = $id;
      $result = $array;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Изменяет данные о странице.
   * @param array $array массив с данными о странице.
   * @return bool
   */
  public function updatePage($array) {
    $id = $array['id'];
    $result = false;
    if (!empty($id)) {
      if (DB::query('
        UPDATE `'.PREFIX.'page`
        SET '.DB::buildPartQuery($array).'
        WHERE id = %d
      ', $id)) {
        $result = true;
      }
    } else {
      $result = $this->addPage($array);
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Удаляет страницу из базы данных.
   * @param int $id  id удаляемой страницы.
   * @return bool
   */
  public function deletePage($id) {
    $result = false;

    if (DB::query('
      DELETE
      FROM `'.PREFIX.'page`
      WHERE id = %d
    ', $id)) {
      $result = true;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает информацию о запрашиваемой странице.
   * @param int $id id запрашиваемой страницы.
   * @return array массив с данными о страницы.
   */
  public function getPage($id) {
    $result = array();
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'page`
      WHERE id = %d
    ', $id);

    if (!empty($res)) {
      if ($page = DB::fetchAssoc($res)) {
        $result = $page;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает страницы, которые должны быть выведены в меню.
   * @return array массив страниц.
   */
  public function getPageInMenu() {
    $result = array();
    $res = DB::query('
      SELECT id, title, url, sort
      FROM `'.PREFIX.'page`
      WHERE print_in_menu = 1
      ORDER BY `sort` ASC
    ');

    if (!empty($res)) {
      while ($page = DB::fetchAssoc($res)) {
        $result[] = $page;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает общее количество страниц сайта.
   *
   * @param int $id id запрашиваемого товара.
   * @return array массив с данными о товаре.
   */
  public function getPageCount() {
    $result = 0;
    $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'page`
    ');

    if ($product = DB::fetchAssoc($res)) {
      $result = $product['count'];
    }

    return $result;
  }

  /**
   * Получает страницу  по его URL.
   *
   * @param string $url запрашиваемой страницы.
   * @return array массив с данными о запрашиваемой страницы.
   */
  public function getPageByUrl($url) {
    $result = array();
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'page`
      WHERE url = "%s" OR url = "%s.html"
    ', $url, $url);

    if (!empty($res)) {
      if ($page = DB::fetchAssoc($res)) {
        $result = $page;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Меняем местами параметры сортировки двух страниц.
   * @param $oneId - ID одной  страницы.
   * @param $twoId - ID другой  страницы.
   * @return bool
   */
  public function changeSortPage($oneId, $twoId) {
    $page1 = $this->getPage($oneId);
    $page2 = $this->getPage($twoId);
    if (!empty($page1) && !empty($page2)) {

      $res = DB::query('
       UPDATE `'.PREFIX.'page` 
       SET  `sort` = '.DB::quote($page1['sort']).'  
       WHERE  `id` ='.DB::quote($page2['id']).'
     ');

      $res = DB::query('
       UPDATE `'.PREFIX.'page` 
       SET  `sort` = '.DB::quote($page2['sort']).'  
       WHERE  `id` ='.DB::quote($page1['id']).'
     ');
      return true;
    }
    return false;
  }

}