<?php

/**
 * Модель: Order
 *
 * Класс Models_Order реализует логику взаимодействия с заказами покупателей.
 * - Проверяет корректность ввода данных в форме оформления заказа;
 * - Добавляет заказ в базу данных.
 * - Отправляет сообщения на электронные адреса пользователя и администраторов, при успешном оформлении заказа.
 * - Удаляет заказ из базы данных.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>

 * @package moguta.cms
 * @subpackage Model
 */
class Models_Order {

  // ФИО покупателя.
  private $fio;
  // Электронный адрес покупателя.
  private $email;
  // Телефон покупателя.
  private $phone;
  // Адрес покупателя.
  private $address;
  // Флаг нового пользователя.
  public $newUser = false;
  // Комментарий покупателя.
  private $info;
  // Массив способов оплаты.
  private $_paymentArray = array();
  // Статичный массив статусов.
  static $status = array(
    0 => 'NOT_CONFIRMED',
    1 => 'EXPECTS_PAYMENT',
    2 => 'PAID',
    3 => 'IN_DELIVERY',
    4 => 'CANSELED',
    5 => 'EXECUTED'
  );

  function __construct() {
    $res = DB::query('SELECT  *  FROM `'.PREFIX.'payment` WHERE id in (6,3,7) ORDER BY id');
    $i = 0;
    while ($row = DB::fetchAssoc($res)) {
      $this->_paymentArray[$row['id']] = $row;
    };
  }

