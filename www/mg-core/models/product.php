<?php

/**
 * Модель: Product
 *
 * Класс Models_Product реализует логику взаимодействия с товарами магазина.
 * - Добавляет товар в базу данных;
 * - Изменяет данные о товаре;
 * - Удаляет товар из базы данных;
 * - Получает информацию о запрашиваемом товаре;
 * - Получает продукт по его URL;
 * - Получает цену запрашиваемого товара по его id.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 */
class Models_Product {

  /**
   * Добавляет товар в базу данных. 
   * @param array $array массив с данными о товаре.
   * @return bool|int в случае успеха возвращает id добавленного товара.
   */
  public function addProduct($array, $clone = false) {

    if (empty($array['title'])) {
      return false;
    }

    $userProperty = $array['userProperty'];
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['id']);

    $result = array();

    $array['url'] = empty($array['url']) ? MG::translitIt($array['title']) : $array['url'];
    if (!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);
    }

    // Исключает дублирование.
    $dublicatUrl = false;
    $tempArray = $this->getProductByUrl($array['url']);
    if (!empty($tempArray)) {
      $dublicatUrl = true;
    }

    if (DB::buildQuery('INSERT INTO `'.PREFIX.'product` SET ', $array)) {
      $id = DB::insertId();

      // Если url дублируется, то дописываем к нему id продукта.
      if ($dublicatUrl) {
        $this->updateProduct(array('id' => $id, 'url' => $array['url'].'_'.$id));
      } else {
        $this->updateProduct(array('id' => $id, 'url' => $array['url'], 'sort' => $id));
      }

      $array['id'] = $id;
      $array['userProperty'] = $userProperty;
      $userProp = array();

      if ($clone) {
        if (!empty($userProperty)) {
          foreach ($userProperty as $property) {
            $userProp[$property['property_id']] = $property['value'];
            if (!empty($property['product_margin']) && $property['product_margin'] != 'product_margin') {
              $userProp[("margin_".$property['property_id'])] = $property['product_margin'];
            }
          }
          $userProperty = $userProp;
        }
      }

      if (!empty($userProperty)) {
        $this->saveUserProperty($userProperty, $id);
      }

      // Обновляем и добавляем варианты продукта.      
      $this->saveVariants($variants, $id);
      $variants = $this->getVariants($id);
      foreach ($variants as $variant) {
        $array['variants'][] = $variant;
      }

      $tempProd = $this->getProduct($id);
      $array['category_url'] = $tempProd['category_url'];
      $array['product_url'] = $tempProd['product_url'];

      $result = $array;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Преобразует  массив характеристик в удобный для работы вид.
   * @param type $array
   * @return array
   */
  public function preProcessUserProperty($userProperty) {
    $prefixs = array("margin", "type");

    foreach ($userProperty as $propertyId => $value) {
      foreach ($prefixs as $prefix) {
        if (strpos($propertyId, $prefix."_") !== false) {
          $propertyId = str_replace($prefix."_", "", $propertyId);
          if (is_array($userProperty[$propertyId])) {
            $userProperty[$propertyId][$prefix] = $value;
          } else {
            $userProperty[$propertyId] = array(
              'value' => $userProperty[$propertyId],
              $prefix => $value,
            );
          }
        }
      }
    }
    return $userProperty;
  }

  /**
   * Изменяет данные о товаре.
   * @param array $array массив с данными о товаре.
   * @param int $id  id изменяемого товара.
   * @return bool
   */
  public function updateProduct($array) {

    $id = $array['id'];
    $userProperty = !empty($array['userProperty']) ? $array['userProperty'] : null; //свойства товара
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    $updateFromModal = !empty($array['updateFromModal']) ? true : false; // варианты товара

    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['updateFromModal']);

    if (!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);
    }

    $result = false;

    // Если происходит обновление параметров.
    if (!empty($id)) {
      unset($array['delete_image']);

      // Обновляем стандартные  свойства продукта.
      if (DB::query('
          UPDATE `'.PREFIX.'product`
          SET '.DB::buildPartQuery($array).'
          WHERE id = %d
        ', $id)) {

        // Обновляем пользовательские свойства продукта.
        if (!empty($userProperty)) {
          $this->saveUserProperty($userProperty, $id);
        }

        // Эта проверка нужна только для того, чтобы исключить удаление 
        //вариантов при обновлении продуктов не из карточки товара в админке, 
        //например по нажатию на "лампочку".
        if (!empty($variants) || $updateFromModal) {

          // обновляем и добавляем варианты продукта.
          if ($variants === null) {
            $variants = array();
          }
          $this->saveVariants($variants, $id);
        }

        $result = true;
      }
    } else {
      $result = $this->addProduct($array);
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Обновляет поле в варианте и синхронизирует привязку первого варианта с продуктом.
   * @param type $id - id варианта.
   * @param type $array - ассоциативный массив поле=>значение.
   * @param type $product_id - id продукта.
   * @return boolean
   */
  public function fastUpdateProductVariant($id, $array, $product_id) {
    if (!DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = %d
     ', $id)) {
      return false;
    };

    // Следующие действия выполняются для синхронизации  значений первого 
    // варианта со значениями записи продукта из таблицы product.
    // Перезаписываем в $array новое значение от первого в списке варианта,
    // и получаем id продукта от этого варианта
    $variants = $this->getVariants($product_id);
    $field = array_keys($array);
    foreach ($variants as $key => $value) {
      $array[$field[0]] = $value[$field[0]];
      break;
    }

    // Обновляем продукт в соответствии с первым вариантом.
    $this->fastUpdateProduct($product_id, $array);
    return true;
  }

  /**
   * Аналогичная fastUpdateProductVariant функция, но с поправками для
   * процесса импорта вариантов.
   * @param type $id - id варианта.
   * @param type $array - массив поле=значение.
   * @param type $product_id - id продукта.
   * @return boolean
   */
  public function importUpdateProductVariant($id, $array, $product_id) {

    if (!$id || !DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = %d
     ', $id)) {

      DB::query('
       INSERT INTO `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array)
      );
    };

    return true;
  }

  /**
   * Обновление заданного поля продукта.
   * @param type $id - id продукта.
   * @param type $array - параметры для обновления.
   * @return boolean
   */
  public function fastUpdateProduct($id, $array) {
    if (!DB::query('
      UPDATE `'.PREFIX.'product`
      SET '.DB::buildPartQuery($array).'
      WHERE id = %d
    ', $id)) {
      return false;
    };
    return true;
  }

  /**
   * Сохраняет пользовательские характеристики для товара.
   * @param int $id - id товара.
   * @param array $userProperty набор характеристик.
   * @return boolean
   */
  public function saveUserProperty($userProperty, $id, $type = 'select') {
    $userProperty = $this->preProcessUserProperty($userProperty);

    foreach ($userProperty as $propertyId => $value) {

      // Проверяем существует ли запись в базе о текущем свойстве.
      $res = DB::query("
        SELECT * FROM `".PREFIX."product_user_property`
        WHERE property_id = ".DB::quote($propertyId)."
          AND product_id = ".DB::quote($id)
      );

      // Обновляем значение свойства если оно существовало.
      if (DB::numRows($res)) {
        if (!is_array($value)) {
          DB::query("
            UPDATE `".PREFIX."product_user_property`
            SET value = ".DB::quote($value)."
            WHERE property_id = ".DB::quote($propertyId)."
              AND product_id = ".DB::quote($id)
          );
        } else {
          DB::query("
            UPDATE `".PREFIX."product_user_property`
            SET value = ".DB::quote($value['value']).",
              product_margin = ".DB::quote($value['margin']).",
              type_view = ".DB::quote($value['type'])."
            WHERE property_id = ".DB::quote($propertyId)."
              AND product_id = ".DB::quote($id)
          );
        }
      } else {

        // Создаем новую запись со значением свойства
        // если его небыло сохранено ранее.
        if (!is_array($value)) {
          DB::query("
            INSERT INTO `".PREFIX."product_user_property`
            VALUES (
            ".DB::quote($id).",
            ".DB::quote($propertyId).",
            ".DB::quote($value).",'', ".DB::quote($type).")");
        } else {
          DB::query("
            INSERT INTO `".PREFIX."product_user_property`
            VALUES (
            ".DB::quote($id).",
            ".DB::quote($propertyId).",
            ".DB::quote($value['value']).",
            ".DB::quote($value['margin']).",
            ".DB::quote($value['type'])."
            )");
        }
      }
    }
  }

  /**
   * Сохраняет варианты товара.
   * @param int $id  id товара
   * @param array $variants набор вариантов
   * @return bool
   */
  public function saveVariants($variants = array(), $id) {
    // Удаляем все имеющиеся товары.
    $res = DB::query("
      DELETE FROM `".PREFIX."product_variant` WHERE product_id = ".DB::quote($id)
    );

    // Если вариантов как минимум два.
    if (count($variants) > 1) {
      // Сохраняем все отредактированные варианты.
      $i = 1;
      foreach ($variants as $variant) {
        $variant['sort'] = $i++;
        DB::query(' 
          INSERT  INTO `'.PREFIX.'product_variant` 
          SET product_id= '.DB::quote($id).", ".DB::buildPartQuery($variant)
        );
      }
    }
  }

  /**
   * Клонируем товар.
   * @param int $id  id клонируемого товара.
   * @return bool
   */
  public function cloneProduct($id) {
    $result = false;

    $arr = $this->getProduct($id);
    $image_url = $arr['image_url'];
    $arr['image_url'] = implode("|", $arr['images_product']);
    $userProperty = $arr['thisUserFields'];
    unset($arr['thisUserFields']);
    unset($arr['category_url']);
    unset($arr['product_url']);
    unset($arr['images_product']);
    $arr['userProperty'] = $userProperty;
    $variants = $this->getVariants($id);

    foreach ($variants as &$item) {
      unset($item['id']);
      unset($item['product_id']);
    }

    $arr['variants'] = $variants;
    $result = $this->addProduct($arr, true);
    $result['image_url'] = $image_url;
    $result['currency'] = MG::getSetting('currency');

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Удаляет товар из базы данных.
   *
   * @param int $id  id удаляемого товара
   * @return bool
   */
  public function deleteProduct($id) {
    $result = false;

    // Удаляем продукт из базы.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    // Удаляем все значения пользовательских характеристик даного продукта.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_user_property`
      WHERE product_id = %d
    ', $id);

    // Удаляем все варианты данного продукта.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_variant`
      WHERE product_id = %d
    ', $id);

    $result = true;
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает информацию о запрашиваемом товаре.
   * @patam string $where необезательный пераметр, формирующий условия поиска, например: id = 1
   * @return array массив заказов
   */
  public function getProductByUserFilter($where = '') {
    $result = array();
    if ($where) {
      $where = ' WHERE '.$where;
    }
    $res = DB::query('
      SELECT  *
      FROM `'.PREFIX.'product`'.$where);
    while ($order = DB::fetchAssoc($res)) {
      $result[$order['id']] = $order;
    }
    return $result;
  }

  /**
   * Получает информацию о запрашиваемом товаре по его ID.
   * @param int $id id запрашиваемого товара.
   * @return array массив с данными о товаре.
   */
  public function getProduct($id) {
    $result = array();
    $res = DB::query('
      SELECT  CONCAT(c.parent_url,c.url) as category_url,
        p.url as product_url, p.*
      FROM `'.PREFIX.'product` p
        LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id
      WHERE p.id = %d
    ', $id);

    if (!empty($res)) {
      if ($product = DB::fetchAssoc($res)) {
        $result = $product;

        // Запрос делает следующее 
        // 1. Вычисляет список пользовательских характеристик для категории товара, 
        // 2. Присваивает всем параметрам значания по умолчанию, 
        // 3. Находит заполненные характеристики товара, заменяет ими значения по умолчанию.
        // В результате получаем набор всех пользовательских характеристик включая те, что небыли определены явно.

        $res = DB::query("
          SELECT p.id AS  `property_id` , p.`default` AS  `value` , 'product_margin', 'type_view', p.* 
          FROM  `".PREFIX."property` AS p    
          WHERE p.id IN (
            SELECT p.id AS `property_id` 
            FROM `".PREFIX."category_user_property` AS c, `".PREFIX."property` AS p
            WHERE c.category_id = ".DB::quote($result['cat_id'])."
            AND p.id = c.property_id )          
          
          UNION 
          
          SELECT pup.property_id, pup.value, pup.product_margin, pup.type_view, prop.*
          FROM `".PREFIX."product_user_property` as pup
          LEFT JOIN `".PREFIX."property` as prop
            ON pup.property_id = prop.id
          WHERE pup.`product_id` = ".DB::quote($id)."
            
          UNION 
          
          SELECT prop.id, prop.default, 'product_margin', 'type_view', prop.*         
          FROM `".PREFIX."property` as prop 
          WHERE prop.`all_category` = 1
            
          ORDER BY 1;
        ");

        while ($userFields = DB::fetchAssoc($res)) {
          // Заполняет каждый товар его характеристиками.
          if (isset($result['thisUserFields'][$userFields['property_id']])) {
            // если товар существует и у него type_view == 'type_view', то он будет перезаписан.
            if ($result['thisUserFields'][$userFields['property_id']]['type_view'] == 'type_view') {
              $result['thisUserFields'][$userFields['property_id']] = $userFields;
            }
          } else {
            $result['thisUserFields'][$userFields['property_id']] = $userFields;
          }
        }

        // Получаем массив картинок для продукта, при этом первую в наборе делаем основной.
        $arrayImages = explode("|", $result['image_url']);
        if (!empty($arrayImages)) {
          $result['image_url'] = $arrayImages[0];
        }

        $result['images_product'] = array(0=>$arrayImages[0]);
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Обновляет остатки продукта, увеличивая их на заданное количество
   * @param type $id - номер продукта.
   * @param type $count - прибавляемое значение к остатку.
   * @param type $code - артикул.
   */
  public function increaseCountProduct($id, $code, $count) {

    $sql = "
      UPDATE `".PREFIX."product_variant` as pv 
      SET pv.`count`= pv.`count`+".DB::quote($count)." 
      WHERE pv.`product_id`=".DB::quote($id)." 
        AND pv.`code`=".DB::quote($code)." 
        AND pv.`count`>=0
    ";

    DB::query($sql);

    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= p.`count`+".DB::quote($count)." 
      WHERE p.`id`=".DB::quote($id)." 
        AND p.`code`=".DB::quote($code)." 
        AND  p.`count`>=0
    ";

    DB::query($sql);
  }

  /**
   * Обновляет остатки продукта, уменьшая их количество,
   * при смене статуса заказа с "отменент" на любой другой.
   * 
   * @param type $id - Номер продукта.
   * @param type $count - Прибавляемое значение к остатку.
   * @param type $code - Артикул.
   */
  public function decreaseCountProduct($id, $code, $count) {

    $product = $this->getProduct($id);
    $variants = $this->getVariants($product['id']);
    foreach ($variants as $idVar => $variant) {
      if ($variant['code'] == $code) {
        $variantCount = ($variant['count'] * 1 - $count * 1) >= 0 ? $variant['count'] - $count : 0;
        $sql = "
          UPDATE `".PREFIX."product_variant` as pv 
          SET pv.`count`= ".DB::quote($variantCount, true)." 
          WHERE pv.`id`=".DB::quote($idVar)." 
            AND pv.`code`=".DB::quote($code)." 
            AND  pv.`count`>0";
        DB::query($sql);
      }
    }

    $product['count'] = ($product['count'] * 1 - $count * 1) >= 0 ? $product['count'] - $count : 0;
    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= ".DB::quote($product['count'], true)." 
      WHERE p.`id`=".DB::quote($id)." 
        AND p.`code`=".DB::quote($code)."
        AND  p.`count`>0";
    DB::query($sql);
  }

  /**
   * Удаляет все миниатюры и оригинал изображения товара из папки upload.
   * @return bool
   */
  public function deleteImageProduct($arrayDelImages) {
    if (!empty($arrayDelImages)) {
      foreach ($arrayDelImages as $value) {
        if (!empty($value)) {

          // Удаление картинки с сервера.
          $documentroot = str_replace('mg-core'.DIRECTORY_SEPARATOR.'models', '', __DIR__);
          if (is_file($documentroot."uploads/".basename($value))) {
            unlink($documentroot."uploads/".basename($value));

            if (is_file($documentroot."uploads/thumbs/30_".basename($value))) {
              unlink($documentroot."uploads/thumbs/30_".basename($value));
            }
            if (is_file($documentroot."uploads/thumbs/70_".basename($value))) {
              unlink($documentroot."uploads/thumbs/70_".basename($value));
            }
          }
        }
      }
    }

    return true;
  }

  /**
   * Возвращает общее количество продуктов каталога.
   * @param int $id id запрашиваемого товара.
   * @return array массив с данными о товаре.
   */
  public function getProductsCount() {
    $result = 0;
    $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'product`
    ');

    if ($product = DB::fetchAssoc($res)) {
      $result = $product['count'];
    }

    return $result;
  }

  /**
   * Получает продукт по его URL.
   * @param string $url запрашиваемого товара.
   * @param int $catId id-категории, т.к. в разных категориях могут быть одинаковые url.
   * @return array массив с данными о товаре.
   *
   */
  public function getProductByUrl($url, $catId = false) {
    $result = array();
    if ($catId !== false) {
      $where = ' and cat_id='.DB::quote($catId);
    }

    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'product`
      WHERE url = "%s" 
    '.$where, $url);

    if (!empty($res)) {
      if ($product = DB::fetchArray($res)) {
        $result = $product;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает цену запрашиваемого товара по его id.
   * @param int $id id изменяемого товара.
   * @return bool|float $error в случаи ошибочного запроса.
   */
  public function getProductPrice($id) {
    $result = false;
    $res = DB::query('
      SELECT price
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Создает форму пользовательских характеристик для товара.
   * В качестве входящего параметра получает массив:
   * <code>
    $param = array(
    'id' => null, // id товара.
    'maxCount' => null, // максимальное количество товара на складе.
    'productUserFields' => null, // массив пользовательских полей для данного продукта.
    'action' => "/catalog", // ссылка для метода формы.
    'method' => "POST", // тип отправки данных на сервер.
    'ajax' => true, // использовать ajax для пересчета стоимости товаров.
    'blockedProp' => array(), // массив из ID свойств, которые ненужно выводить в форме.
    'noneAmount' => false, // не выводить  input для количества.
    'titleBtn' => "В корзину", // название кнопки.
    'blockVariants' => '', // блок вариантов.
    'classForButton' => 'addToCart buy-product buy', // классы для кнопки.
    'noneButton' => false, // не выводить кнопку отправки.
    'addHtml' => '' // добавить HTML в содержимое формы.
    )
   * </code>
   * @param int $param - массив параметров.
   * $blockedProp - массив с ID свойствам, которые не надо выводить.
   * @return string html форма.
   */
  public function createPropertyForm(
  $param = array(
    'id' => null,
    'maxCount' => null,
    'productUserFields' => null,
    'action' => "/catalog",
    'method' => "POST",
    'ajax' => true,
    'blockedProp' => array(),
    'noneAmount' => false,
    'titleBtn' => "В корзину",
    'blockVariants' => '',
    'classForButton' => 'addToCart buy-product buy',
    'noneButton' => false,
    'addHtml' => ''
  )
  ) {
    extract($param);
    if (empty($classForButton)) {
      $classForButton = 'addToCart buy-product buy';
    }
    if ($id === null || $maxCount === null) {
      return "error param!";
    }

    // если используется аяксовый метод выбора, то подключаем доп класс для работы с формой. 
    $marginPrice = 0; // добавочная цена, в зависимости от выбраных автоматом характеристик
    $secctionCartNoDummy = array(); //Не подставной массив характеристик, все характеристики с настоящими #ценами#
    //в сессию записать реальные значения, в паблик подмену, с привязкой в конце #№
    $html = '<form action="'.SITE.$action.'" method="'.$method.'" class="property-form">';
    if ($ajax) {
      mgAddMeta("<script type=\"text/javascript\" src=\"".SITE."/mg-core/script/jquery.form.js\"></script>");
    }

    if (!empty($productUserFields)) {
      foreach ($productUserFields as $property) {
        if (in_array($property['id'], $blockedProp)) {
          continue;
        }

        /*
          'select' - набор значений, можно интерпретировать как  выпадающий список либо набор радиокнопок
          'assortment' - мультиселект
          'string' - пара ключь значение
          'assortmentCheckBox' - набор чекбоксов
         */
        switch ($property['type']) {

          case 'select': {
            $html .=  "";
            break;
          } 
          

          case 'assortmentCheckBox': {
            $html .=  "";
            break;
          }

          case 'assortment': {
            $html .=  "";
            break;
          }

          case 'string': {
              $marginStoper = $marginPrice;
              if (!empty($property['value'])) {
                $html .= '<p>'.$property['name'].': <span class="label-black">'.
                  (!empty($property['value']) ? $property['value'] : $property['data']).
                  '</span></p>';
              }
              break;
            }

          default:
            if (!empty($property['data'])) {
              $html .= ''.$property['name'].': <span class="label-black">'.
                str_replace("|", ",", $property['data']).
                '</span>';
            }
            break;
        }
      }

      $_SESSION['propertyNodummy'] = $secctionCartNoDummy;
    }

    $html .= '<div class="buy-container">';
    
    if ($maxCount == "0") {
      $hidder = 'style="display:none"';
    }
    
    if (!$noneAmount) {
      $html .= '<div class="hidder-element" '.$hidder.' ><p class="qty-text">Количество:</p>
        <div class="cart_form">
          <input type="text" name="amount_input" class="amount_input" data-max-count="'.$maxCount.'" value="1" />
          <div class="amount_change">
            <a href="#" class="up">+</a>
            <a href="#" class="down">-</a>
          </div>
        </div>
        </div>';
    }

    $html .= '<div class="hidder-element" '.$hidder.' ><input type="hidden" name="inCartProductId" value="'.$id.'">';
    
    if (!$noneButton) {
      
      // Если товаров на складе нет, то не выводить кнопку в корзину.
      if ($ajax) {
        $html .= '<a class="'.$classForButton.'" href="'.
          SITE.'/catalog?inCartProductId='.$id.'" data-item-id="'.$id.'">'.
          $titleBtn.'</a><input type="submit" name="buyWithProp" onclick="return false;" style="display:none">';
      } else {
        $html .= '<input type="submit" name="buyWithProp">';
      }
    }
    
    $html .= $addHtml;
    $html .='</div>
    </div>';    

    $html .= '</form>';

    $result = array('html' => $html, 'marginPrice' => $marginPrice);
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Формирует блок варинтов товара.
   * 
   * @param $id - id товара
   * @return  string
   */
  public function getBlockVariants($id) {
    $html .='<div class="clear"></div><div class="block-variants">';
    
    // получаем варианты товара
    $i = 0;
    $arr = $this->getVariants($id);
    foreach ($arr as $variant) {
      $disabled = '';
      $outOfStock = '';
      if ($variant['activity'] === "0" || $variant['count'] == 0) {
        $outOfStock = "<span class='reminfo'>Нет в наличии</span>";
      }
      $img = '';
      if (!empty($variant['image'])) {
        $img = '<img src="'.SITE.'/uploads/thumbs/30_'.$variant['image'].'" width="30" height="20">';
      }
      $maxCount = !$i ? $variant['count'] : $maxCount;
      $variant = '<label><input '.$disabled.' type="radio" name="variant" value = '.
        $variant['id'].' '.(!$i ? 'checked=checked' : '').'">
        <span class="label-black"> '.$img.' '.
        $variant['title_variant'].'</span> <span class="varian-price">'.
        $variant['price'].' '.MG::getSetting('currency').'</span> </label>'.
        $outOfStock.'<br>';
   
      $html .= $variant;

      $i++;
    }
    $html .='</div>';
    return $html;
  }

  /**
   * Формирует массив блоков варинтов товаров на странице каталога.
   * Метод создан для сокращения количества запросов к БД.
   * @param $array  - массив id товаров
   * @param $returnArray  - если true то вернет просто массив без html блоков
   * @return  string
   */
  public function getBlocksVariantsToCatalog($array, $returnArray = false) {
    if (!empty($array)) {
      $in = implode(',', $array);
    }
    
    // Получаем все варианты для переданого массива продуктов.
    if ($in) {
      $res = DB::query('
       SELECT  pv.*
       FROM `'.PREFIX.'product_variant` pv    
       WHERE pv.product_id  in ('.$in.')
       ORDER BY sort
     ', $id);

      if (!empty($res)) {
        while ($variant = DB::fetchAssoc($res)) {
          $results[$variant['product_id']][] = $variant;
        }
      }
    }

    if ($returnArray) {
      return $results;
    }

    if (!empty($results)) {
      // Для каждого продукта создаем HTML верстку вариантов.
      foreach ($results as &$blockVariants) {
        $html = '';
        $html .='<div class="clear"></div><div class="block-variants">';
        // Получаем варианты товара.
        $i = 0;

        foreach ($blockVariants as $variant) {
          $disabled = '';
          $outOfStock = '';
          if ($variant['activity'] === "0" || $variant['count'] == 0) {
            // $disabled='disabled';
            $outOfStock = "<span class='reminfo'>Нет в наличии</span>";
          }
          $img = '';
          if (!empty($variant['image'])) {
            $img = '<img src="'.SITE.'/uploads/thumbs/30_'.$variant['image'].'" width="30" height="20">';
          }
          $maxCount = !$i ? $variant['count'] : $maxCount;
          $variant = '<label><input '.$disabled.' type="radio" name="variant" value = '.
            $variant['id'].' '.(!$i ? 'checked=checked' : '').'">
          <span class="label-black"> '.$img.' '.
            $variant['title_variant'].'</span> <span class="varian-price">'.
            $variant['price'].' '.MG::getSetting('currency').'</span> </label>'.
            $outOfStock.'<br>';
         
          $html .= $variant;
          $i++;
        }
        $html .='</div>';
        $blockVariants = $html;
      }
    }
    return $results;
  }

  /**
   * Формирует добавочную строку к названию характеристики,
   * в зависимости от наличия наценки и стоимости.   * 
   * @param $valueArr - массив с наценкой
   * @return  $array - массив с разделенными данными, название пункта и стоимость.
   */
  public function addMarginToProp($margin) {
    $symbol = '+';
    if (!empty($margin)) {
      if ($margin < 0) {
        $symbol = '-';
        $margin = $margin * -1;
      }
    }
    return (!empty($margin)) ? ' '.$symbol.' '.$margin.' '.MG::getSetting('currency') : '';
  }

  /**
   * Отделяет название характеристики от цены название_пункта#стоимость#.
   * Пример входящей строки:
   *  Красный#300#
   * @param $value - строка которую надо распарсить
   * @return  $array - массив с разделенными данными, название пункта и стоимость.
   */
  public function parseMarginToProp($value) {
    $array = array();
    $pattern = "/^(.*)#([\d\.\,-]*)#$/";
    preg_match($pattern, $value, $matches);
    if (isset($matches[1]) && isset($matches[2])) {
      $array = array('name' => $matches[1], 'margin' => $matches[2]);
    }
    return $array;
  }

  /**
   * Обновление состояния корзины.
   */
  public function calcPrice() {
    $product = $this->getProduct($_POST['inCartProductId']);
    if (isset($_POST['variant'])) {
      $variants = $this->getVariants($_POST['inCartProductId']);
      $variant = $variants[$_POST['variant']];
      $product['price'] = $variant['price'];
      $product['code'] = $variant['code'];
      $product['count'] = $variant['count'];
      $product['old_price'] = $variant['old_price'];
    }

    $cart = new Models_Cart;
    $property = $cart->createProperty($_POST);
    $product['price'] = SmalCart::plusPropertyMargin($product['price'], $property['propertyReal']);

    $response = array(
      'status' => 'success',
      'data' => array(
        'price' => $product['price'].' '.MG::getSetting('currency'),
        'old_price' => $product['old_price'].' '.MG::getSetting('currency'),
        'code' => $product['code'],
        'count' => $product['count'],
        'price_wc' => $product['price']
      )
    );

    echo json_encode($response);
    exit;
  }

  /**
   * Возвращает набор вариантов товара.
   * 
   * @param $id - id продукта для поиска его вариантов
   * @return  $array - массив с параметрами варианта.
   */
  public function getVariants($id, $title_variants = false) {
    $results = array();
    if (!$title_variants) {
      $res = DB::query('
      SELECT  pv.*
      FROM `'.PREFIX.'product_variant` pv    
      WHERE pv.product_id = %d 
      ORDER BY sort
    ', $id);
    } else {
      $res = DB::query('
        SELECT  pv.*
        FROM `'.PREFIX.'product_variant` pv    
        WHERE pv.product_id = %d  and pv.title_variant ="%s"
        ORDER BY sort
      ', $id, $title_variants);
    }

    if (!empty($res)) {
      while ($variant = DB::fetchAssoc($res)) {
        $results[$variant['id']] = $variant;
      }
    }
    return $results;
  }

}