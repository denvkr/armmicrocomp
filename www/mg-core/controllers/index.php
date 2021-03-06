<?php

/**
 * Контроллер: Catalog
 *
 * Класс Controllers_Catalog обрабатывает действия пользователей в каталоге интернет магазина.
 * - Формирует список товаров для конкретной страницы;
 * - Добавляет товар в корзину.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Index extends BaseController {

  function __construct() {
    $settings = MG::get('settings');
    // Если нажата кнопка купить.
    $_REQUEST['category_id'] = URL::getQueryParametr('category_id');

    if (!empty($_REQUEST['inCartProductId'])) {
      $cart = new Models_Cart;
      $property = $cart->createProperty($_POST);
      $cart->addToCart($_REQUEST['inCartProductId'], $_REQUEST['amount_input'], $property);
      SmalCart::setCartData();
      MG::redirect('/cart');
    }

    $countСatalogProduct = $settings['countСatalogProduct'];
    // Показать первую страницу выбранного раздела.
    $page = 1;

    // Запрашиваемая страница.
    if (isset($_REQUEST['p'])) {
      $page = $_REQUEST['p'];
    }
    
    $model = new Models_Catalog;

    // Получаем список вложенных категорий, для вывода всех продуктов, на страницах текущей категории.
    $model->categoryId = MG::get('category')->getCategoryList($_REQUEST['category_id']);

    // В конец списка, добавляем корневую текущую категорию.
    $model->categoryId[] = $_REQUEST['category_id'];

    // Передаем номер требуемой страницы, и количество выводимых объектов.
    $countСatalogProduct = 100;
 
    // Формируем список товаров со старой ценой.
    $saleProducts = $model->getListByUserFilter(MG::getSetting('countSaleProduct'), ' p.old_price>0 and p.activity=1 ORDER BY sort ASC');

    foreach ($saleProducts['catalogItems'] as &$item) {
      $item["recommend"] = 0;
      $item["new"] = 0;  
      $imagesUrl = explode("|", $item['image_url']);
      $item["image_url"] = "";
      if (!empty($imagesUrl[0])) {
        $item["image_url"] = $imagesUrl[0];
      }
    }

    $page = new Models_Page;
    $html = $page->getPageByUrl('index');
    $html['html_content'] = MG::inlineEditor(PREFIX.'page', "html_content", $html['id'], $html['html_content']);


    $this->data = array(
      'recommendProducts' => !empty($recommendProducts['catalogItems']) ? $recommendProducts['catalogItems'] : array(),
      'newProducts' => !empty($newProducts['catalogItems']) ? $newProducts['catalogItems'] : array(),
      'saleProducts' => !empty($saleProducts['catalogItems']) ? $saleProducts['catalogItems'] : array(),
      'titeCategory' => $html['meta_title'],
      'cat_desc' => $html['html_content'],
      'meta_title' => $html['meta_title'],
      'meta_keywords' => $html['meta_keywords'],
      'meta_desc' => $html['meta_desc'],
      'currency' => $settings['currency'],
      'actionButton' => MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView'
    );
  }

}