  /**
   * Проверяет корректность ввода данных в форму обратной связи и регистрацию в системе покупателя.
   *
   * @param array $arrayData  массив в введнными пользователем данными.
   * @return bool|string $error сообщение с ошибкой в случае некорректных данных.
   */
  public function isValidData($arrayData) {
    $result = null;

    // Если электронный адрес зарегистрирован в системе.
    $currenUser = USER::getThis();
    if ($currenUser->email != trim($arrayData['email'])) {
      if (USER::getUserInfoByEmail($arrayData['email'])) {
        $error = "<span class='user-exist'>Пользователь с таким email существует. 
          Пожалуйста <a href='".SITE."/enter'>войдите в систему</a> используя 
          свой электронный адрес и пароль!</span>";
        // Иначе новый пользователь.
      } else {
        $this->newUser = true;
      }
    }

    // Корректность емайл.
    if (!preg_match('/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]{0,61}+\.)+[a-z]{2,6}$/', $arrayData['email'])) {
      $error = "<span class='order-error-email'>E-mail введен некорректно!</span>";
    }

    // Наличие телефона.
    if (empty($arrayData['phone'])) {
      $error = "<span class='no-phone'>Введите верный номер телефона!</span>";
    }

    // Если нет ошибок, то заносит информацию в поля класса.
    if (!empty($error)) {
      $result = $error;
    } else {

      $this->fio = trim($arrayData['fio']);
      $this->email = trim($arrayData['email']);
      $this->phone = trim($arrayData['phone']);
      $this->address = trim($arrayData['address']);
      $this->info = trim($arrayData['info']);
      $this->delivery = $arrayData['delivery'];
      $deliv = new Delivery();
      $this->delivery_cost = $deliv->getCostDelivery($arrayData['delivery']);
      $this->payment = $arrayData['payment'];
      $cart = new Models_Cart();
      $this->summ = $cart->getTotalSumm();
      $result = false;
      $this->addNewUser();
    }
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Если заказ оформляется впервые на нового покупателя, то создает новую запись в таблице пользователей.     
   */
  public function addNewUser() {
    // Если заказ производит новый пользователь, то регистрируем его
    if ($this->newUser) {
      USER::add(
        array(
          'email' => $this->email,
          'role' => 2,
          'name' => $this->fio ? $this->fio : 'Пользователь',
          'pass' => crypt(time()),
          'address' => $this->address,
          'phone' => $this->phone,
          'nameyur' => $_POST['yur_info']['nameyur'],
          'adress' => $_POST['yur_info']['adress'],
          'inn' => $_POST['yur_info']['inn'],
          'kpp' => $_POST['yur_info']['kpp'],
          'bank' => $_POST['yur_info']['bank'],
          'bik' => $_POST['yur_info']['bik'],
          'ks' => $_POST['yur_info']['ks'],
          'rs' => $_POST['yur_info']['rs'],
        )
      );
    }
  }

  /**
   * Сохраняет заказ в базу сайта.
   * Добавляет в массив корзины третий параметр 'цена товара', для сохранения в заказ.
   * Это нужно для тогою чтобы в последствии вывести детальную информацию о заказе.
   * Если оставить только id то информация может оказаться неверной, так как цены меняютcя.
   * @return int $id номер заказа.
   */
  public function addOrder($adminOrder = false) {
    $itemPosition = new Models_Product();
    $cart = new Models_Cart();
    $catalog = new Models_Catalog();
    $categoryArray = $catalog->getCategoryArray();
    $this->summ = 0;

    // Массив запросов на обновление количества товаров.
    $updateCountProd = array();

    // Добавляем в массив корзины параметр 'цена товара'.
    if ($adminOrder) {
      $this->email = $adminOrder['user_email'];
      $this->phone = $adminOrder['phone'];
      $this->address = $adminOrder['address'];
      $this->delivery = $adminOrder['delivery_id'];
      $this->delivery_cost = $adminOrder['delivery_cost'];
      $this->payment = $adminOrder['payment_id'];
      $this->fio = $adminOrder['user_email'];
      $formatedDate = date('Y-m-d H:i:s'); // Форматированная дата ГГГГ-ММ-ДД ЧЧ:ММ:СС.

      foreach ($adminOrder['order_content'] as $item) {
        $product = $itemPosition->getProduct($item['id']);
        $_SESSION['couponCode'] = $item['coupon'];
        $productUrl = $product['category_url'].'/'.$product['url'];
        $itemCount = $item['count'];
        if (!empty($product)) {
          $product['price'] = $item['price'];
          $product['price'] = $cart->applyCoupon($_SESSION['couponCode'], $product['price'], $product);
          $discount = 100 - ($product['price'] * 100) / $item['price'];

          $productPositions[] = array(
            'id' => $product['id'],
            'name' => $item['title'],
            'url' => $productUrl,
            'code' => $item['code'],
            'price' => $product['price'],
            'count' => $itemCount,
            'property' => $item['property'],
            'coupon' => $_SESSION['couponCode'],
            'discount' => $discount,
            'info' => $this->info,
          );
          $this->summ += $product['price'] * $itemCount;
          $product['count'] = ($product['count'] - $itemCount) >= 0 ? $product['count'] - $itemCount : 0;
          // По ходу формируем массив запросов на обновление количества товаров.
          $updateCountProd[] = "UPDATE `".PREFIX."product` SET `count`= ".DB::quote($product['count'])." WHERE `id`=".DB::quote($product['id'])." AND `count`>0";
        }
      }
    } elseif (!empty($_SESSION['cart'])) {
      foreach ($_SESSION['cart'] as $item) {
        $product = $itemPosition->getProduct($item['id']);

        // Дописываем к массиву продуктов данные о выбранных характеристиках из корзины покупок, чтобы приплюсовать к сумме заказа.
        if ($item['id'] == $product['id']) {
          $product['property_html'] = $item['propertyReal'];
        }

        $variant = null;

        if (!empty($item['variantId']) && $item['id'] == $product['id']) {
          $variants = $itemPosition->getVariants($product['id']);
          $variant = $variants[$item['variantId']];
          $product['price'] = $variant['price'];
          $product['price'] = $cart->applyCoupon($_SESSION['couponCode'], $product['price'], $product);
          $product['code'] = $variant['code'];
          $product['count'] = $variant['count'];
          $product['title'] .= " ".$variant['title_variant'];

          //По ходу формируем массив запросов на обновление количества товаров
          $resCount = $variant['code'];
          $resCount = ($variant['count'] - $item['count']) >= 0 ? $variant['count'] - $item['count'] : 0;
          $updateCountProd[] = "UPDATE `".PREFIX."product_variant` SET `count`= ".DB::quote($resCount)." WHERE `id`=".DB::quote($item['variantId'])." AND `count`>0";
        }

        $productUrl = $product['category_url'].'/'.$product['url'];

        // Eсли куки не актуальны исключает попадание несуществующего продукта в заказ
        if (!empty($product)) {
          $product['price'] = SmalCart::plusPropertyMargin($product['price'], $product['property_html']);
          $tempPrice = $product['price'];
          $product['price'] = $cart->applyCoupon($_SESSION['couponCode'], $product['price'], $product);
          $discount = 100 - ($product['price'] * 100) / $tempPrice;

          $productPositions[] = array(
            'id' => $product['id'],
            'name' => $product['title'],
            'url' => $productUrl,
            'code' => $product['code'],
            'price' => $product['price'],
            'count' => $item['count'],
            'property' => $item['property'],
            'coupon' => $_SESSION['couponCode'],
            'discount' => $discount,
            'info' => $this->info,
          );

          $this->summ += $product['price'] * $item['count'];
          if (!$resCount) {
            $resCount = ($product['count'] - $item['count']) >= 0 ? $product['count'] - $item['count'] : 0;
          }

          //По ходу формируем массив запросов на обновление количества товаров
          $updateCountProd[] = "UPDATE `".PREFIX."product` SET `count`= ".DB::quote($resCount)." WHERE `id`=".DB::quote($product['id'])." AND `count`>0";
          $resCount = null;
        }
      }
    }

    // Сериализует данные в строку для записи в бд.
    $orderContent = addslashes(serialize($productPositions));

    // Сериализует данные в строку для записи в бд информации об юридическом лице.
    $yurInfo = '';
    if (!empty($adminOrder['yur_info'])) {
      $yurInfo = addslashes(serialize($adminOrder['yur_info']));
    }
    if (!empty($_POST['yur_info'])) {
      $yurInfo = addslashes(serialize($_POST['yur_info']));
    }


    // Создает новую модель корзины, чтобы узнать сумму заказа.
    $cart = new Models_Cart();

    // Генерируем уникальный хэш для подтверждения заказа.
    $hash = $this->_getHash($this->email);

    // Формируем массив параметров для SQL запроса.
    $array = array(
      'user_email' => $this->email,
      'summ' => $this->summ,
      'order_content' => $orderContent,
      'phone' => $this->phone,
      'address' => $this->address,
      'delivery_id' => $this->delivery,
      'delivery_cost' => $this->delivery_cost,
      'payment_id' => $this->payment,
      'paided' => '0',
      'status_id' => '0',
      'confirmation' => $hash,
      'yur_info' => $yurInfo,
      'name_buyer' => $this->fio,
    );

    // Если заказ оформляется через админку.
    if ($adminOrder) {
      $array['comment'] = $adminOrder['comment'];
      $array['status_id'] = $adminOrder['status_id'];
      $array['add_date'] = $formatedDate;
      DB::buildQuery("INSERT INTO `".PREFIX."order` SET ", $array);
    } else {

      // Отдает на обработку  родительской функции buildQuery.
      DB::buildQuery("INSERT INTO `".PREFIX."order` SET add_date = now(), ", $array);
    }

    // Заказ номер id добавлен в базу.
    $id = null;
    $id = DB::insertId();
    unset($_SESSION['couponCode']);

    // Ссылка для подтверждения заказа
    $link = 'ссылке <a href="'.SITE.'/order?sec='.$hash.'&id='.$id.'" target="blank">'.SITE.'/order?sec='.$hash.'&id='.$id.'</a>';
    $table = "";

    // Формирование тела письма.
    if ($id) {

      // Уменьшаем количество купленных товаров
      if (!empty($updateCountProd)) {
        foreach ($updateCountProd as $sql) {
          DB::query($sql);
        }
      }

      // Если заказ создался, то уменьшаем количество товаров на складе.
      $settings = MG::get('settings');
      $delivery = $this->getDeliveryMethod(false, $this->delivery);
      $sitename = $settings['sitename'];
      $currency = MG::getSetting('currency');
      $subj = 'Оформлена заявка №'.$id.' на сайте '.$sitename;
      $subj = str_replace('№', '#', $subj);
      if ($this->fio)
        $table .= '<br/><b>Покупатель:</b> '.$this->fio;
      $table .= '<br/><b>E-mail:</b> '.$this->email;
      $table .= '<br/><b>Тел:</b> '.$this->phone;
      if ($this->address)
        $table .= '<br/><b>Адрес:</b> '.$this->address;
      $table .= '<br/><b>Доставка:</b> '.$delivery['description'];
      $paymentArray = $this->getPaymentMethod($this->payment);
      $table .= '<br/><b>Оплата:</b> '.$paymentArray['name'];
      $table .= '
        <style>
          table {border: 4px double black;border-collapse: collapse;}
          th {text-align: left;background: #ccc;padding: 5px;border: 1px solid black;}
          td {padding: 5px;border: 1px solid black;}
        </style>';
      $table .= '<br><br><table>';

      if (!empty($_SESSION['cart']) || $adminOrder) {
        $table .= '
            <tr>
              <th>Наименование товара</th>
              <th>Артикул</th>
              <th>Стоимость</th>
              <th>Количество</th>
            </tr>';

        foreach ($productPositions as $product) {
          $product['property'] = htmlspecialchars_decode(str_replace('&amp;', '&', $product['property']));
          $table .= '
            <tr>
              <td>'.$product['name'].$product['property'].'</td>
              <td>'.$product['code'].'</td>
              <td>'.$product['price'].' '.$currency.'</td>
              <td>'.$product['count'].' шт.</td>
            </tr>';
        }
      }

      $table .= '</table>';
      $table .= '<br><b>Итого:</b> '.$this->summ.' '.$currency;
      $table .= '<br/><b>Стоимость доставки:</b> '.$this->delivery_cost." ".$currency;
      $totalSumm = $this->delivery_cost + $this->summ;
      $table .= '<br/><b>Всего к оплате:</b> <span style="color:red">'.$totalSumm.' '.$currency.'</span>';
      $msg = MG::getSetting('orderMessage').'<br><u>Обязательно подтвердите</u> свой заказ, перейдя по '.$link.'.<br>'.$table;
      $msg = str_replace('#ORDER#', $id, $msg);
      $msg = str_replace('#SITE#', $sitename, $msg);
      $msg = str_replace('№', '#', $msg);
      $mails = explode(',', MG::getSetting('adminEmail'));

      // Отправка заявки админам.
      // Дополнительная информация для админов.
      $msgAdmin.= '<br/><br/><b>Покупатель перешел к нам на сайт из: </b><br/>'.$_SESSION['lastvisit'].'<br/><br/><b>Покупатель впервые перешел к нам на сайт из: </b><br/>'.$_SESSION['firstvisit'];

      foreach ($mails as $mail) {
        if (preg_match('/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]+\.)+[a-z]{2,6}$/', $mail)) {
          Mailer::addHeaders(array("Reply-to" => $this->email));
          Mailer::sendMimeMail(array(
            'nameFrom' => $this->fio,
            'emailFrom' => MG::getSetting('noReplyEmail'),
            'nameTo' => $sitename,
            'emailTo' => $mail,
            'subject' => $subj,
            'body' => $msg.$msgAdmin,
            'html' => true
          ));
        }
      }

      // Добавление в тело письма ссылки для задания пароля.
      $msg .= '<br>Подтвердите свой заказ, перейдя по '.$link;

      // Отправка заявки пользователю.
      Mailer::sendMimeMail(array(
        'nameFrom' => $sitename,
        'emailFrom' => MG::getSetting('noReplyEmail'),
        'nameTo' => $this->fio,
        'emailTo' => $this->email,
        'subject' => $subj,
        'body' => $msg,
        'html' => true
      ));

      // Если заказ успешно записан, то очищает корзину.
      if (!$adminOrder) {
        $cart->clearCart();
      }
    }

    // Возвращаем номер созданого заказа.
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $id, $args);
  }

  /**
   * Изменяет данные о заказе
   *
   * @param array $array массив с данными о заказе.
   * @return bool
   */
  public function updateOrder($array) {
    $id = $array['id'];
    unset($array['id']);
    $this->refreshCountProducts($id, $array['status_id']);

    $result = false;
    if (!empty($id)) {
      if (DB::query('
        UPDATE `'.PREFIX.'order`
        SET '.DB::buildPartQuery($array).'
        WHERE id = %d
      ', $id)) {
        $result = true;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Пересчитывает количество остатков продуктов при отменении заказа.
   * @param int $orderId  - id заказа.
   * @param int $status_id  - новый статус заказа.
   * @return bool
   */
  public function refreshCountProducts($orderId, $status_id) {
    // Если статус меняется на "Отменен", то пересчитываем остатки продуктов из заказа.
    $order = $this->getOrder(' id = '.DB::quote($orderId, true));

    // Увеличиваем колличество товаров. 
    if ($status_id == 4) {
      if ($order[$orderId]['status_id'] != 4) {
        $order_content = unserialize(stripslashes($order[$orderId]['order_content']));
        $product = new Models_Product();
        foreach ($order_content as $item) {
          $product->increaseCountProduct($item['id'], $item['code'], $item['count']);
        }
      }
    } else {
      // Уменьшаем колличество товаров. 
      if ($order[$orderId]['status_id'] == 4) {
        $order_content = unserialize(stripslashes($order[$orderId]['order_content']));
        $product = new Models_Product();
        foreach ($order_content as $item) {
          $product->decreaseCountProduct($item['id'], $item['code'], $item['count']);
        }
      }
    }
  }

  /**
   * Удаляет заказ из базы данных.
   * @param int $id  id удаляемого заказа
   * @return bool
   */
  public function deleteOrder($id) {
    $result = false;

    if (DB::query('
      DELETE
      FROM `'.PREFIX.'order`
      WHERE id = %d
    ', $id)) {
      $result = true;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив заказов подцепляя данные о способе доставки.
   * @patam string $where необезательный пераметр, формирующий условия поиска заказа, например: id = 1
   * @return array массив заказов
   */
  public function getOrder($where = '') {

    if ($where) {
      $where = 'WHERE '.$where;
    }

    $result = DB::query('
      SELECT  *
      FROM `'.PREFIX.'order`'.$where);

    while ($order = DB::fetchAssoc($result)) {
      $delivery = $this->getDeliveryMethod(false, $order['delivery_id']);
      $order['description'] = $delivery['description'];
      $order['cost'] = $delivery['cost'];
      $orderArray[$order['id']] = $order;
    }
    return $orderArray;
  }

  /**
   * Устанавливает переданный статус заказа.
   *
   * @param int $id - номер заказа.
   * @param int $statusId  - статус заказа.
   * @return boolean результат выполнения метода.
   */
  public function setOrderStatus($id, $statusId) {
    $res = DB::query('
      UPDATE `'.PREFIX.'order`
      SET status_id = %d
      WHERE id = %d', $statusId, $id);

    if ($res) {
      return true;
    }

    return false;
  }

  /**
   * Генерация случайного хэша.
   * @param string $string - строка, на основе которой готовится хэш.
   * @return string случайный хэш
   * @private
   */
  public function _getHash($string) {
    $hash = htmlspecialchars(crypt($string));
    return $hash;
  }

  /**
   * Получение данных о способах доставки.
   *
   * @return array массив содержащий способы доставки.
   */
  public function getDeliveryMethod($returnArray = true, $id = -1) {

    if ($returnArray) {

      $deliveryArray = array();
      $result = DB::query('SELECT  *  FROM `'.PREFIX.'delivery` ORDER BY id');
      while ($delivery = DB::fetchAssoc($result)) {
        $deliveryArray[$delivery['id']] = $delivery;
        $deliveryIds[] = $delivery['id'];
      }

      if (!empty($deliveryIds)) {
        $in = 'in('.implode(',', $deliveryIds).')';
        $deliveryCompareArray = array();
        $res = DB::query('
          SELECT  *  
          FROM `'.PREFIX.'delivery_payment_compare` 
          WHERE `delivery_id` '.$in);
        while ($row = DB::fetchAssoc($res)) {
          $deliveryCompareArray[$row['delivery_id']][] = $row;
        }
      }

      foreach ($deliveryArray as &$item) {
        // Получаем доступные методы оплаты $delivery['paymentMethod'] для данного способа доставки.
        $jsonStr = '{';
        foreach ($deliveryCompareArray[$item['id']] as $compareMethod) {
          $jsonStr .= '"'.$compareMethod['payment_id'].'":'.$compareMethod['compare'].',';
        }
        $jsonStr = substr($jsonStr, 0, strlen($jsonStr) - 1);
        $jsonStr .= '}';
        $item['paymentMethod'] = $jsonStr;
      }

      return $deliveryArray;
    } elseif ($id >= 0) {
      $result = DB::query('
        SELECT description, cost
        FROM `'.PREFIX.'delivery`
        WHERE id = %d', $id);
      return DB::fetchAssoc($result);
    }
  }

  /**
   * Расшифровка по id статуса заказа.
   *
   * @param int $statusId - id статуса заказа.
   * @return string
   */
  public function getOrderStatus($statusId) {
    return self::$status[$statusId['status_id']];
  }

  /**
   * Расшифровка по id методов оплаты.
   *
   * @param int $paymentId
   * @return array
   */
  public function getPaymentMethod($paymentId) {

    if (count($this->_paymentArray) < $paymentId) {
      return false;
    }

    //получаем доступные методы доставки $this->_paymentArray[$paymentId]['deliveryMethod'] для данного сопособа оплаты
    //массив соответствия доставки к данному методу.
    $compareArray = $this->getCompareMethod('payment_id', $paymentId);

    if (count($compareArray)) {
      $jsonStr = '{';

      foreach ($compareArray as $compareMethod) {
        $jsonStr .= '"'.$compareMethod['delivery_id'].'":'.$compareMethod['compare'].',';
      }

      $jsonStr = substr($jsonStr, 0, strlen($jsonStr) - 1);
      $jsonStr .= '}';
      $this->_paymentArray[$paymentId]['deliveryMethod'] = $jsonStr;
    }
    return $this->_paymentArray[$paymentId];
  }

  /**
   * Получает набор всех способов доставки.
   * @return array
   */
  public function getPaymentBlocksMethod() {

    $paymentArray = array();

    $result = DB::query('SELECT  *  FROM `'.PREFIX.'payment` ORDER BY id');
    while ($payment = DB::fetchAssoc($result)) {
      $paymentArray[$payment['id']] = $payment;
      $paymentIds[] = $payment['id'];
    }

    $compareArray = array();
    if (!empty($paymentIds)) {
      $in = 'in('.implode(',', $paymentIds).')';
      $res = DB::query('
          SELECT  *  
          FROM `'.PREFIX.'delivery_payment_compare` 
          WHERE `payment_id` '.$in);
      while ($row = DB::fetchAssoc($res)) {
        $compareArray[$row['payment_id']][] = $row;
      }
    }

    foreach ($paymentArray as &$item) {

      // Получаем доступные методы оплаты $delivery['paymentMethod'] для данного способа доставки.
      $jsonStr = '{';
      if (empty($compareArray[$item['id']])) {
        continue;
      }

      foreach ($compareArray[$item['id']] as $compareMethod) {
        $jsonStr .= '"'.$compareMethod['delivery_id'].'":'.$compareMethod['compare'].',';
      }
      $jsonStr = substr($jsonStr, 0, strlen($jsonStr) - 1);
      $jsonStr .= '}';
      $item['deliveryMethod'] = $jsonStr;
    }

    return $paymentArray;
  }

  /**
   * Возвращает весь список способов оплаты в ассоциативном массиве с индексами.
   * @return array
   */
  public function getListPayment() {
    $result = array();
    $res = DB::query('SELECT  *  FROM `'.PREFIX.'payment`');

    while ($row = DB::fetchAssoc($res)) {
      $result[$row['id']] = $row['name'];
    }
    return $result;
  }

  /**
   * Возвращает максимальную сумму заказа.
   * @return array
   */
  public function getMaxPrice() {
    $res = DB::query('
      SELECT MAX(`summ`+`delivery_cost`) as summ 
      FROM `'.PREFIX.'order`');

    if ($row = DB::fetchObject($res)) {
      $result = $row->summ;
    }

    return $result;
  }

  /**
   * Возвращает минимальную сумму заказа.
   * @return array
   */
  public function getMinPrice() {
    $res = DB::query('
      SELECT MIN(`summ`+`delivery_cost`) as summ 
      FROM `'.PREFIX.'order`'
    );
    if ($row = DB::fetchObject($res)) {
      $result = $row->summ;
    }
    return $result;
  }

  /**
   * Возвращает весь список способов доставки в ассоциативном массиве с индексами.
   * @return array
   */
  public function getListDelivery() {
    $result = array();
    $res = DB::query('SELECT  *  FROM `'.PREFIX.'delivery`');
    while ($row = DB::fetchAssoc($res)) {
      $result[$row['id']] = $row['name'];
    }
    return $result;
  }

  /**
   * Получение статуса оплаты.
   *
   * @param int $paidedId
   * @return string
   */
  public function getPaidedStatus($paidedId) {

    if (1 == $paidedId['paided']) {
      return 'оплачен';
    } else {
      return 'не оплачен';
    }
  }

  /**
   * Возвращает общее количество заказов.
   * $where - условие выбора
   */
  public function getOrderCount($where = '') {
    $result = 0;
    $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'order`
    '.$where);

    if ($order = DB::fetchAssoc($res)) {
      $result = $order['count'];
    }

    return $result;
  }

  /**
   * Возвращает информацию о соответствии методов оплаты к методам доставки.
   * $where - условие выбора
   */
  private function getCompareMethod($methodSearch, $id) {
    $result = array();
    $res = DB::query('
      SELECT  *  
      FROM `'.PREFIX.'delivery_payment_compare` 
      WHERE `%s` = %d', $methodSearch, $id);
    while ($row = DB::fetchAssoc($res)) {
      $result[] = $row;
    }
    return $result;
  }

  /**
   * Отправляет сообщение  об оплате заказа.
   * @param string $orderNamber - номер заказа.
   * @param string $paySumm - сумма заказа.
   * @param string $pamentId - id способа оплаты.
   */
  public function sendMailOfPayed($orderNamber, $paySumm, $pamentId) {
    $pamentArray = $this->_paymentArray[$pamentId];
    $siteName = MG::getSetting('sitename');
    $adminEmail = MG::getSetting('adminEmail');
    $subj = 'Оплата заказа '.$orderNamber.' на сайте '.$siteName;
    $msg = '
      Вы получили это письмо, так как произведена оплата заказа '.
      $orderNamber.' на сумму '.$paySumm.' '.MG::getSetting('currency').
      '. Оплата произведена при помощи '.$pamentArray['name'].
      '<br/> Статус заказа сменен на "Оплачен"';

    $mails = explode(',', MG::getSetting('adminEmail'));

    foreach ($mails as $mail) {
      if (preg_match('/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]+\.)+[a-z]{2,6}$/', $mail)) {
        Mailer::sendMimeMail(array(
          'nameFrom' => $siteName,
          'emailFrom' => MG::getSetting('noReplyEmail'),
          'nameTo' => $sitename,
          'emailTo' => $mail,
          'subject' => $subj,
          'body' => $msg,
          'html' => true
        ));
      }
    }
  }

  /**
   * Уведомляет админов о смене статуса заказа пользователем, высылая им письма.
   * @param type $orderNumber - номер заказа.
   */
  public function sendMailOfUpdateOrder($orderNumber) {
    $siteName = MG::getSetting('sitename');
    $adminEmail = MG::getSetting('adminEmail');
    $subj = 'Пользователь отменил заказ #'.$orderNumber.' на сайте '.$siteName;
    $msg = '
      Вы получили это письмо, так как произведена смена статуса заказа.
     <br/>Статус заказа #'.$orderNumber.' сменен на "Отменен".';

    $mails = explode(',', MG::getSetting('adminEmail'));

    foreach ($mails as $mail) {
      if (preg_match('/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]+\.)+[a-z]{2,6}$/', $mail)) {
        Mailer::sendMimeMail(array(
          'nameFrom' => $siteName,
          'emailFrom' => MG::getSetting('noReplyEmail'),
          'nameTo' => $sitename,
          'emailTo' => $mail,
          'subject' => $subj,
          'body' => $msg,
          'html' => true
        ));
      }
    }
  }

  /**
   * Полученнияе массива параметров оплаты.
   * @param int $pay - id способа оплаты.
   * @return array параметры оплаты.
   */
  public function getParamArray($pay, $orderId, $summ) {
    $paramArray = array();
    $jsonPaymentArray = json_decode(nl2br($this->_paymentArray[$pay]['paramArray']), true);
    if (!empty($jsonPaymentArray)) {
      foreach ($jsonPaymentArray as $paramName => $paramValue) {
        $paramArray[] = array('name' => $paramName, 'value' => $paramValue);
      }
      if (5 == $pay) { // Для robokassa добавляем сигнатуру.
        $paramArray['sign'] = md5($paramArray[0]['value'].":".$summ.":".$orderId.":".$paramArray[1]['value']);
      }
    }
    return $paramArray;
  }

  /**
   * Полученнияе общего количества невыполненых заказов.
   * @return int - количество заказов
   */
  public function getNewOrdersCount() {
    $sql = "
  		SELECT `id`
      FROM `".PREFIX."order`
      WHERE `status_id`!=5 AND `status_id`!=4";

    $res = DB::query($sql);
    $count = DB::numRows($res);
    return $count ? $count : 0;
  }

  /**
   * Полученнияе статистики заказов за каждый день начиная с открытия магазина.
   * @return array - [время, значение]
   */
  public function getOrderStat() {
    $result = array();
    $res = DB::query('    
      SELECT (UNIX_TIMESTAMP( CAST( o.add_date AS DATE ) ) * 1000) as "date" , COUNT( add_date ) as "count"
      FROM `'.PREFIX.'order` AS o
      GROUP BY CAST( o.add_date AS DATE )
    ');

    while ($order = DB::fetchAssoc($res)) {
      $result[] = array($order['date'] * 1, $order['count'] * 1);
    }
    return $result;
  }

  /**
   * Полученнияе статистики заказов за выбранный период. 
   * @param $dateFrom дата "от".
   * @param $dateTo дата "До".
   * @return array
   */
  public function getStatisticPeriod($dateFrom, $dateTo) {
    $dateFromRes = $dateFrom;
    $dateToRes = $dateTo;
    $dateFrom = date('Y-m-d', strtotime($dateFrom));
    $dateTo = date('Y-m-d', strtotime($dateTo));
    $period =
      "AND `add_date` >= ".DB::quote($dateFrom)."
       AND `add_date` <= ".DB::quote($dateTo);

    // Количество закрытых заказов всего.
    $ordersCount = $this->getOrderCount('WHERE status_id = 5 '.$period);

    $noclosed = $this->getOrderCount('WHERE status_id <> 5 '.$period);

    // Cумма заработанная за все время работы магазина.
    $res = DB::query("
      SELECT sum(summ) as 'summ'  FROM `".PREFIX."order`
      WHERE status_id = 5 ".$period
    );

    if ($row = DB::fetchAssoc($res)) {
      $summ = $row['summ'];
    }

    $product = new Models_Product;
    $productsCount = $product->getProductsCount();
    $res = DB::query("SELECT id  FROM `".PREFIX."user`");
    $usersCount = DB::numRows($res);

    $result = array(
      'from_date_stat' => $dateFromRes,
      'to_date_stat' => $dateToRes,
      "orders" => $ordersCount ? $ordersCount : "0",
      "noclosed" => $noclosed ? $noclosed : "0",
      "summ" => $summ ? $summ : "0",
      "users" => $usersCount ? $usersCount : "0",
      "products" => $productsCount ? $productsCount : "0",
    );

    return $result;
  }

  /**
   * Выводит на экран печатную форму для печати заказа в админке.
   * @param int $id - id заказа.
   * @param boolean $sign использовать ли подпись.
   * @return array 
   */
  public function printOrder($id, $sign = true) {
    $orderInfo = $this->getOrder('id='.DB::quote($id, true));

    $order = $orderInfo[$id];

    $perOrders = unserialize(stripslashes($order['order_content']));
    $currency = MG::getSetting('currency');
    $totSumm = $order['summ'] + $order['cost'];
    $paymentArray = $this->getPaymentMethod($order['payment_id']);
    $order['name'] = $paymentArray['name'];

    $propertyOrder = MG::getOption('propertyOrder');
    $propertyOrder = stripslashes($propertyOrder);
    $propertyOrder = unserialize($propertyOrder);


    $paramArray = $this->getParamArray(7, $order['id'], $order['summ']);
    foreach ($paramArray as $k => $field) {
      $paramArray[$k]['value'] = htmlentities($paramArray[$k]['value'], ENT_QUOTES, "UTF-8");
    }

    $customer = unserialize(stripslashes($order['yur_info']));

    $customerInfo = $customer['inn'].', '.$customer['kpp'].', '.
      $customer['nameyur'].', '.$customer['adress'].', '.
      $customer['bank'].', '.$customer['bik'].', '.$customer['ks'].', '.
      $customer['rs'].', '.$customer['nameyur'];


    $ylico = false;
    if (empty($order['yur_info'])) {
      $fizlico = true;
      $userInfo = USER::getUserInfoByEmail($order['user_email']);
      $customerInfo = $userInfo->name.' '.$userInfo->sname.','.
        $order['address'].', тел. '.
        $order['phone'].', '.$order['email'];
    }

    $html = '
      <style type="text/css">
       .form-wrapper table{border-collapse: collapse;width:100%;color:#000;}
       .form-wrapper small-table{border-collapse: separate;}
       .form-wrapper table tr th{padding: 10px;border: 1px solid #000;background:#FFFFE0;}
	     .form-wrapper .who-pay tr td{padding: 5px;}
	     .form-wrapper .who-pay tr td.name{width: 110px;}
	     .form-wrapper .who-pay{margin: 10px 0 0 0;}
       .form-wrapper table tr td{padding: 5px;border: 1px solid #000;}
       .form-wrapper table tr td.bottom{border: none;text-align: right;}
	     .form-wrapper .order-total{margin: 10px 0 0 0;color:#000;}
	     .form-wrapper .title{text-align:center;font-size:24px;color:#000;}
	     .form-wrapper .total-list{list-style:none;}
	     .form-wrapper .no-border, .form-wrapper .who-pay tr td, .form-wrapper .small-table tr td{border:none;}
	     .form-wrapper .colspan4{border:none;text-align:right;}
	     .form-wrapper .rowspan2{vertical-align:bottom;}
	     .form-wrapper .nowrap{white-space:nowrap;}
       .yur-table td {height:30px;}
       .form-table td {height:30px; vertical-align: baseline;}
       .p {height:30px; vertical-align: baseline;}
     </style>
     <div class="form-wrapper">
	   <strong>'.$propertyOrder['nameyur'].'</strong><br>
	   '.$propertyOrder['adress'].'
	   <br/>
	   <br/>
	   <table class="yur-table">
			<tr>
				<td>
          ИНН  '.$propertyOrder['inn'].'
        </td>
				<td>КПП '.$propertyOrder['kpp'].'</td>
				<td rowspan="2" class="rowspan2 nowrap" valign="middle">Сч. №</td>
				<td rowspan="2" class="rowspan2" valign="bottom">'.$propertyOrder['rs'].'</td>
			</tr>
			<tr>
				<td colspan="2">Получатель <br>'.$propertyOrder['nameyur'].'</td>
			</tr>
			<tr>
				<td colspan="2" rowspan="2">Банк получателя <br>'.$propertyOrder['bank'].'</td>
				<td>БИК</td>
				<td>'.$propertyOrder['bik'].'</td>
			</tr>
			<tr>
				<td class="nowrap">Сч. №</td>
				<td>'.$propertyOrder['ks'].'</td>
			</tr>
	   </table>
         <h1 class="title">
           Счет <strong>№ '.$propertyOrder['prefix'].$order['id'].'</strong>
           от '.date('d.m.Y', strtotime($order['add_date'])).'						
         </h1>
		 <table class="who-pay">
			<tr>
				<td class="name" width="100">Плательщик:</td>
				<td width="760">'.$customerInfo.'</td>
			</tr>
		 </table>
		 <br />
		 <br />
     <table class="form-table">
       <tr>
         <th bgcolor="#FFFFE0" width="40">№</th>
         <th bgcolor="#FFFFE0" width="327">Товар</th>
         <th bgcolor="#FFFFE0" >Артикул</th>
         <th bgcolor="#FFFFE0" >Цена</th>
         <th bgcolor="#FFFFE0" width="70">Кол-во</th>
         <th bgcolor="#FFFFE0" width="50">НДС</th>
         <th bgcolor="#FFFFE0" >Сумма</th>
       </tr>';
    
    $i = 1;
    $ndsPercent = is_numeric($propertyOrder['nds']) ? $propertyOrder['nds'] : 0;
    $totalNds = 0;
    if ($ndsPercent === 0) {
      $totalNds = '-';
    }

    if (!empty($perOrders))
      foreach ($perOrders as $perOrder) {
        if ($totalNds !== '-') {
          $marginNds = $perOrder['price'] * $ndsPercent / (100 + $ndsPercent);
          $perOrder['price'] -= $marginNds;
          $totalNds+=$marginNds;
        }
        $html .= '<tr>
            <td style="padding: 5px;">'.$i++.'</td>
            <td cellpadding="5">
              '.$perOrder['name'].'
              '.htmlspecialchars_decode(str_replace('&amp;', '&', $perOrder['property'])).'
            </td>
            <td >'.$perOrder['code'].'</td>
            <td >'.sprintf('%2.2f', $perOrder['price']).'  '.$currency.'</td>
            <td >'.$perOrder['count'].' шт.</td>
            <td >'.(($propertyOrder['nds'] >= 0 && is_numeric($propertyOrder['nds'])) ? $propertyOrder['nds'].'%' : '---').'</td>
            <td >'.sprintf('%2.2f', $perOrder['price'] * $perOrder['count']).'  '.$currency.'</td>
          </tr>';
      }

    $html .= '
      <tr>
        <td colspan="6" class="colspan4">
          <strong>Итого без НДС:</strong>
        </td>
        <td>'.sprintf('%2.2f', ($order['summ'] - $totalNds)).'  '.$currency.'</td>
      </tr>
      <tr>
		<td colspan="6" class="colspan4">
			<strong>Итого НДС:</strong>
		</td>';
    if ($totalNds !== '-') {
      $html .='<td>'.sprintf('%2.2f', $totalNds).'  '.$currency.'</td>';
    } else {
      $html .='<td>---</td>';
    }
    $html .='</tr>
   <tr>
		<td colspan="6" class="colspan4">
			<strong>Доставка:</strong>
		</td>';

    $html .='<td><strong>'.$order['delivery_cost'].'  '.$currency.'</strong></td>';
    $totalsumm = $order['summ'] + $order['delivery_cost'];

    $html .= '</tr>
      <tr>
        <td colspan="6" class="colspan4">
          <strong>Всего к оплате:</strong>
        </td>
        <td><strong>'.sprintf('%2.2f', $totalsumm).'  '.$currency.'</strong></td>
      </tr>
      </table>
    <p>Всего наименований '.$i.', на сумму '.$totalsumm.'  '.$currency.'</p>
    ';
    
    include('int2str.php');
    $sumToWord = new int2str($totalsumm);
    $sumToWord->ucfirst($sumToWord->rub);
    $html.='<p><strong style="font-size: 18px;">'.$sumToWord->ucfirst($sumToWord->rub).'</strong></p>    
    <div class="clear">&nbsp;</div>
    </div>';

    $imgSing = '';
    if (file_exists($propertyOrder['sing'])) {
      $imgSing = '<img src="'.SITE.'/'.$propertyOrder['sing'].'">';
    } else {
      if (file_exists('uploads/sing.jpg')) {
        $imgSing = '<img src="'.SITE.'/uploads/sing.jpg">';
      }
    }

    $imgStamp = '';
    if (file_exists($propertyOrder['stamp'])) {
      $imgStamp = '<img src="'.SITE.'/'.$propertyOrder['stamp'].'">';
    } else {
      if (file_exists('uploads/stamp.jpg')) {
        $imgStamp = '<img src="'.SITE.'/uploads/stamp.jpg">';
      }
    }

    if (empty($propertyOrder['usedsing'])) {
      $imgSing = '';
      $imgStamp = '';
    }

    $html .= '
          <br /> 
					<br />           
          <table>
          
          <tr>
            <td width="240"></td>
            <td width="10"></td> 
            <td width="140" align="center">'.$imgSing.'</td>
            <td width="30"></td> 
            <td width="240"></td>
          </tr>
          
          <tr>
            <td width="240">Генеральный директор</td>
            <td width="10"></td>        
            <td width="140"></td>
            <td width="30"></td> 
            <td width="240" align="center">/'.$propertyOrder['general'].'/</td>
          </tr>
          
          <tr>
            <td width="240"></td>
            <td width="10"></td>
            <td width="140"><hr>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;подпись</td>        
            <td width="30"></td> 
            <td width="240"><hr><strong style="font-size: 10px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;расшифровка подписи</strong></td>
          </tr>
          
          <tr>
            <td width="240"></td>
            <td width="10"></td>
            <td width="140"></td>   
            <td width="30"></td> 
            <td width="240"></td>
          </tr>
          
          <tr>
            <td width="240"></td>
            <td width="10"></td>
            <td width="140"></td> 
            <td width="30"></td> 
            <td width="240">М.П.'.$imgStamp.'</td>
          </tr>
          
          <tr>
          </tr>
          
          </table>
       ';

    return $html;
  }

  /**
   * Отдает pdf файл на скачивание.
   * @param $orderId - номер заказа id.
   * @return array 
   */
  public function getPdfOrder($orderId) {
    // Подключаем библиотеку tcpdf.php
    require_once('mg-core/script/tcpdf/tcpdf.php');
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->setImageScale(1.53);
    $pdf->SetFont('freeserif', '', 10);
    $pdf->AddPage();

    $orderInfo = $this->getOrder('id='.DB::quote($orderId, true));

    if (!empty($orderInfo[$orderId]['yur_info'])) {
      $html = $this->printOrder($orderId);
    } else {
      //$html = "Извините, функция сохранения квитанции в PDF на стадии разработки.";  
      $html = $this->printOrder($orderId);
      //$html = $this->printQittance(false);     
    }

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Order '.$orderId.'.pdf', 'D');
    exit;
  }

  /**
   * Выводит на экран печатную форму для печати квитанции на оплату заказа.
   * @param boolean вывод на печать в публичной части, либо в админке.
   * @return array 
   */
  public function printQittance($public = true) {
    MG::disableTemplate();
    $line = "<p class='line'></p>";
    $line2 = "<p class='line2'></p>";
    $name = (!empty($_POST['name'])) ? $_POST['name'] : $line;
    $inn = (!empty($_POST['inn'])) ? $_POST['inn'] : $line;
    $nsp = (!empty($_POST['nsp'])) ? $_POST['nsp'] : $line;
    $ncsp = (!empty($_POST['ncsp'])) ? $_POST['ncsp'] : $line2;
    $bank = (!empty($_POST['bank'])) ? $_POST['bank'] : $line;
    $bik = (!empty($_POST['bik'])) ? $_POST['bik'] : $line2;
    $appointment = (!empty($_POST['appointment'])) ? $_POST['appointment'] : $line;
    $nls = (!empty($_POST['nls'])) ? $_POST['nls'] : $line;
    $payer = (!empty($_POST['payer'])) ? $_POST['payer'] : $line2;
    $addrPayer = (!empty($_POST['addrPayer'])) ? $_POST['addrPayer'] : $line2;
    $sRub = (!empty($_POST['sRub'])) ? $_POST['sRub'] : '_______';
    $sKop = (!empty($_POST['sKop'])) ? $_POST['sKop'] : 0;
    $uRub = (!empty($_POST['uRub'])) ? $_POST['uRub'] : '_______';
    $uKop = (!empty($_POST['uKop'])) ? $_POST['uKop'] : 0;
    $day = (!isset($_POST['day']) || $_POST['day'] == '_') ? '____' : $_POST['day'];
    $month = (!isset($_POST['month']) || $_POST['month'] == '_') ?
      '___________________' : $_POST['month'];

    if (!isset($_POST['sKop'])){
      $sKop = '___';    
    }
    if (!isset($_POST['uKop'])){
      $uKop = '___';    
    }
    $sResult = (!empty($sKop)) ? $sResult = "$sRub.$sKop" : $sRub;
    $uResult = (!empty($uKop)) ? $uResult = "$uRub.$uKop" : $uRub;

    $rubResult = $sResult + $uResult;
    
    if (empty($rubResult)){
      settype($rubResult, 'null');    
    }
    
    if (is_double($rubResult)) {
      list($rub, $kop) = explode('.', $rubResult);
    } else if (is_int($rubResult)) {
      $rub = $rubResult;
      $kop = "0";
    }

    if (empty($rub))
      $rub = '_______';
    if (!isset($kop))
      $kop = '___';
    ob_start();
    ?>

    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Квитанция Сбербанка</title>
        <style type="text/css">
          * {
            padding: 0;
            margin: 0;
          }

          body {
            font-size: 16px;
          }
          .clear { clear: both;}
          #blank{ width: 792px; border: 4px solid #000; margin: 0 auto; }
          .blanks-wrapper { width: 800px;  margin: 0 auto; padding: 20px 0; }
          #control-panel{height:40px;}
          #control-panel a span{display:inline-block;}
          #control-panel a.btn-personal span{padding:4px 10px 4px 27px;background:url(<?php echo SITE ?>/mg-admin/design/images/icons/go-back-icon.png) 6px 4px no-repeat;}
          #control-panel a.btn-print span{padding:4px 10px 4px 27px;background:url(<?php echo SITE ?>/mg-admin/design/images/icons/print-icon.png) 6px 4px no-repeat;}
          #control-panel a{display:block;
                           background: #FCFCFC; /* Old browsers */
                           background: -moz-linear-gradient(top, #FCFCFC 0%, #E8E8E8 100%); /* FF3.6+ */
                           background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#FCFCFC), color-stop(100%,#E8E8E8)); /* Chrome,Safari4+ */
                           background: -webkit-linear-gradient(top, #FCFCFC 0%,#E8E8E8 100%); /* Chrome10+,Safari5.1+ */
                           background: -o-linear-gradient(top, #FCFCFC 0%,#E8E8E8 100%); /* Opera11.10+ */
                           background: -ms-linear-gradient(top, #FCFCFC 0%,#E8E8E8 100%); /* IE10+ */
                           filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#FCFCFC', endColorstr='#E8E8E8',GradientType=0 ); /* IE6-9 */
                           background: linear-gradient(top, #FCFCFC 0%,#E8E8E8 100%); /* W3C */
                           border: 1px solid #D3D3D3;
                           font-family: Tahoma, Verdana, sans-serif;
                           font-size:14px;
                           border-radius: 5px;
                           -moz-border-radius: 5px;
                           -webkit-border-radius: 5px;
                           color:#333;
                           text-decoration:none;
          }
          #control-panel a:hover{
            background: #eeeeee; /* Old browsers */
            background: -moz-linear-gradient(top, #eeeeee 0%, #eeeeee 100%); /* FF3.6+ */
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eeeeee), color-stop(100%,#eeeeee)); /* Chrome,Safari4+ */
            background: -webkit-linear-gradient(top, #eeeeee 0%,#eeeeee 100%); /* Chrome10+,Safari5.1+ */
            background: -o-linear-gradient(top, #eeeeee 0%,#eeeeee 100%); /* Opera11.10+ */
            background: -ms-linear-gradient(top, #eeeeee 0%,#eeeeee 100%); /* IE10+ */
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#eeeeee', endColorstr='#eeeeee',GradientType=0 ); /* IE6-9 */
            background: linear-gradient(top, #eeeeee 0%,#eeeeee 100%);
          }
          #control-panel a:active{
            -moz-box-shadow: 0 0 4px 2px rgba(0,0,0,.3) inset;
            -webkit-box-shadow: 0 0 4px 2px rgba(0,0,0,.3) inset;
            box-shadow: 0 0 4px 2px #CFCFCF inset;
            outline:none;
          }
          #control-panel .btn-print { float: left; }
          #control-panel .btn-personal { float: right; }
          #top { border-bottom: 4px solid #000; }
          #top, #bottom { overflow: hidden; }
          #left01, #left02 { float: left; width: 185px; }
          #right01, #right02 { width: 600px; float: right; border-left: 4px solid #000; }
          #right01 dt, #right02 dt { text-align: center; }
          #right01 dd, #right02 dd { text-align: center; font-size: 13px; }
          .line { border-bottom: 1px solid #000;  padding: 25px 0 0 0; }
          .line2 { border-bottom: 1px solid #000; padding: 15px 0 0 0; }
          .inn { float: left; margin: 0 30px 0 0; width: 250px; }
          .nsp { float: right; width: 320px; }
          .bank { float: left; margin: 0 25px 0 0; width: 325px; }
          .bik dt, .ncsp dt, .payer dt, .addrPayer dt { float: left; padding: 10px 0 0 5px; }
          .bik dd, .ncsp dd, .payer dd, .addrPayer dd { float: right; width: 200px; font-size: 16px !important; padding: 10px 0 0 0; text-align: left !important; }
          .ncsp dd { width: 250px; margin: 0 0 7px 0; }
          .payer dd { width: 430px; }
          .addrPayer dd { width: 430px; }
          .appointment { float: left; margin: 0 30px 0 0; width: 320px; }
          .nls { float: right; width: 250px; }
          .sRub { float: left; width: 290px; }
          .uRub {float: right; width: 310px; }
          .result { float: left; width: 290px; margin: 10px 0 0 0;}
          .date { float: left; margin: 10px 0 0 0;}
          .terms { margin: 10px 0 40px 5px; }

          @media print {
            .no-print {
              display:none;
            }
          }
        </style>
      <head>
      <body>
        <div class="blanks-wrapper">
          <div id="control-panel" class="no-print">
            <a href="javascript:vodi(0);" onclick="window.print();" class="no-print btn-print"><span>Распечатать</span></a>
            <a href="<?php echo SITE ?>/personal" class="no-print btn-personal"><span>Вернуться в личный кабинет</span></a>
          </div>  
          <div id="blank">
            <div id="top">
              <div id="left01">
                <p style="text-align: center;">
                  <strong>Извещание</strong>
                  <strong style="display: block; margin-top: 150%;">Кассир</strong>
                </p>
              </div>
              <div id="right01">
                <dl>
                  <dt><?php echo $name ?></dt>
                  <dd>(наименование получателя)</dd>
                </dl>

                <dl class="inn">
                  <dt><?php echo $inn; ?></dt>
                  <dd>(ИНН получателя платежа)</dd>
                </dl>

                <dl class="nsp">
                  <dt><?php echo $nsp; ?></dt>
                  <dd>(номер счета получателя платежа)</dd>
                </dl>

                <dl class="bank">
                  <dt><?php echo $bank; ?></dt>
                  <dd>(наименование банка получателя)</dd>
                </dl>

                <dl class="bik">
                  <dt>БИК</dt>
                  <dd><?php echo $bik; ?></dd>
                </dl>
                <div class="clear"></div>

                <dl class="ncsp">
                  <dt>Номер кор./сч банка получателя платежа</dt>
                  <dd><?php echo $ncsp; ?></dd>
                </dl>
                <div class="clear"></div>

                <dl class="appointment">
                  <dt><?php echo $appointment; ?></dt>
                  <dd>(наименование платежа)</dd>
                </dl>

                <dl class="nls">
                  <dt><?php echo $nls; ?></dt>
                  <dd>(номер лицевого счета (код) плательщика)</dd>
                </dl>

                <dl class="payer">
                  <dt>Ф.И.О. плательщика:</dt>
                  <dd><?php echo $payer; ?></dd>
                </dl>

                <div class="clear"></div>
                <dl class="addrPayer">
                  <dt>Адрес плательщика:</dt>
                  <dd><?php echo $addrPayer; ?></dd>
                </dl>

                <div class="clear"></div>
                <?php echo $currency = MG::getSetting('currency'); ?>
                <div class="sRub">
                  <p>Сумма платежа: <?php echo $sRub; ?> <?php echo $currency; ?> <?php echo $sKop; ?> коп.</p>
                </div>
                <div class="sKop">
                  <p>Сумма платы за услуги <?php echo $uRub; ?> <?php echo $currency; ?> <?php echo $uKop ?> коп.</p>
                </div>
                <div class="result">
                  <p>Итого: <?php echo $rub; ?> <?php echo $currency; ?> <?php echo $kop; ?> коп.</p>
                </div>

                <div class="date"><?php echo $day; ?>.<?php echo $month; ?>.<?php echo date('Y'); ?> г.</div>
                <div class="clear"></div>
                <p class="terms">
                  С условиями приема указанной в платежном документе суммы, в т.ч. с суммой взимаемой платы
                  банка ознакомлен и согласен.
                </p>
              </div>
              <div class="clear"></div>
              <div id="bottom" style="border-top: 4px solid #000">
                <div id="left02">
                  <p style="text-align: center; margin-top: 150%;">
                    <strong>Квитанция</strong><br>
                    <strong>Кассир</strong>
                  </p>
                </div>
                <div id="right02">
                  <dl>
                    <dt><?php echo $name ?></dt>
                    <dd>(наименование получателя)</dd>
                  </dl>

                  <dl class="inn">
                    <dt><?php echo $inn; ?></dt>
                    <dd>(ИНН получателя платежа)</dd>
                  </dl>

                  <dl class="nsp">
                    <dt><?php echo $nsp; ?></dt>
                    <dd>(номер счета получателя платежа)</dd>
                  </dl>

                  <dl class="bank">
                    <dt><?php echo $bank; ?></dt>
                    <dd>(наименование банка получателя)</dd>
                  </dl>

                  <dl class="bik">
                    <dt>БИК</dt>
                    <dd><?php echo $bik; ?></dd>
                  </dl>
                  <div class="clear"></div>

                  <dl class="ncsp">
                    <dt>Номер кор./сч банка получателя платежа</dt>
                    <dd><?php echo $ncsp; ?></dd>
                  </dl>
                  <div class="clear"></div>

                  <dl class="appointment">
                    <dt><?php echo $appointment; ?></dt>
                    <dd>(наименование платежа)</dd>
                  </dl>

                  <dl class="nls">
                    <dt><?php echo $nls; ?></dt>
                    <dd>(номер лицевого счета (код) плательщика)</dd>
                  </dl>

                  <dl class="payer">
                    <dt>Ф.И.О. плательщика:</dt>
                    <dd><?php echo $payer; ?></dd>
                  </dl>

                  <div class="clear"></div>
                  <dl class="addrPayer">
                    <dt>Адрес плательщика:</dt>
                    <dd><?php echo $addrPayer; ?></dd>
                  </dl>

                  <div class="clear"></div>
                  <div class="sRub">
                    <p>Сумма платежа: <?php echo $sRub; ?> <?php echo $currency; ?> <?php echo $sKop; ?> коп.</p>
                  </div>
                  <div class="sKop">
                    <p>Сумма платы за услуги <?php echo $uRub; ?> <?php echo $currency; ?> <?php echo $uKop ?> коп.</p>
                  </div>
                  <div class="result">
                    <p>Итого: <?php echo $rub; ?> <?php echo $currency; ?> <?php echo $kop; ?> коп.</p>
                  </div>

                  <div class="date"><?php echo $day; ?>.<?php echo $month; ?>.<?php echo date('Y'); ?> г.</div>
                  <div class="clear"></div>
                  <p class="terms">
                    С условиями приема указанной в платежном документе суммы, в т.ч. с суммой взимаемой платы
                    банка ознакомлен и согласен.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </body>
    </html><?php
    if ($public) {
      exit;
    }

    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }

  /**
   * Экспортирует параметры конкретного заказа в CSV файл.
   * @param $orderId - id заказа.
   * @return array
   */
  public function getExportCSV($orderId) {

    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream;");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=data.csv");
    header("Content-Transfer-Encoding: binary ");

    $orderInfo = $this->getOrder('id='.DB::quote($orderId, true));
    $order = $orderInfo[$orderId];

    foreach ($order as $key => $value) {
      $order[$key] = '"'.str_replace("\"", "\"\"", $order[$key]).'"';
      $csvText .= $order[$key].';';
    }

    echo mb_convert_encoding($csvText, "WINDOWS-1251", "UTF-8");
    exit;
  }

}