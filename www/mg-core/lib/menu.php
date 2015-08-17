<?php

/**
 * Класс Menu - задает пункты меню сайта.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Menu {

  private function __construct() {
    
  }

  /**
   * Возвращает меню в HTML виде.
   * @return type
   */
  public static function getMenuFull() {
    $print = '<ul class="top-menu-list">';
    $print .= MG::get('pages')->getPagesUl(0);
    $print .= '</ul>';
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $print, $args);
  }

  /**
   * Возвращает меню в HTML виде.
   * @return type
   */
  public static function getMenu() {
    $menuItem = self::getArrayMenu();

    $print = '<ul class="top-menu-list">';

    foreach ($menuItem as $name => $item) {

      if ('Вход' == $item['title'] && '' != $_SESSION['User']) {
        $print .= '<li><a href='.SITE.'"/enter">'.$_SESSION['User'].'</a><a class="logOut" href="enter?out=1"><span style="font-size:10px">[ выйти ]</span></a></li>';
      } else {
        $item['title'] = MG::contextEditor('page', $item['title'], $item["id"], 'page');
        $print .= '<li><a href="'.$item['url'].'">'.$item['title'].'</a></li>';
      }
    }

    $print .= '</ul>';

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $print, $args);
  }

  /**
   * Возвращает массив пунктов меню.
   * @return type
   */
  public static function getArrayMenu() {

    $model = new Models_Page;
    $arrPages = $model->getPageInMenu();
    $menuItem = array();
    foreach ($arrPages as $item) {
      if ($item['url'] == "index" || $item['url'] == "index.html") {
        $item['url'] = '';
      }
      $menuItem[] = array('title' => $item['title'], 'id' => $item['id'], 'url' => SITE.'/'.$item['url']);
    }

    return $menuItem;
  }

}