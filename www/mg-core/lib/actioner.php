<?php

/**
 * Класс Actioner - предназначен для обработки административных действий, 
 * совершаемых из панели управления сайтом, таких как добавление и удалени товаров, 
 * категорий, и др. сущностей.
 * 
 * Методы класса являются контролерами между AJAX запросами и логикой моделей движка, возвращают в конечном результате строку в JSON формате.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Actioner {

  /**
   * @var string сообщение об успешнон результате выполнения операции. 
   */
  public $messageSucces;

  /**
   * @var string сообщение о неудачном результате выполнения операции. 
   */
  public $messageError;

  /**
   * @var mixed массив с данными возвращаемый в ответ на AJAX запрос. 
   */
  public $data = array();

  /**
   * @var mixed язык локали движка. 
   */
  public $lang = array();

  /**
   * @var string префикс таблиц в базе сайта. 
   */
  public $prefix;

  /**
   * Конструктор инициализирует поля клааса.
   * @param boolean $lang - массив дополняющий локаль движка. Используется для работы плагинов.
   */
  public function __construct($lang = false) {
    $this->messageSucces = 'Succes';
    $this->messageError = 'Error';

    $langMerge = array();
    if (!empty($lang)) {
      $langMerge = $lang;
    }// если $lang не пустой, значит он передан для работы в наследнике данного класса, например для обработки аяксовых запросов плагина
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');

    $lang = array_merge($lang, $langMerge);

    $this->lang = $lang;
    $this->prefix = PREFIX;
  }

  /**
   * Запускает один из методов данного класса.
   * @param type $action - название метода который нужно вызвать.
   */
  public function runAction($action) {
    unset($_POST['mguniqueurl']);
    unset($_POST['mguniquetype']);
    //отсекаем все что после  знака ?
    $action = preg_replace("/\?.*/s", "", $action);

    $this->jsonResponse($this->$action());
    exit;
  }


  /**
   * Добавляет продукт в базу.
   * @return boolean
   */
  public function addProduct() {
    $model = new Models_Product;
    $this->data = $model->addProduct($_POST);
    $this->messageSucces = $this->lang['ACT_CREAT_PROD'].' "'.$_POST['name'].'"';
    $this->messageError = $this->lang['ACT_NOT_CREAT_PROD'];
    return true;
  }

  /**
   * Клонирует  продукт.
   * @return boolean
   */
  public function cloneProduct() {
    $model = new Models_Product;
    $this->data = $model->cloneProduct($_POST['id']);
    $this->messageSucces = $this->lang['ACT_CLONE_PROD'];
    $this->messageError = $this->lang['ACT_NOT_CLONE_PROD'];
    return true;
  }

  /**
   * Активирует плагин.
   * @return boolean
   */
  public function activatePlugin() {
    $this->messageSucces = $this->lang['ACTIVE_PLUG'].' "'.$_POST['pluginTitle'].'"';
    $pluginFolder = $_POST['pluginFolder'];
    $res = DB::query("
      SELECT *
      FROM  `".PREFIX."plugins`
      WHERE folderName = '%s'
      ", $pluginFolder);

    if (!DB::numRows($res)) {
      $result = DB::query("
        INSERT INTO `".PREFIX."plugins`
        VALUES ('%s', '1')"
          , $pluginFolder);

      MG::createActivationHook($pluginFolder);
      $this->data['havePage'] = PM::isHookInReg($pluginFolder);
      return true;
    }

    if ($result = DB::query("
      UPDATE `".PREFIX."plugins`
      SET active = '1'
      WHERE `folderName` = '%s'
      ", $pluginFolder
      )) {
      MG::createActivationHook($pluginFolder);
      $this->data['havePage'] = PM::isHookInReg($pluginFolder);
      $this->data['newInformer'] = MG::createInformerPanel();
      return true;
    }

    return false;
  }

  /**
   * Деактивирует плагин.
   * @return boolean
   */
  public function deactivatePlugin() {
    $this->messageSucces = $this->lang['ACT_NOT_ACTIVE_PLUG'].' "'.$_POST['pluginTitle'].'"';
    $pluginFolder = $_POST['pluginFolder'];
    $res = DB::query("
      SELECT *
      FROM  `".PREFIX."plugins`
      WHERE folderName = '%s'
      ", $pluginFolder);

    if (DB::numRows($res)) {
      DB::query("
        UPDATE `".PREFIX."plugins`
        SET active = '0'
        WHERE `folderName` = '%s'
      ", $pluginFolder
      );

      MG::createDeactivationHook($pluginFolder);
      return true;
    }

    return false;
  }

  /**
   * Удаляет инсталятор.
   * @return void
   */
  public function delInstal() {
    $installDir = SITE_DIR.URL::getCutPath().'/install/';
    $this->removeDir($installDir);
    MG::redirect('');
  }

  /**
   * Удаляет папку со всем ее содержимым.
   * @param string $path путь к удаляемой папке.
   * @return void
   */
  public function removeDir($path) {
    if (file_exists($path) && is_dir($path)) {
      $dirHandle = opendir($path);

      while (false !== ($file = readdir($dirHandle))) {

        if ($file != '.' && $file != '..') {// Исключаем папки с назварием '.' и '..'
          $tmpPath = $path.'/'.$file;
          chmod($tmpPath, 0777);

          if (is_dir($tmpPath)) {  // Если папка.
            $this->removeDir($tmpPath);
          } else {

            if (file_exists($tmpPath)) {
              // Удаляем файл.
              unlink($tmpPath);
            }
          }
        }
      }
      closedir($dirHandle);

      // Удаляем текущую папку.
      if (file_exists($path)) {
        rmdir($path);
        return true;
      }
    }
  }

  /**
   * Добавляет картинку для использования в визуальном редакторе.
   * @return boolean
   */
  public function upload() {
    new Upload();
  }

  /**
   * Подключает elfinder.
   * @return boolean
   */
  public function elfinder() {

    include('mg-core/script/elfinder/php/connector.php');
  }

  /**
   * Добавляет водяной знак.
   * @return boolean
   */
  public function updateWaterMark() {

    $uploader = new Upload(false);

    $tempData = $uploader->addImage(false, true);
    $this->data = array('img' => $tempData['actualImageName']);

    if ($tempData['status'] == 'success') {
      $this->messageSucces = $tempData['msg'];
      return true;
    } else {
      $this->messageError = $tempData['msg'];
      return false;
    }
  }

  /**
   * Обрабатывает запрос на установку плагина.
   * @return boolean
   */
  public function addNewPlugin() {

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {
      $file_array = $_FILES['addPlugin'];
      $downloadResult = PM::downloadPlugin($file_array);

      if ($downloadResult['data']) {
        $this->messageSucces = $downloadResult['msg'];
        PM::extractPluginZip($downloadResult['data']);
        return true;
      } else {
        $this->messageError = $downloadResult['msg'];
      }
    }
    return false;
  }

  /**
   * Обрабатывает запрос на удаление плагина.
   * @return boolean
   */
  public function deletePlugin() {
    $this->messageSucces = $this->lang['ACT_PLUGIN_DEL'].$_POST['id'];
    $this->messageError = $this->lang['ACT_PLUGIN_DEL_ERR'];

    // удаление картинки с сервера.
    $documentroot = str_replace('mg-core/lib', '', dirname(__FILE__));
    if (PM::deletePlagin($_POST['id']) && $this->removeDir($documentroot.'mg-plugins/'.$_POST['id']
      )
    ) {
      return true;
    }
    return false;
  }

  /**
   * Добавляет картинку товара.
   * @return boolean
   */
  public function addImage() {
    $uploader = new Upload(false);

    $tempData = $uploader->addImage(true);
    $this->data = array('img' => $tempData['actualImageName']);
    if ($tempData['status'] == 'success') {
      $this->messageSucces = $tempData['msg'];
      return true;
    } else {
      $this->messageError = $tempData['msg'];
      return false;
    }
  }

  /**
   * Добавляет картинку без водяного знака.
   * @return boolean
   */
  public function addImageNoWaterMark() {
    $uploader = new Upload(false);
    $_POST['noWaterMark'] = true;
    $tempData = $uploader->addImage(true);
    $this->data = array('img' => $tempData['actualImageName']);
    if ($tempData['status'] == 'success') {
      $this->messageSucces = $tempData['msg'];
      return true;
    } else {
      $this->messageError = $tempData['msg'];
      return false;
    }
  }

  /**
   * Удаляет категорию.
   * @return type
   */
  public function deleteCategory() {
    $this->messageSucces = $this->lang['ACT_DEL_CAT'];
    $this->messageError = $this->lang['ACT_NOT_DEL_CAT'];
    return MG::get('category')->delCategory($_POST['id']);
  }

  /**
   * Удаляет страницу.
   * @return type
   */
  public function deletePage() {
    $this->messageSucces = $this->lang['ACT_DEL_PAGE'];
    $this->messageError = $this->lang['ACT_NOT_DEL_PAGE'];
    return MG::get('pages')->delPage($_POST['id']);
  }

  /**
   * Удаляет пользователя.
   * @return type
   */
  public function deleteUser() {
    $this->messageSucces = $this->lang['ACT_DEL_USER'];
    $this->messageError = $this->lang['ACT_NOT_DEL_USER'];
    return USER::delete($_POST['id']);
  }

  /**
   * Удаляет товар.
   * @return type
   */
  public function deleteProduct() {
    $this->messageSucces = $this->lang['ACT_DEL_PROD'];
    $this->messageError = $this->lang['ACT_NOT_DEL_PROD'];
    $model = new Models_Product;
    return $model->deleteProduct($_POST['id']);
  }

  /**
   * Удаляет заказ.
   * @return type
   */
  public function deleteOrder() {

    $this->messageSucces = $this->lang['ACT_DEL_ORDER'];
    $this->messageError = $this->lang['ACT_NOT_DEL_ORDER'];
    $model = new Models_Order;
    $this->data = array('count' => $model->getNewOrdersCount());
    return $model->deleteOrder($_POST['id']);
  }

  /**
   * Удаляет пользовательскую характеристику товара.
   * @return type
   */
  public function deleteUserProperty() {

    $this->messageSucces = $this->lang['ACT_DEL_PROP'];
    $this->messageError = $this->lang['ACT_NOT_DEL_PROP'];
    $result = false;
    if (DB::query('
      DELETE
      FROM `'.PREFIX.'property`
      WHERE id = %d', $_POST['id']) &&
      DB::query('
      DELETE
      FROM `'.PREFIX.'product_user_property`
      WHERE property_id = %d', $_POST['id'])
    ) {
      $result = true;
    }
    return $result;
  }

  /**
   * Удаляет категорию.
   * @return boolean
   */
  public function editCategory() {
    $this->messageSucces = $this->lang['ACT_EDIT_CAT'].' "'.$_POST['title'].'"';
    $this->messageError = $this->lang['ACT_NOT_EDIT_CAT'];

    $id = $_POST['id'];
    unset($_POST['id']);
    // Если назначаемая категория, является тойже.
    if ($_POST['parent'] == $id) {
      $this->messageError = $this->lang['ACT_ERR_EDIT_CAT'];
      return false;
    }

    $childsCaterory = MG::get('category')->getCategoryList($id);
    // Если есть вложенные, и одна из них назначена родительской.
    if (!empty($childsCaterory)) {
      foreach ($childsCaterory as $cateroryId) {
        if ($_POST['parent'] == $cateroryId) {
          $this->messageError = $this->lang['ACT_ERR_EDIT_CAT'];
          return false;
        }
      }
    }

    if ($_POST['parent'] == $id) {
      $this->messageError = $this->lang['ACT_ERR_EDIT_CAT'];
      return false;
    }
    return MG::get('category')->editCategory($id, $_POST);
  }

  /**
   * Сохраняет и обновляет параметры товара.
   * @return type
   */
  public function saveProduct() {
    $this->messageSucces = $this->lang['ACT_SAVE_PROD'];
    $this->messageError = $this->lang['ACT_NOT_SAVE_PROD'];

    //Перед сохранением удалим все помеченные  картинки продукта физически с диска.    
    $images = explode("|", $_POST['image_url']);
    $model = new Models_Product;
    if (!is_numeric($_POST['count'])) {
      $_POST['count'] = "-1";
    }

    //Обновление
    if (!empty($_POST['id'])) {
      $_POST['updateFromModal'] = true; // флаг, чтобы отличить откуда было обновление  товара
      $model->updateProduct($_POST);
      $_POST['image_url'] = $images[0];
      $_POST['currency'] = MG::getSetting('currency');
      $_POST['recommend'] = $_POST['recommend'];
      $tempProd = $model->getProduct($_POST['id']);
      $_POST['category_url'] = $tempProd['category_url'];
      $_POST['product_url'] = $tempProd['product_url'];
      $this->data = $_POST;
    } else {  // добавление
      unset($_POST['delete_image']);
      $this->data = $model->addProduct($_POST);
      $this->data['image_url'] = $images[0];
      $this->data['currency'] = MG::getSetting('currency');
      $this->data['recommend'] = $_POST['recommend'];
    }
    return true;
  }

  /**
   * Обновляет параметры товара (быстрый вариант).
   * @return type
   */
  public function fastSaveProduct() {
    $this->messageSucces = $this->lang['ACT_SAVE_PROD'];
    $this->messageError = $this->lang['ACT_NOT_SAVE_PROD'];

    $model = new Models_Product;
    $variant = $_POST['variant'];
    unset($_POST['variant']);

    $arr = array(
      $_POST['field'] => $_POST['value']
    );

    // Обновление.
    if ($variant) {
      return $model->fastUpdateProductVariant($_POST['id'], $arr, $_POST['product_id']);
    } else {
      return $model->fastUpdateProduct($_POST['id'], $arr);
    }
  }

  /**
   * Перезаписывает новым значением, любое поле в любой таблице, в зависимости от входящих параметров.
   */
  public function fastSaveContent() {
    if (!DB::query('
       UPDATE `'.DB::quote($_POST['table'], true).'`
       SET `'.DB::quote($_POST['field'], true).'` = '.DB::quote($_POST['content']).'
       WHERE id = '.DB::quote($_POST['id'], true))) {
      return false;
    }
    return true;
  }

  /**
   * Устанавливает флаг для вывода продукта в блоке рекоммендуемых товаров.
   * @return type
   */
  public function recomendProduct() {
    $this->messageSucces = $this->lang['ACT_PRINT_RECOMEND'];
    $this->messageError = $this->lang['ACT_NOT_PRINT_RECOMEND'];

    $model = new Models_Product;
    // Обновление.
    if (!empty($_POST['id'])) {
      $model->updateProduct($_POST);
    }

    if ($_POST['recommend']) {
      return true;
    }

    return false;
  }

  /**
   * Устанавливает флаг  активности продукта 
   * @return type
   */
  public function visibleProduct() {
    $this->messageSucces = $this->lang['ACT_V_PROD'];
    $this->messageError = $this->lang['ACT_UNV_PROD'];

    $model = new Models_Product;
    // Обновление.
    if (!empty($_POST['id'])) {
      $model->updateProduct($_POST);
    }

    if ($_POST['activity']) {
      return true;
    }

    return false;
  }

  /**
   * Устанавливает флаг для вывода продукта в блоке новых товаров.
   * @return type
   */
  public function newProduct() {
    $this->messageSucces = $this->lang['ACT_PRINT_NEW'];
    $this->messageError = $this->lang['ACT_NOT_PRINT_NEW'];

    $model = new Models_Product;
    // Обновление.
    if (!empty($_POST['id'])) {
      $model->updateProduct($_POST);
    }

    if ($_POST['new']) {
      return true;
    }

    return false;
  }

  /**
   * Устанавливает флаг для выбранной страницы, чтобы выводить ее в главном меню.
   * @return type
   */
  public function printMainMenu() {
    $this->messageSucces = $this->lang['ADD_IN_MENU'];
    $this->messageError = $this->lang['NOT_ADD_IN_MENU'];

    $model = new Models_Page;
    // Обновление.
    if (!empty($_POST['id'])) {
      $model->updatePage($_POST);
    }

    if ($_POST['print_in_menu']) {
      return true;
    }

    return false;
  }

  /**
   * Печать заказа.
   */
  public function printOrder() {
    $this->messageSucces = $this->lang['ACT_PRINT_ORD'];
    $model = new Models_Order;
    $this->data = array('html' => $model->printOrder($_POST['id']));
    return true;
  }

  /**
   * Получает данные по промокоду.
   */
  public function getPromoCode() {
    $this->messageSucces = 'Промокод применен';
    // Заменить на получение скидки.
    $codes = array();
    // Запрос для проверки , существуют ли промокоды.  
    $result = DB::query('SHOW TABLES');
    while ($row = DB::fetchArray($result)) {
      if (PREFIX.'promo-code' == $row[0]) {
        $res = DB::query('SELECT code, percent FROM `'.PREFIX.'promo-code` WHERE invisible = 1');
        while ($code = DB::fetchAssoc($res)) {
          $codes[$code['code']] = $code['percent'];
        }
      };
    }
    $percent = $codes[$_POST['promocode']];
    $this->data = array('percent' => $percent, 'codes' => array('DEFAULT-DISCONT', 'DEFAULT-DISCONT2'));
    return true;
  }

  /**
   * Получает данные по вводимому email в форме заказа.
   * @return boolean
   */
  public function getUserEmail() {
    $emails = array('mark-avdeev@mail.ru', 'mark-avdeev2@mail.ru');
    $this->data = $emails;
    return true;
  }

  /**
   * Сохраняет и обновляет параметры заказа.
   * @return type
   */
  public function saveOrder() {
    $this->messageSucces = $this->lang['ACT_SAVE_ORD'];
    $this->messageError = $this->lang['ACT_SAVE_ORDER'];

    // Cобираем воедино все параметры от юр. лица если они были переданы, для записи в информацию о заказе.
    $_POST['yur_info'] = '';
    if (!empty($_POST['inn'])) {
      $_POST['yur_info'] = array(
        'email' => $_POST['orderEmail'],
        'name' => $_POST['orderBuyer'],
        'address' => $_POST['orderAddress'],
        'phone' => $_POST['orderPhone'],
        'inn' => $_POST['inn'],
        'kpp' => $_POST['kpp'],
        'nameyur' => $_POST['nameyur'],
        'adress' => $_POST['adress'],
        'bank' => $_POST['bank'],
        'bik' => $_POST['bik'],
        'ks' => $_POST['ks'],
        'rs' => $_POST['rs'],
      );
    }

    $model = new Models_Order;

    // Обновление.
    if (!empty($_POST['id'])) {
      unset($_POST['inn']);
      unset($_POST['kpp']);
      unset($_POST['nameyur']);
      unset($_POST['adress']);
      unset($_POST['bank']);
      unset($_POST['bik']);
      unset($_POST['ks']);
      unset($_POST['rs']);
      unset($_POST['ogrn']);

      if (!empty($_POST['yur_info'])) {
        $_POST['yur_info'] = addslashes(serialize($_POST['yur_info']));
      }

      $_POST['order_content'] = addslashes(serialize($_POST['order_content']));
      $model->updateOrder($_POST);
    } else {
      if ($user = USER::getUserInfoByEmail($_POST['orderEmail'])) {
        // $_POST['orderBuyer'] = $user->name;
      } else {
        $newUserData = array(
          'email' => $_POST['orderEmail'],
          'role' => 2,
          'name' => $_POST['orderBuyer'],
          'pass' => crypt(time()),
          'address' => $_POST['orderAddress'],
          'phone' => $_POST['orderPhone'],
          'inn' => $_POST['inn'],
          'kpp' => $_POST['kpp'],
          'nameyur' => $_POST['nameyur'],
          'adress' => $_POST['adress'],
          'bank' => $_POST['bank'],
          'bik' => $_POST['bik'],
          'ks' => $_POST['ks'],
          'rs' => $_POST['rs'],
        );

        USER::add($newUserData);
      }

      $id = $model->addOrder($_POST);
      $this->messageSucces = $this->lang['ACT_SAVE_ORD'].' № '.$id;
      $_POST['id'] = $id;
      $_POST['newId'] = $id;
    }

    $_POST['count'] = $model->getNewOrdersCount();
    $_POST['date'] = date('d.m.Y H:i');
    $this->data = $_POST;
    return true;
  }

  /**
   * Сохраняет и обновляет параметры категории.
   * @return type
   */
  public function saveCategory() {
    $this->messageSucces = $this->lang['ACT_SAVE_CAT'];
    $this->messageError = $this->lang['ACT_NOT_SAVE_CAT'];
    $_POST['parent_url'] = MG::get('category')->getParentUrl($_POST['parent']);
    // Обновление.
    if (!empty($_POST['id'])) {
      if (MG::get('category')->updateCategory($_POST)) {
        $this->data = $_POST;
      } else {
        return false;
      }
    } else {  // добавление
      $this->data = MG::get('category')->addCategory($_POST);
    }
    return true;
  }

  /**
   * Сохраняет и обновляет параметры страницы.
   * @return type
   */
  public function savePage() {
    $this->messageSucces = $this->lang['ACT_SAVE_PAGE'];
    $this->messageError = $this->lang['ACT_NOT_SAVE_PAGE'];

    $_POST['parent_url'] = MG::get('pages')->getParentUrl($_POST['parent']);
    // Обновление.
    if (!empty($_POST['id'])) {
      if (MG::get('pages')->updatePage($_POST)) {
        $this->data = $_POST;
      } else {
        return false;
      }
    } else {  // добавление
      $this->data = MG::get('pages')->addPage($_POST);
    }
    return true;
  }

  /**
   * Делает страницу невидимой в меню.
   * @return type
   */
  public function invisiblePage() {

    $this->messageError = $this->lang['ACT_NOT_SAVE_PAGE'];
    if ($_POST['invisible'] === "1") {
      $this->messageSucces = $this->lang['ACT_UNV_PAGE'];
    } else {
      $this->messageSucces = $this->lang['ACT_V_PAGE'];
    }
    // Обновление.
    if (!empty($_POST['id']) && isset($_POST['invisible'])) {
      MG::get('pages')->updatePage($_POST);
    } else {
      return false;
    }
    return true;
  }

  /**
   * Делает категорию невидимой в меню.
   * @return type
   */
  public function invisibleCat() {

    $this->messageError = $this->lang['ACT_NOT_SAVE_CAT'];
    if ($_POST['invisible'] === "1") {
      $this->messageSucces = $this->lang['ACT_UNV_CAT'];
    } else {
      $this->messageSucces = $this->lang['ACT_V_CAT'];
    }
    // Обновление.
    if (!empty($_POST['id']) && isset($_POST['invisible'])) {
      MG::get('category')->updateCategory($_POST);
    } else {
      return false;
    }
    return true;
  }

  /**
   * Делает все категории видимыми в меню.
   * @return type
   */
  public function refreshVisibleCat() {
    MG::get('category')->refreshVisibleCat();
    $this->messageSucces = $this->lang['ACT_PINT_IN_MENU'];
    return true;
  }

  /**
   * Делает все страницы видимыми в меню.
   * @return type
   */
  public function refreshVisiblePage() {
    MG::get('pages')->refreshVisiblePage();
    $this->messageSucces = $this->lang['ACT_PINT_IN_MENU'];
    return true;
  }

  /**
   * Сохраняет и обновляет параметры пользователя.
   * @return type
   */
  public function saveUser() {
    $this->messageSucces = $this->lang['ACT_SAVE_USER'];
    $this->messageError = $this->lang['ACT_NOT_SAVE_USER'];

    // Обновление.
    if (!empty($_POST['id'])) {

      // если пароль не передан значит не обновляем его
      if (empty($_POST['pass'])) {
        unset($_POST['pass']);
      } else {
        $_POST['pass'] = crypt($_POST['pass']);
      }

      //вычисляем надо ли перезаписать данные текущего пользователя после обновления
      //(только в том случае если из админки меняется запись текущего пользователя)
      $authRewrite = $_POST['id'] != User::getThis()->id ? true : false;

      if (User::update($_POST['id'], $_POST, $authRewrite)) {
        $this->data = $_POST;
      } else {
        return false;
      }
    } else {  // добавление
      try {
        $_POST['id'] = User::add($_POST);
      } catch (Exception $exc) {
        $this->messageError = $this->lang['ACT_ERR_SAVE_USER'];
        return false;
      }
      //отправка письма с онформацией о рагистрации
      $siteName = MG::getSetting('sitename');
      $userEmail = $_POST['email'];
      $message = '
        Здравствуйте!<br>
          Вы получили данное письмо так как на сайте '.$siteName.' зарегистрирован новый пользователь с логином '.$userEmail.'.<br>
          Отвечать на данное сообщение не нужно.';
      $emailData = array(
        'nameFrom' => $siteName,
        'emailFrom' => MG::getSetting('noReplyEmail'),
        'nameTo' => 'Пользователю сайта '.$siteName,
        'emailTo' => $userEmail,
        'subject' => 'Активация пользователя на сайте '.$siteName,
        'body' => $message,
        'html' => true
      );
      Mailer::sendMimeMail($emailData);

      $_POST['date_add'] = date('d.m.Y H:i');
      $this->data = $_POST;
    }
    return true;
  }

  /**
   * Изменяет настройки.
   * @return boolean
   */
  public function editSettings() {
    $this->messageSucces = $this->lang['ACT_SAVE_SETNG'];
    if (!empty($_POST['options'])) {
      foreach ($_POST['options'] as $option => $value) {
        if (!DB::query("UPDATE `".PREFIX."setting` SET `value`='%s' Where `option`='%s'", $value, $option)) {
          return false;
        }
      }

      return true;
    }
  }
  
  /**
   * Получает параметры редактируемого продукта.
   */
  public function getProductData() {

    $this->messageError = $this->lang['ACT_NOT_GET_POD'];

    $model = new Models_Product;
    $product = $model->getProduct($_POST['id']);

    if (empty($product)) {
      return false;
    }
    $this->data = $product;

    // Получаем весь набор пользовательских характеристик.
    $res = DB::query("SELECT * FROM `".PREFIX."property`");
    while ($userFields = DB::fetchAssoc($res)) {
      $this->data['allProperty'][] = $userFields;
    }

    $variants = $model->getVariants($_POST['id']);
    foreach ($variants as $variant) {
      $this->data['variants'][] = $variant;
    }
   
    return true;
  }

  /**
   * Получает параметры для категории продуктов.
   */
  public function getProdDataWithCat() {
    $this->data['allProperty'] = array();
    $this->data['thisUserFields'] = array();

    // Получаем заданные ранее пользовательские характеристики для редактируемого товара.
    $res = DB::query("
        SELECT pup.property_id, pup.value, pup.product_margin, pup.type_view, prop.*
        FROM `".PREFIX."product_user_property` as pup
        LEFT JOIN `".PREFIX."property` as prop ON pup.property_id = prop.id
        WHERE pup.`product_id` = ".DB::quote($_POST['produtcId']));

    while ($userFields = DB::fetchAssoc($res)) {
      $this->data['thisUserFields'][] = $userFields;
    }

    // Получаем набор пользовательских характеристик предназначенных для выбраной категории.
    $res = DB::query("
        SELECT *
        FROM `".PREFIX."category_user_property` as сup
        LEFT JOIN `".PREFIX."property` as prop ON сup.property_id = prop.id
        WHERE сup.`category_id` = ".DB::quote($_POST['categoryId']));
    $alreadyProp = array();
    while ($userFields = DB::fetchAssoc($res)) {
      $this->data['allProperty'][] = $userFields;
      $alreadyProp[$userFields['property_id']] = true;
    }

    // Получаем набор пользовательских характеристик.
    // Предназначенных для всех категорий и приплюсовываем его к уже имеющимя характеристикам выбраной категории.
    $res = DB::query("SELECT * FROM `".PREFIX."property` WHERE all_category = 1");
    while ($userFields = DB::fetchAssoc($res)) {
      if (empty($alreadyProp[$userFields['id']])) {
        $this->data['allProperty'][] = $userFields;
        $alreadyProp[$userFields['id']];
      }
    }

    return true;
  }

  /**
   * Получает пользовательские поля для добавления нового продукта.
   */
  public function getUserProperty() {
    $res = DB::query("SELECT * FROM `".PREFIX."property`");
    while ($userFields = DB::fetchAssoc($res)) {
      $this->data['allProperty'][] = $userFields;
    }
    return true;
  }

  /**
   * Получает привязку пользовательского свойства к набору категорий.
   */
  public function getConnectionCat() {
    $id = $_POST['id'];
    $categoryIds = array();
    // Получчаем список выбраных категорий дл данной характеристики.
    $res = DB::query("
        SELECT category_id
        FROM `".PREFIX."category_user_property` as сup
        WHERE сup.`property_id` = %s", $id);

    while ($row = DB::fetchAssoc($res)) {
      $categoryIds[] = $row['category_id'];
    }

    $this->data['selectedCatIds'] = implode(',', $categoryIds);
    $listCategories = MG::get('category')->getCategoryTitleList(0);
    $arrayCategories = MG::get('category')->getHierarchyCategory(0);
    $html = MG::get('category')->getTitleCategory($arrayCategories, 0);
    $this->data['optionHtml'] = $html;

    return true;
  }

  /**
   * Получает пользовательские характеристики для добавления нового продукта.
   */
  public function addUserProperty() {     
    return false;
  }

  /**
   * Сохраняет пользовательские настройки для товаров.
   */
  public function saveUserProperty() {
    $this->messageSucces = $this->lang['ACT_EDIT_POP'];
    $id = $_POST['id'];
    $array = $_POST;   
    if (!empty($id)) {
      unset($array['id']);
      if (DB::query('
        UPDATE `'.PREFIX.'property`
        SET '.DB::buildPartQuery($array).'
        WHERE id = %d
      ', $id)) {
        $result = true;
      }
    }
    return true;
  }

  /**
   * Сохраняет привязку выбранных категорий для характеристики.
   */
  public function saveUserPropWithCat() {
    $this->messageSucces = $this->lang['ACT_EDIT_POP'];
    $category = explode("|", $_POST['category']);
    $propId = $_POST['id'];
    // удалаляем все привязки характеристики к категориям сделанные ранее
    DB::query('
        DELETE FROM `'.PREFIX.'category_user_property`
        WHERE property_id = %d
      ', $propId);

    foreach ($category as $cat_id) {
      DB::query("
        INSERT INTO `".PREFIX."category_user_property`
        VALUES ('%s', '%s')"
        , $cat_id, $propId);
    }

    $allCategory = empty($_POST['category']) ? 1 : 0;

    // Обновлем флаг , использовать во всех категориях.
    DB::query('
        UPDATE `'.PREFIX.'property`
        SET all_category = '.$allCategory.'
        WHERE id = %d
        ', $propId);
    return true;
  }

  /**
   * Получает параметры редактируемого пользователя.
   */
  public function getUserData() {
    $this->messageError = $this->lang['ACT_GET_USER'];
    ;
    $this->data = USER::getUserById($_POST['id']);
    return false;
  }

  /**
   * Получает параметры категории.
   */
  public function getCategoryData() {
    $this->messageError = $this->lang['ACT_NOT_GET_CAT'];
    $result = DB::query("
      SELECT * FROM `".PREFIX."category`
      WHERE `id` =".DB::quote($_POST['id'])
    );
    if ($response = DB::fetchAssoc($result)) {
      $this->data = $response;
      return true;
    } else {
      return false;
    }

    return false;
  }

  /**
   * Получает параметры редактируемой страницы.
   */
  public function getPageData() {
    $this->messageError = $this->lang['ACT_SAVE_SETNG'];
    $result = DB::query("
      SELECT * FROM `".PREFIX."page`
      WHERE `id` =".DB::quote($_POST['id'])
    );
    if ($response = DB::fetchAssoc($result)) {
      $this->data = $response;
      return true;
    } else {
      return false;
    }

    return false;
  }

  /**
   * Устанавливает порядок сортировки. Меняет местами две категории.
   */
  public function changeSortCat() {
    $switchId = $_POST['switchId'];
    $sequence = explode(',', $_POST['sequence']);
    if (!empty($sequence)) {
      foreach ($sequence as $item) {
        MG::get('category')->changeSortCat($switchId, $item);
      }
    } else {
      $this->messageError = $this->lang['ACT_NOT_GET_CAT'];
      return false;
    }

    $this->messageSucces = $this->lang['ACT_SWITH_CAT'];
    return true;
  }

  /**
   * Устанавливает порядок сортировки. Меняет местами две записи
   */
  public function changeSortRow() {
    $switchId = $_POST['switchId'];
    $tablename = $_POST['tablename'];
    $sequence = explode(',', $_POST['sequence']);
    if (!empty($sequence)) {
      foreach ($sequence as $item) {
        MG::changeRowsTable($tablename, $switchId, $item);
      }
    } else {
      return false;
    }

    $this->messageSucces = $this->lang['ACT_SWITH'];
    return true;
  }

  /**
   * Устанавливает порядок сортировки. Меняет местами две страницы.
   */
  public function changeSortPage() {
    $switchId = $_POST['switchId'];
    $sequence = explode(',', $_POST['sequence']);
    if (!empty($sequence)) {
      foreach ($sequence as $item) {
        MG::get('pages')->changeSortPage($switchId, $item);
      }
    } else {
      $this->messageError = $this->lang['ACT_NOT_GET_PAGE'];
      return false;
    }

    $this->messageSucces = $this->lang['ACT_SWITH_PAGE'];
    return true;
  }

  /**
   * Возвращает ответ в формате JSON.
   * @param boolean $flag - если отработаный метод что-то вернул, то ответ считается успешным ждущей его фунции.
   * @return boolean
   */
  public function jsonResponse($flag) {
    if ($flag === null) {
      return false;
    }
    if ($flag) {
      $this->jsonResponseSucces($this->messageSucces);
    } else {
      $this->jsonResponseError($this->messageError);
    }
  }

  /**
   * Возвращает положительный ответ с сервера.
   * @param string $message
   */
  public function jsonResponseSucces($message) {
    $result = array(
      'data' => $this->data,
      'msg' => $message,
      'status' => 'success');
    echo json_encode($result);
  }

  /**
   * Возвращает отрицательный ответ с сервера.
   * @param string $message
   */
  public function jsonResponseError($message) {
    $result = array(
      'data' => $this->data,
      'msg' => $message,
      'status' => 'error');
    echo json_encode($result);
  }

  /**
   * Проверяет актуальность текущей версии системы.
   * @return void возвращает в AJAX сообщение о результате операции.
   */
  public function checkUpdata() {
    $msg = Updata::checkUpdata();

    if ($this->lang['ACT_THIS_LAST_VER'] == $msg['msg']) {
      $status = 'alert';
    } else {
      $status = 'success';
    }
    $response = array(
      'msg' => $msg['msg'],
      'status' => $status,
    );

    echo json_encode($response);
    exit;
  }

  /**
   * Обновленяет верcию CMS.
   *
   * @return void возвращает в AJAX сообщение о результате операции.
   */
  public function updata() {
    $version = $_POST['version'];

    if (Updata::updataSystem($version)) {
      $msg = $this->lang['ACT_UPDATE_VER'];
      $status = 'success';
    } else {
      $msg = $this->lang['ACT_ERR_UPDATE_VER'];
      $status = 'error';
    }

    $response = array(
      'msg' => $msg,
      'status' => $status,
    );

    echo json_encode($response);
  }

  /**
   * Отключает публичную часть сайта. Обычно требуется для внесения изменений администратором.
   * @return bool
   */
  public function downTime() {
    $downtime = MG::getOption('downtime');

    if ('Y' == $downtime) {
      $activ = 'N';
    } else {
      $activ = 'Y';
    }

    $res = DB::query('
      UPDATE `'.PREFIX.'setting`
      SET `value` = "'.$activ.'"
      WHERE `option` = "downtime"
    ');

    if ($res) {
      return true;
    };
  }

  /**
   * Функцию отправляет на сервер обновления информацию о системе и в случае одобрения скачивает архив с обновлением.
   * @return void возвращает в AJAX сообщение загруженную в систему версию.
   */
  public function preDownload() {
    $this->messageSucces = $this->lang['ACT_UPLOAD_ZIP']." ".$_POST['version'];
    $this->messageError = $this->lang['ACT_NOT_UPLOAD_ZIP'];
    $result = Updata::preDownload($_POST['version']);
    if ($result['status'] == 'error') {
      $this->messageError = $result['msg'];
      return false;
    }

    return true;
  }

  /**
   * Установливает загруженный ранее архив с обновлением.
   * @return void возвращает в AJAX сообщение о результате операции.
   */
  public function postDownload() {
    $this->messageSucces = $this->lang['ACT_UPDATE_TRUE'].$_POST['version'];
    $this->messageError = $this->lang['ACT_NOT_UPDATE_TRUE'];

    $version = $_POST['version'];

    if (Updata::extractZip($version.'-m.zip')) {
      $this->messageSucces = $this->lang['ACT_UPDATE_VER'];
      return true;
    } else {
      $this->messageError = $this->lang['ACT_ERR_UPDATE_VER'];
      return false;
    }
    return false;
  }

  /**
   * Устанавливает цветовую тему для меню в административном разделе
   * @return boolean
   */
  public function setTheme() {

    if ($_POST['color']) {
      MG::setOption(array('option' => 'themeColor', 'value' => $_POST['color']));
      MG::setOption(array('option' => 'themeBackground', 'value' => $_POST['background']));
    }
    return true;
  }

  /**
   * Устанавливает язык в административном разделе.
   * @return boolean
   */
  public function changeLanguage() {

    if ($_POST['language']) {
      MG::setOption(array('option' => 'languageLocale', 'value' => $_POST['language']));
    }
    return true;
  }

  /**
   * Устанавливает количество отображаемых записей в разделе товаров
   * @return boolean
   */
  public function setCountPrintRowsProduct() {

    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }


    MG::setOption(array('option' => 'countPrintRowsProduct', 'value' => $count));
    return true;
  }

  /**
   * Устанавливает количество отображаемых записей в разделе страницы
   * @return boolean
   */
  public function setCountPrintRowsPage() {

    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }


    MG::setOption(array('option' => 'countPrintRowsPage', 'value' => $count));
    return true;
  }

  /**
   * Устанавливает количество отображаемых записей в разделе пользователей
   * @return boolean
   */
  public function setCountPrintRowsOrder() {

    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }

    MG::setOption(array('option' => 'countPrintRowsOrder', 'value' => $count));
    return true;
  }

  /**
   * Устанавливает количество отображаемых записей в разделе заказов
   * @return boolean
   */
  public function setCountPrintRowsUser() {

    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }

    MG::setOption(array('option' => 'countPrintRowsUser', 'value' => $count));
    return true;
  }

  /**
   * Возвращает список найденых продуктов по ключевому слову
   * @return boolean
   */
  public function searchProduct() {
    $this->messageSucces = $this->lang['SEACRH_PRODUCT'];
    $model = new Models_Catalog;

    $arr = $model->getListProductByKeyWord($_POST['keyword'], true, false, true);

    $product = new Models_Product;
    foreach ($arr['catalogItems'] as $key => $item) {
      if ($item['variant_exist']) {
        $variants = $product->getVariants($item['id']);
        foreach ($variants as $var) {
          $arr['catalogItems'][$key]['variants'][] = $var;
        }
      }
    }

    $this->data = $arr['catalogItems'];
    return true;
  }

  /**
   * Устанавливает локаль для плагина, используется в JS плагинов
   * @return boolean
   */
  public function seLocalesToPlug() {
    $this->data = PM::plugLocales($_POST['pluginName']);
    return true;
  }

  /**
   * Сохранение способа доставки
   */
  public function saveDeliveryMethod() {
    $this->messageSucces = $this->lang['ACT_SUCCESS'];
    $this->messageError = $this->lang['ACT_ERROR'];
    $status = $_POST['status'];
    $deliveryName = $_POST['deliveryName'];
    $deliveryCost = $_POST['deliveryCost'];
    $deliveryId = $_POST['deliveryId'];
    $free = $_POST['free'];

    $paymentMethod = $_POST['paymentMethod'];
    $paymentArray = json_decode($paymentMethod, true);

    $deliveryDescription = $_POST['deliveryDescription'];
    $deliveryActivity = $_POST['deliveryActivity'];

    switch ($status) {
      case 'createDelivery':
        $sql = "
          INSERT INTO `".PREFIX."delivery` (`name`,`cost`, `description`, `activity`,`free`)
          VALUES (
            '".$deliveryName."', '".$deliveryCost."', '".$deliveryDescription."', '".$deliveryActivity."', '".$free."'
          );
        ";
        $result = DB::query($sql);

        if ($deliveryId = DB::insertId()) {
          $status = 'success';
          $msg = $this->lang['ACT_SUCCESS'];
        } else {
          $status = 'error';
          $msg = $this->lang['ACT_ERROR'];
        }

        foreach ($paymentArray as $paymentId => $compare) {
          $sql = "
            INSERT INTO `".PREFIX."delivery_payment_compare`
              (`compare`,`payment_id`, `delivery_id`)
            VALUES (
              '".$compare."', '".$paymentId."', '".$deliveryId."'
            );
          ";
          $result = DB::query($sql);
        }

        break;
      case 'editDelivery':
        $sql = "
          UPDATE `".PREFIX."delivery`
          SET `name` = '".$deliveryName."',
              `cost` = '".$deliveryCost."',
              `description` = '".$deliveryDescription."',
              `activity` = '".$deliveryActivity."',
              `free` = '".$free."'             
          WHERE id = ".$deliveryId;
        $result = DB::query($sql);

        foreach ($paymentArray as $paymentId => $compare) {
          $result = DB::query("
            SELECT * 
            FROM `".PREFIX."delivery_payment_compare`         
            WHERE `payment_id` = ".$paymentId."
              AND `delivery_id` = ".$deliveryId);
          if (!DB::numRows($object)) {
            $sql = "
                INSERT INTO `".PREFIX."delivery_payment_compare`
                  (`compare`,`payment_id`, `delivery_id`)
                VALUES (
                  '".$compare."', '".$paymentId."', '".$deliveryId."'
                );
              ";
            $result = DB::query($sql);
          } else {
            $sql = "
              UPDATE `".PREFIX."delivery_payment_compare`
              SET `compare` = '".$compare."'
              WHERE `payment_id` = ".$paymentId."
                AND `delivery_id` = ".$deliveryId;
            $result = DB::query($sql);
          }
        }


        if ($result) {
          $status = 'success';
          $msg = $this->lang['ACT_SUCCESS'];
        } else {
          $status = 'error';
          $msg = $this->lang['ACT_ERROR'];
        }
    }

    $response = array(
      'data' => array(
        'id' => $deliveryId,
      ),
      'status' => $status,
      'msg' => $msg,
    );
    echo json_encode($response);
  }

  /**
   * Удаляет способ доставки.
   * @return boolean
   */
  public function deleteDeliveryMethod() {
    $this->messageSucces = $this->lang['ACT_SUCCESS'];
    $this->messageError = $this->lang['ACT_ERROR'];
    $res1 = DB::query('DELETE FROM `'.PREFIX.'delivery` WHERE `id`= '.$_POST['id']);
    $res2 = DB::query('DELETE FROM `'.PREFIX.'delivery_payment_compare` WHERE `delivery_id`= '.$_POST['id']);

    if ($res1 && $res2) {
      return true;
    }
    return false;
  }

  /**
   * Сохраняет способ оплаты.
   */
  public function savePaymentMethod() {
    $paymentParam = str_replace("'", "\'", $_POST['paymentParam']);

    $deliveryMethod = $_POST['deliveryMethod'];
    $deliveryArray = json_decode($deliveryMethod, true);
    $paymentActivity = $_POST['paymentActivity'];
    $paymentId = $_POST['paymentId'];

    if (is_array($deliveryArray)) {
      foreach ($deliveryArray as $deliveryId => $compare) {
        $sql = "
          UPDATE `".PREFIX."delivery_payment_compare`
          SET `compare` = '".$compare."'
          WHERE `payment_id` = ".$paymentId."
            AND `delivery_id` = ".$deliveryId;
        $result = DB::query($sql);
      }
    }
    $sql = "
      UPDATE `".PREFIX."payment`
      SET `paramArray` = '".$paymentParam."',
          `activity` = ".DB::quote($paymentActivity)."
      WHERE id = ".$paymentId;
    $result = DB::query($sql);

    if ($result) {
      $status = 'success';
      $msg = $this->lang['ACT_SUCCESS'];
    } else {
      $status = 'error';
      $msg = $this->lang['ACT_ERROR'];
    }

    $sql = "
      SELECT *
      FROM `".PREFIX."payment`     
      WHERE id = ".$paymentId;
    $result = DB::query($sql);
    if ($row = DB::fetchAssoc($result)) {
      $paymentParam = $row['paramArray'];
    }

    $response = array(
      'status' => $status,
      'msg' => $msg,
      'data' => array('paymentParam' => $paymentParam)
    );
    echo json_encode($response);
  }

  /**
   * Обновляет способов оплаты и доставки при переходе по вкладкам в админке.
   */
  public function getMethodArray() {
    $mOrder = new Models_Order;
    $deliveryArray = $mOrder->getDeliveryMethod();
    $response['data']['deliveryArray'] = $deliveryArray;

    $paymentArray = array();
    $i = 1;
    while ($payment = $mOrder->getPaymentMethod($i)) {
      $paymentArray[$i] = $payment;
      $i++;
    }
    $response['data']['paymentArray'] = $paymentArray;
    echo json_encode($response);
  }

  /**
   * Проверяет наличие подключенного модуля xmlwriter и библиотеки libxml.
   */
  public function existXmlwriter() {
    $this->messageSucces = 'Начата генерация файла';
    $this->messageError = 'Отсутствует необходимое PHP расширение xmlwriter или модуль libxml';
    if (LIBXML_VERSION && extension_loaded('xmlwriter')) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Осуществляет импорт данных в таблицы продуктов и категорий.
   */
  public function importFromCsv() {
    $this->messageSucces = 'Импорт выполнен';
    $this->messageError = 'Ошибка';
    $importer = new Import();
    $importer->ImportFromCSV();
    return true;
  }

  /**
   * Получает файл шаблона.
   */
  public function getTemplateFile() {
    $this->messageError = $this->lang['NOT_FILE_TPL'];
    if (file_exists(PATH_TEMPLATE.$_POST['path']) && is_writable(PATH_TEMPLATE.$_POST['path'])) {
      $this->data['filecontent'] = file_get_contents(PATH_TEMPLATE.$_POST['path']);
      return true;
    } else {
      $this->data['filecontent'] = "CHMOD = ".substr(sprintf('%o', fileperms(PATH_TEMPLATE.$_POST['path'])), -4);
      return true;
    }
    return false;
  }

  /**
   * Сохраняет файл шаблона.
   */
  public function saveTemplateFile() {
   
    return false;
   
  }

  /**
   * Очищает кеш проверки версий и проверяет наличие новой.
   */
  public function clearLastUpdate() {
    if (!$checkLibs = MG::libExists()) {
      MG::setOption('timeLastUpdata', '');
      $newVer = Updata::checkUpdata(true);    
      if (!$newVer) {
        $this->messageError = "Пока нет новых версий";
        return false;
      }
      $this->messageSucces = "Доступна новая версия ".$newVer['lastVersion'];
      return true;
    } else {
      $this->messageError = "Невозможно проверить наличие версий. Библиотека CURL отключена";
      return false;
    }
  }

  /**
   * Получает список продуктов при вводе в поле поиска товара при создании заказа через админку.
   */
  public function getSearchData() {
    $keyword = URL::getQueryParametr('search');
    if (!empty($keyword)) {
      $catalog = new Models_Catalog;
      $product = new Models_Product;
      $items = $catalog->getListProductByKeyWord($keyword, true, true, true);

      foreach ($items['catalogItems'] as $key => $item) {

        $propertyFormData = $product->createPropertyForm($param = array(
          'id' => $item['id'],
          'maxCount' => 999,
          'productUserFields' => $item['thisUserFields'],
          'action' => "/catalog",
          'method' => "POST",
          'ajax' => true,
          'blockedProp' => array(),
          'noneAmount' => true,
          'titleBtn' => "<span>Добавить в заказ</span>",
          'blockVariants' => $product->getBlockVariants($item['id']),
          'classForButton' => 'addToCart buy-product buy custom-btn',
        ));
       
        $items['catalogItems'][$key]['price'] += $propertyFormData['marginPrice'];
        $items['catalogItems'][$key]['propertyForm'] = $propertyFormData['html'];
      }
    }

    $searchData = array(
      'status' => 'success',
      'item' => array(
        'keyword' => $keyword,
        'count' => $items['numRows'],
        'items' => $items,
      ),
      'currency' => MG::getOption('currency')
    );

    echo json_encode($searchData);
    exit;
  }

  /**
   * Возвращает список заказов для вывода статистики по заданному периоду.
   * @return boolean
   */
  public function getOrderPeriodStat() {
    $model = new Models_Order;
    $this->data = $model->getStatisticPeriod($_POST['from_date_stat'], $_POST['to_date_stat']);
    return true;
  }

  /**
   * Возвращает список заказов для вывода статистики.
   * @return boolean
   */
  public function getOrderStat() {
    $model = new Models_Order;
    $this->data = $model->getOrderStat();
    return true;
  }

  /**
   * Получает параметры заказа
   */
  public function getOrderData() {

    $model = new Models_Order();
    $orderData = $model->getOrder(" id = ".DB::quote($_POST['id']));
    $orderData = $orderData[$_POST['id']];
    $orderData['yur_info'] = unserialize(stripslashes($orderData['yur_info']));
    $orderData['order_content'] = unserialize(stripslashes($orderData['order_content']));

    if (!empty($orderData['order_content'])) {
      $product = new Models_Product();
      foreach ($orderData['order_content'] as &$items) {
        $res = $product->getProduct($items['id']);
        $items['image_url'] = $res['image_url'];
        $items['property'] = htmlspecialchars_decode(str_replace('&amp;', '&', $items['property']));
        $response['discount'] = $items['discount'];
      }
    }

    //заменить на получение скидки
    $codes = array();
    $percent = 0;
    // Запрос для проверки , существуют ли промокоды.  
    $result = DB::query('SHOW TABLES');
    while ($row = DB::fetchArray($result)) {
      if (PREFIX.'promo-code' == $row[0]) {
        $res = DB::query('SELECT code, percent FROM `'.PREFIX.'promo-code` WHERE invisible = 1');
        while ($code = DB::fetchAssoc($res)) {
          $codes[] = $code['code'];
          if ($code['code'] == $orderData['order_content'][0]['coupon']) {
            $percent = $code['percent'];
          }
        }
      };
    }

    $response['order'] = $orderData;
    $response['order']['discontPercent'] = $percent;
    $response['order']['promoCodes'] = $codes;

    $deliveryArray = $model->getDeliveryMethod();
    $response['deliveryArray'] = $deliveryArray;

    $paymentArray = array();
    $i = 1;
    while ($payment = $model->getPaymentMethod($i)) {
      $paymentArray[$i] = $payment;
      $i++;
    }
    $response['paymentArray'] = $paymentArray;

    $this->data = $response;

    return true;
  }

  /**
   * Устанавливает флаг редактирования сайта.
   * @return boolean
   */
  public function setSiteEdit() {
    MG::setOption(array('option' => 'enabledSiteEditor', 'value' => $_POST['enabled']));
    return true;
  }

  /**
   * Возвращает список найденых продуктов по ключевому слову.
   * @return boolean
   */
  public function uploadCsvToImport() {

    $uploader = new Upload(false);
    $tempData = $uploader->addImportCatalogCSV();
    $this->data = array('img' => $tempData['actualImageName']);

    if ($tempData['status'] == 'success') {
      $this->messageSucces = $tempData['msg'];
      return true;
    } else {
      $this->messageError = $tempData['msg'];
      return false;
    }
  }

  /**
   * Импортирует данные из файла importCatalog.csv.
   * @return boolean
   */
  public function startImport() {
    $this->messageSucces = "Процесс запущен";
    $this->messageError = "Неудалось начать импорт";


    $import = new Import();
    if (empty($_POST['rowId'])) {
      unset($_SESSION['stopProcessImportCsv']);
    }
 
    if ($_POST['delCatalog'] !== null) {
      if ($_POST['delCatalog'] === "true") {
        DB::query('TRUNCATE TABLE  `'.PREFIX.'product_variant`');
        DB::query('TRUNCATE TABLE  `'.PREFIX.'product`');
        DB::query('TRUNCATE TABLE  `'.PREFIX.'product_user_property`');
        DB::query('TRUNCATE TABLE  `'.PREFIX.'category`');
        DB::query('TRUNCATE TABLE  `'.PREFIX.'category_user_property`');
      }
    }

    $this->data = $import->startUpload($_POST['rowId']);
    return true;
  }

  /**
   * Останавливает процесс импорта каталога из файла importCatalog.csv.
   * @return boolean
   */
  public function canselImport() {
    $this->messageSucces = "Процесс прерван пользователем";
    $this->messageError = "Неудалось отменить импорт";

    $import = new Import();
    $import->stopProcess();

    return true;
  }

  /**
   * Сохраняет реквизиты в настройках заказа.
   * @return boolean
   */
  public function savePropertyOrder() {
    $this->messageSucces = "Настройки сохранены";
    $this->messageError = "Неудалось сохранить настройки";

    $propertyOrder = serialize($_POST);
    $propertyOrder = addslashes($propertyOrder);
    MG::setOption(array('option' => 'propertyOrder', 'value' => $propertyOrder));

    return true;
  }

}