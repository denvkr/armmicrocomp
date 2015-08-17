<?php

/**
 * Класс SmalCart - моделирует данные для маленькой корзины.
 *  - Предоставляет массив с количеством товаров и их общей стоимостью.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class SmalCart {

  /**
   * Записывает в cookie текущее состояние
   * корзины в сериализованном виде.
   * @return void
   */
  public static function setCartData() {
    // Сериализует  данные корзины из сессии в строку.
    // $cartContent = serialize($_SESSION['cart']);
    // Записывает сериализованную строку в куки, хранит 1 год.
    // SetCookie('cart', $cartContent, time()+3600*24*365);
    // MG::createHook(__CLASS__."_".__FUNCTION__, $cartContent);
  }

  /**
   * Получает данные из куков назад в сессию.
   * @return bool
   */
  public static function getCokieCart() {
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $_SESSION['cart'], $args);
  }

  /**
   * Вычисляет общую стоимость содержимого, а также количество.
   * @return array массив с данными о количестве и цене.
   */
  public static function getCartData() {
    $modelCart = new Models_Cart();
    // Количество вещей в корзине.
    $res['cart_count'] = 0;

    // Общая стоимость.
    $res['cart_price'] = 0;

    // Если удалось получить данные из куков и они успешно десериализованы в $_SESSION['cart'].
    //self::getCokieCart() &&
    if (!empty($_SESSION['cart'])) {
      $settings = MG::get('settings');

      $totalPrice = 0;
      $totalCount = 0;


      if (!empty($_SESSION['cart'])) {
        $itemIds = array();

        foreach ($_SESSION['cart'] as $key => $item) {
          if (!empty($item['id'])) {
            $itemIds[] = $item['id'];
          }
        }

        if (!empty($itemIds)) {
          // Пробегаем по содержимому.
          $where = ' IN ('.trim(DB::quote(implode(',', $itemIds)), "'").')';
        }
      } else {
        $where = ' IN (0)';
      }

      // Пробегаем по содержимому.
      //   $where = ' IN ('.trim(DB::quote(implode(',',$itemIds)),"'").')';
      $result = DB::query('
          SELECT CONCAT(c.parent_url,c.url) AS category_url, p.url AS product_url, p.*
          FROM `'.PREFIX.'product` AS p
          LEFT JOIN `'.PREFIX.'category` AS c ON c.id = p.cat_id
          WHERE p.id '.$where);

      $itemPosition = new Models_Product();
      while ($row = DB::fetchAssoc($result)) {
        $rowTitle = $row['title'];
        foreach ($_SESSION['cart'] as $key => $item) {
          $variant = null;

          if (!empty($item['variantId']) && $item['id'] == $row['id']) {
            $variants = $itemPosition->getVariants($row['id']);
            $variant = $variants[$item['variantId']];
            $row['price'] = $variant['price'];
            $row['code'] = $variant['code'];
            $row['count'] = $variant['count'];
            $row['title'] = $rowTitle." ".$variant['title_variant'];
            $row['variantId'] = $variant['id'];
          }

          $price = $row['price'];
          if ($item['id'] == $row['id']) {
            $count = $item['count'];
            $row['countInCart'] = $count;
            $row['property_html'] = htmlspecialchars_decode(str_replace('&amp;', '&', $item['property']));
            $price = self::plusPropertyMargin($price, $item['propertyReal']);
            $row['property'] = $item['propertySetId'];

            $price = $modelCart->applyCoupon($_SESSION['couponCode'], $price, $row);
            $row['priceInCart'] = $price * $count." ".$settings['currency'];

            $arrayImages = explode("|", $row['image_url']);
            if (!empty($arrayImages)) {
              $row['image_url'] = $arrayImages[0];
            }

            $res['dataCart'][] = $row;
            $totalPrice += $price * $count;
            $totalCount += $count;
            $itemIds[] = $item['id'];
          }
        }
      }

      $res['cart_price_wc'] = $totalPrice." ".$settings['currency'];
      $res['cart_count'] = $totalCount;
      $res['cart_price'] = $totalPrice;
    }


    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $res, $args);
  }

  /**
   * Прибавляет к стоимости товара дополнительные цены от выбранных характеристик.
   * $price - базовая цена товара
   * $propertyHtml - строка с информацией о выбраных характеристиках
   * @return array массив с данными о количестве и цене.
   */
  public static function plusPropertyMargin($price, $propertyHtml) {
    $m = array();
    preg_match_all("/#([\d\.\,-]*)#</i", $propertyHtml, $m);

    if (!empty($m[1])) {
      //находим все составляющие цены характеристик и прибавляем их к общей стоимости позиции
      foreach ($m[1] as $partPrice) {
        $price+=is_numeric($partPrice * 1) ? $partPrice * 1 : 0;
      }
    }
    return $price;
  }

}