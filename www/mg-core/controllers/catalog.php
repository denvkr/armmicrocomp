<?php

/**
 * Контроллер: Catalog
 *
 * Класс Controllers_Catalog обрабатывает действия пользователей в каталоге интернет-магазина.
 * - Формирует список товаров для конкретной страницы;
 * - Добавляет товар в корзину.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Catalog extends BaseController {

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

    // Если происходит поиск по ключевым словам.
    $keyword = URL::getQueryParametr('search');

    if (!empty($keyword)) {
      $items = $model->getListProductByKeyWord($keyword);

      $searchData = array('keyword' => $keyword, 'count' => $items['numRows']);
    } else {

      // Получаем список вложенных категорий, для вывода всех продуктов, на страницах текущей категории.
      $model->categoryId = MG::get('category')->getCategoryList($_REQUEST['category_id']);

      // В конец списка, добавляем корневую текущую категорию.
      $model->categoryId[] = $_REQUEST['category_id'];

      // Передаем номер требуемой страницы, и количество выводимых объектов.
      $countСatalogProduct = $settings['countСatalogProduct'];
      $items = $model->getList($countСatalogProduct, false, true);
    }
    $settings = MG::get('settings');

    foreach ($items['catalogItems'] as $item) {
      $productIds[] = $item['id'];
    }

    $product = new Models_Product;
    $blocksVariants = $product->getBlocksVariantsToCatalog($productIds);

    foreach ($items['catalogItems'] as $k => $item) {
      $items['catalogItems'][$k]["recommend"] = 0;
      $items['catalogItems'][$k]["new"] = 0; 
      $imagesUrl = explode("|", $item['image_url']);
      $items['catalogItems'][$k]["image_url"] = "";
      if (!empty($imagesUrl[0])) {
        $items['catalogItems'][$k]["image_url"] = $imagesUrl[0];
      }

      $items['catalogItems'][$k]['title'] = MG::modalEditor('catalog', $item['title'], 'edit', $item["id"]);
      // Формируем варианты товара.
      if ($item['variant_exist']) {

        // Легкая форма без характеристик.
        $liteFormData = $product->createPropertyForm($param = array(
          'id' => $item['id'],
          'maxCount' => $item['count'],
          'productUserFields' => null,
          'action' => "/catalog",
          'method' => "POST",
          'ajax' => true,
          'blockedProp' => array(),
          'noneAmount' => true,
          'titleBtn' => "В корзину",
          'blockVariants' => $blocksVariants[$item['id']]
        ));
        $items['catalogItems'][$k]['liteFormData'] = $liteFormData['html'];
      }
    }

    $categoryDesc = MG::get('category')->getDesctiption($_REQUEST['category_id']);

    if ($_REQUEST['category_id']) {
      $categoryDesc = MG::inlineEditor(PREFIX.'category', "html_content", $_REQUEST['category_id'], $categoryDesc);
    }

    $data = array(
      'items' => $items['catalogItems'],
      'titeCategory' => $model->currentCategory['title'],
      'cat_desc' => $categoryDesc,
      'pager' => $items['pager'],
      'searchData' => empty($searchData) ? '' : $searchData,
      'meta_title' => !empty($model->currentCategory['meta_title']) ? $model->currentCategory['meta_title'] : $model->currentCategory['title'],
      'meta_keywords' => !empty($model->currentCategory['meta_keywords']) ? $model->currentCategory['meta_keywords'] : "товары,продукты,изделия",
      'meta_desc' => !empty($model->currentCategory['meta_desc']) ? $model->currentCategory['meta_desc'] : "В каталоге нашего магазина есть все.",
      'currency' => $settings['currency'],
      'actionButton' => MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView'
    );

    if (URL::isSection('catalog')) {
      $page = new Models_Page;
      $html = $page->getPageByUrl('catalog');
      $html['html_content'] = MG::inlineEditor(PREFIX.'page', "html_content", $html['id'], $html['html_content']);
      $data['meta_title'] = $html['meta_title'] ? $html['meta_title'] : $html['title'];
      $data['meta_title'] = $data['meta_title'] ? $data['meta_title'] : $model->currentCategory['title'];
      $data['meta_keywords'] = $html['meta_keywords'];
      $data['meta_desc'] = $html['meta_desc'];
      $data['cat_desc'] = $html['html_content'];
    }
    if ($keyword) {
      $data['meta_title'] = 'Поиск по фразе: '.$keyword;
    }

    $this->data = $data;
  }

}