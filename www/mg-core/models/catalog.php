<?php

/**
 * Модель: Catalog
 *
 * Класс Models_Catalog реализует логику работы с каталогом.
 * - Проверяет данные из формы авторизации;
 * - Получает параметры пользователя по его логину.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 */
class Models_Catalog {

  /**
   * @var type @var mixed Массив с категориями продуктов.
   */
  public $categoryId = array();

  /**
   * @var type @var mixed Массив текущей категории.
   */
  public $currentCategory = array();

  /**
   * @var type @var mixed Фильтр пользователя..
   */
  public $userFilter = array();

  /**
   * Получает ссылку и название текущей категории.
   * @return bool
   */
  protected function getCurrentCategory() {
    $result = false;

    $sql = '
      SELECT *
      FROM `'.PREFIX.'category`
      WHERE id = %d
    ';

    if (end($this->categoryId)) {
      $result = DB::query($sql, end($this->categoryId));
      if ($this->currentCategory = DB::fetchAssoc($result)) {
        $result = true;
      }
    } else {
      $this->currentCategory['url'] = 'catalog';
      $this->currentCategory['title'] = 'Каталог';
      $result = true;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список товаров и пейджер для постраничной навигации.
   * @param int $countRows - количество возвращаемых записей для одной страницы.
   * @param boolean $mgadmin - откуда вызван метод, из публичной части или панели управления.
   * @param boolean $onlyActive - учитывать только активные продукты.
   * @return type
   */
  public function getList($countRows = 20, $mgadmin = false, $onlyActive = false) {

    // Если не удалось получить текущую категорию.    
    if (!$this->getCurrentCategory()) {
      echo 'Ошибка получения данных!';
      exit;
    }

    // Страница.
    $page = URL::get("page");
    
     $sql = '
        SELECT
          DISTINCT p.id,
          CONCAT(c.parent_url,c.url) as category_url,
          p.url as product_url,
          p.*, pv.product_id as variant_exist
        FROM `'.PREFIX.'product` p
        LEFT JOIN `'.PREFIX.'category` c
          ON c.id = p.cat_id
        LEFT JOIN `'.PREFIX.'product_variant` pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM( pv.count ) AS varcount
          FROM  `'.PREFIX.'product_variant` AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id';      

    // Формируем фильтр для продуктов, по имеющимся категориям, внутри выбранной.
    if ('catalog' == $this->currentCategory['url']) {
      // Запрос вернет все товары внутри выбраной категории, а также внутри вложеных в нее категорий.    
      if ($onlyActive) {
        $sql .= ' WHERE p.activity = 1';
        if (MG::getSetting('printProdNullRem') == "true") {
          $sql .=" AND (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)";
        }
      } else {
        if (MG::getSetting('printProdNullRem') == "true") {
          $sql .= ' WHERE (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)';
        }
      }      
      
    } else {
      $filter = 'c.id IN ('.implode(',', $this->categoryId).')';
      // Запрос вернет общее кол-во продуктов в выбранной категории.
      $sql .='  WHERE  '.$filter;
      if ($onlyActive) {
        $sql .= ' AND p.activity = 1';
      }
      if (MG::getSetting('printProdNullRem') == "true") {
        $sql .=" AND (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)";
      } 
      
    }

    $sql .= ' ORDER BY sort DESC';
    $navigator = new Navigator($sql, $page, $countRows); //определяем класс
    $this->pages = $navigator->getRowsSql();

    /**
     * Достаем пользовательские характеристики для каждого товара.
     */
    $idsProduct = array();

    foreach ($this->pages as $key => $product) {
      $idsProduct[$product['id']] = $key;

      // Назначаем для продукта позьзовательские характеристики по умолчанию, заданные категорией.
      $this->pages[$key]['thisUserFields'] = MG::get('category')->getUserPropertyCategoryById($product['cat_id']);

      // Формируем ссылки подробнее и в корзину.
      $this->pages[$key]['actionBuy'] = '<a href="'.SITE.'/catalog?inCartProductId='.$product["id"].'" class="addToCart product-buy" data-item-id="'.$product["id"].'">В корзину</a>';
      $this->pages[$key]['actionView'] = '<a href="'.SITE.'/'.(isset($product["category_url"]) ? $product["category_url"] : 'catalog').'/'.$product["product_url"].'" class="product-info">Подробнее</a>';
    }

    // Собираем все ID продуктов в один запрос.
    if ($prodSet = trim(DB::quote(implode(',', array_keys($idsProduct))), "'")) {
      // формируем список id продуктов к которым нужно найти пользовательские характеристики.
      $where = ' IN ('.$prodSet.')';
    } else {
      $where = ' IN (0)';
    }

    $res = DB::query("          
      SELECT pup.property_id, pup.value, pup.product_id, prop.*
      FROM `".PREFIX."product_user_property` as pup
      LEFT JOIN `".PREFIX."property` as prop
        ON pup.property_id = prop.id
      WHERE pup.`product_id` ".$where);

    while ($userFields = DB::fetchAssoc($res)) {
      // Дописываем в массив пользовательских характеристик, все переопределенные для каждого тоавара, оставляя при этом не измененные характеристики по умолчанию.
      $this->pages[$idsProduct[$userFields['product_id']]]['thisUserFields'][$userFields['property_id']] = $userFields;
    }

    if ($mgadmin) {
      $this->pager = $navigator->getPager('forAjax');
    } else {
      $this->pager = $navigator->getPager();
    }

    $result = array('catalogItems' => $this->pages, 'pager' => $this->pager);
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает список продуктов в соответствии с выбранными параметрами фильтра.
   * @param type $countRows - количество записей;
   * @param type $userfilter - пользовательская составляющая для запроса;
   * @param type $mgadmin - админка;
   * @return array
   */
  public function getListByUserFilter($countRows = 20, $userfilter, $mgadmin = false) {

    // Вычисляет общее количество продуктов.
    $page = URL::get("page");

    // Запрос вернет общее кол-во продуктов в выбранной категории.
    $sql = '
      SELECT DISTINCT p.id,
        CONCAT(c.parent_url,c.url) as category_url,
        p.url as product_url,
        p.*,pv.product_id as variant_exist
      FROM `'.PREFIX.'product` p
      LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id
      LEFT JOIN `'.PREFIX.'product_variant` pv
        ON p.id = pv.product_id
     WHERE  '.$userfilter;

    $navigator = new Navigator($sql, $page, $countRows); //определяем класс.
    $this->pages = $navigator->getRowsSql();
    $model = new Models_Product();

    /**
     * Достаем пользовательские характеристики для каждого товара.
     */
    $idsProduct = array();

    foreach ($this->pages as $key => $product) {
      $idsProduct[$product['id']] = $key;

      // Назначаем для продукта позьзовательские характеристики по умолчанию, заданные категорией.
      $this->pages[$key]['thisUserFields'] = MG::get('category')->getUserPropertyCategoryById($product['cat_id']);

      // Формируем ссылки подробнее и в корзину.
      $this->pages[$key]['actionBuy'] = '<a href="'.SITE.'/catalog?inCartProductId='.$product["id"].'" class="addToCart product-buy" data-item-id="'.$product["id"].'">В корзину</a>';
      $this->pages[$key]['actionView'] = '<a href="'.SITE.'/'.(isset($product["category_url"]) ? $product["category_url"] : 'catalog').'/'.$product["product_url"].'" class="product-info">Подробнее</a>';
    }

    $arrayVariants = $model->getBlocksVariantsToCatalog(array_keys($idsProduct), true);

    foreach (array_keys($idsProduct) as $id) {
      $this->pages[$idsProduct[$id]]['variants'] = $arrayVariants[$id];
    }

    // Собираем все ID продуктов в один запрос.
    if ($prodSet = trim(DB::quote(implode(',', array_keys($idsProduct))), "'")) {
      // Формируем список id продуктов, к которым нужно найти пользовательские характеристики.
      $where = ' IN ('.$prodSet.')';
    } else {
      $where = ' IN (0)';
    }

    $res = DB::query("          
      SELECT pup.property_id, pup.value, pup.product_id, prop.*
      FROM `".PREFIX."product_user_property` as pup
      LEFT JOIN `".PREFIX."property` as prop
        ON pup.property_id = prop.id
      WHERE pup.`product_id` ".$where);

    while ($userFields = DB::fetchAssoc($res)) {
      // дописываем в массив пользовательских характеристик, все переопределенные для каждого тоавара, оставляя при этом не измененные характеристики по умолчанию
      $this->pages[$idsProduct[$userFields['product_id']]]['thisUserFields'][$userFields['property_id']] = $userFields;
    }

    if ($mgadmin) {
      $this->pager = $navigator->getPager('forAjax');
    } else {

      $this->pager = $navigator->getPager();
    }

    $result = array('catalogItems' => $this->pages, 'pager' => $this->pager);
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список найденных продуктов соответствующих поисковой фразе.
   * @param string $keyword - поисковая фраза.
   * @param string $allRows - получить сразу все записи.
   * @param string $onlyActive - учитывать только активные продукты.
   * @param boolean $adminPanel - запрос из публичной части или админки.
   * @return array
   */
  public function getListProductByKeyWord($keyword, $allRows = false, $onlyActive = false, $adminPanel = false) {
    $result = array();

    // Поиск по точному соответствию.
    $model = new Models_Catalog();

    if (!isset($_GET["page"])) {
      $result = $model->getListByUserFilter(1, " p.title =".DB::quote(trim($keyword))." OR p.code =".DB::quote(trim($keyword)));
    }

    if (count($result['catalogItems']) !== 0) {
      foreach ($result['catalogItems'] as &$item) {
        $imagesUrl = explode("|", $item["image_url"]);
        $item["image_url"] = "";
        if (!empty($imagesUrl[0])) {
          $item["image_url"] = $imagesUrl[0];
        }
        $item['currency'] = MG::getSetting('currency');
      }
      $args = func_get_args();
      return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    };

    // Пример $keyword = " 'красный',   зеленый "
    // Убираем начальные пробелы и конечные.
    $keyword = trim($keyword); //$keyword = "'красный',   зеленый"    
    // Вырезаем спец символы из поисковой фразы.
    $keyword = preg_replace('/[-`~!#$%^&*()_=+\\\\|\\/\\[\\]{};:"\',<>?]+/', '', $keyword); //$keyword = "красный   зеленый"
    // Замена повторяющихся пробелов на на один.
    $keyword = preg_replace('/ +/', ' ', $keyword); //$keyword = "красный зеленый"
    // Обрамляем каждое слово в звездочки, для расширенного поиска.
    $keyword = str_replace(' ', '* *', $keyword); //$keyword = "красный* *зеленый"
    // Добавляем по краям звездочки.
    $keyword = '*'.$keyword.'*'; //$keyword = "*красный* *зеленый*"

    $sql = " 
      SELECT distinct p.code, CONCAT(c.parent_url,c.url) AS category_url, 
        p.url AS product_url, p.*, pv.product_id as variant_exist
      FROM  `".PREFIX."product` AS p
      LEFT JOIN  `".PREFIX."category` AS c ON c.id = p.cat_id
      LEFT JOIN  `".PREFIX."product_variant` AS pv ON p.id = pv.product_id";

    if (!$adminPanel) {
      $sql .=" LEFT JOIN (
        SELECT pv.product_id, SUM( pv.count ) AS varcount
        FROM  `".PREFIX."product_variant` AS pv
        GROUP BY pv.product_id
      ) AS temp ON p.id = temp.product_id";
    }

    $product = new Models_Product();
    $fulltext = "";
    
    // Проверяем наличие записей в вариантах если их нет, то не включаем 
    // в поиск полнотекстовые индесты таблицы вариантов.
    if ($product->getVariants($id)) {
      $fulltext = ", pv.`code`, pv.`title_variant`";
    }
    $sql .=
      " WHERE MATCH (
        p.`title` , p.`description` , p.`code` , p.`meta_title` , p.`meta_keywords` , p.`meta_desc` ".$fulltext."
        )
        AGAINST (
        '".$keyword."'
        IN BOOLEAN
        MODE
        ) ";

    // Проверяем чтобы в вариантах была хотябы одна единица.
    if (!$adminPanel) {
      $sql .=" AND (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)";
    }

    if ($onlyActive) {
      $sql .= ' AND p.`activity` = 1';
    }

    $page = URL::get("page");

    $settings = MG::get('settings');
    $navigator = new Navigator($sql, $page, $settings['countСatalogProduct'], $linkCount = 6, $allRows); // Определяем класс.
    $this->pages = $navigator->getRowsSql();
    $idsProduct = array();

    if (!empty($this->pages)) {
      foreach ($this->pages as $key => $product) {
        $idsProduct[$product['id']] = $key;
        // Назначаем для продукта позьзовательские характеристики по умолчанию, заданные категорией.
        $this->pages[$key]['thisUserFields'] = MG::get('category')->getUserPropertyCategoryById($product['cat_id']);
        $this->pages[$key]['currency'] = $settings['currency'];

        $this->pages[$key]['actionBuy'] = '<a href="'.SITE.'/catalog?inCartProductId='.$product["id"].'" class="addToCart product-buy" data-item-id="'.$product["id"].'">В корзину</a>';
        $this->pages[$key]['actionView'] = '<a href="'.SITE.'/'.(isset($product["category_url"]) ? $product["category_url"] : 'catalog').'/'.$product["product_url"].'" class="product-info">Подробнее</a>';

        $imagesUrl = explode("|", $this->pages[$key]['image_url']);
        $this->pages[$key]["image_url"] = "";
        if (!empty($imagesUrl[0])) {
          $this->pages[$key]["image_url"] = $imagesUrl[0];
        }
      }
    }

    if ($findPart = trim(DB::quote(implode(',', array_keys($idsProduct))), "'")) {
      // Формируем список id продуктов к которым нужно найти пользовательские характеристики.
      $where = ' IN ('.$findPart.')';
    } else {
      $findPart = ' IN (0)';
    }

    $res = DB::query("
      SELECT pup.property_id, pup.value, pup.product_id, pup.type_view, prop.*
      FROM `".PREFIX."product_user_property` as pup
      LEFT JOIN `".PREFIX."property` as prop
        ON pup.property_id = prop.id
      WHERE pup.`product_id` ".$where);
    while ($userFields = DB::fetchAssoc($res)) {
      if (!empty($this->pages)) {
        $this->pages[$idsProduct[$userFields['product_id']]]['thisUserFields'][$userFields['property_id']] = $userFields;
      }
    }

    $this->pager = $navigator->getPager();

    $result = array(
      'catalogItems' => $this->pages, 
      'pager' => $this->pager, 
      'numRows' => $navigator->getNumRowsSql()
    );

    if (count($result['catalogItems']) == 0) {
      //примитивный поиск по названию. 100% соответствие
      $sql = " 
        SELECT id
        FROM  `".PREFIX."product` AS p
        WHERE title = ".DB::quote($keyword)." OR code = ".DB::quote($keyword);
      if ($row = DB::fetchAssoc($sql)) {
        $product = new Models_Product();      
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Выгружает содержание всего каталога в CSV файл.
   * @return array
   */
  public function exportToCsv() {

  }
  
  /**
   * Добавляет продукт в CSV выгрузку.
   * @param type $row - продукт.
   * @param type $variant - есть ли варианты этого продукта.
   * @return string
   */  
  public function addToCsvLine($row, $variant = false) {
    $row['category_url'] = '"'.str_replace("\"", "\"\"", $row['category_url']).'"';
    $row['title'] = '"'.str_replace("\"", "\"\"", $row['title']).'"';
    $row['description'] = '"'.str_replace("\"", "\"\"", $row['description']).'"';
    $row['price'] = '"'.str_replace("\"", "\"\"", $row['price']).'"';
    $row['url'] = '"'.str_replace("\"", "\"\"", $row['url']).'"';
    $row['image_url'] = '"'.str_replace("\"", "\"\"", $row['image_url']).'"';
    $row['code'] = '"'.str_replace("\"", "\"\"", $row['code']).'"';
    $row['count'] = '"'.str_replace("\"", "\"\"", $row['count']).'"';
    $row['activity'] = '"'.str_replace("\"", "\"\"", $row['activity']).'"';
    $row['meta_title'] = '"'.str_replace("\"", "\"\"", $row['meta_title']).'"';
    $row['meta_keywords'] = '"'.str_replace("\"", "\"\"", $row['meta_keywords']).'"';
    $row['meta_desc'] = '"'.str_replace("\"", "\"\"", $row['meta_desc']).'"';
    $row['old_price'] = '"'.str_replace("\"", "\"\"", $row['old_price']).'"';
    $row['recommend'] = '"'.str_replace("\"", "\"\"", $row['recommend']).'"';
    $row['new'] = '"'.str_replace("\"", "\"\"", $row['new']).'"';
    $row['sort'] = '"'.str_replace("\"", "\"\"", $row['sort']).'"';
    $row['description'] = str_replace("\r", "", $row['description']);
    $row['description'] = str_replace("\n", "", $row['description']);
    $row['meta_desc'] = str_replace("\r", "", $row['meta_desc']);
    $row['meta_desc'] = str_replace("\n", "", $row['meta_desc']);

    $csvText = $row['category_url'].";".
      $row['title'].";";
    if ($variant) {
      $csvText .= $row['title_variant'].";";
    } else {
      $csvText .= ";";
    }
    $csvText .= $row['description'].";".
      $row['price'].";".
      $row['url'].";".
      $row['image_url'].";".
      $row['code'].";".
      $row['count'].";".
      $row['activity'].";".
      $row['meta_title'].";".
      $row['meta_keywords'].";".
      $row['meta_desc'].";".
      $row['old_price'].";".
      $row['recommend'].";".
      $row['new'].";".
      $row['sort']."\n";

    return $csvText;
  }

  /**
   * Получает массив категорий.
   * @return mixed - ассоциативный массив id => категория.
   */
  public function getCategoryArray() {
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'category`');
    while ($row = DB::fetchAssoc($res)) {
      $result[$row['id']] = $row;
    }
    return $result;
  }

  /**
   * Получает минимальную цену из всех сстоимостей продуктов.
   * @return float.
   */
  public function getMinPrice() {
    $res = DB::query('SELECT MIN(`price`) as price FROM `'.PREFIX.'product`');
    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }
    return $result;
  }

  /**
   * Получает минимальную цену из всхе сстоимостей продуктов.
   * @return float.
   */
  public function getMaxPrice() {
    $res = DB::query('SELECT MAX(`price`) as price FROM `'.PREFIX.'product`');
    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }
    return $result;
  }

  /**
   * Возвращает пример загружаемого каталога.
   * @return array
   */
  public function getExampleCSV() {

    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream;");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=data.csv");
    header("Content-Transfer-Encoding: binary ");

    $csvText .='Категория;Товар;Вариант;Описание;Цена;URL;Изображение;Артикул;Количество;Активность;Заголовок [SEO];Ключевые слова [SEO];Описание [SEO];Старая цена;Рекомендуемый;Новый;Сортировка
"Ювелирные украшения";"Обручальные кольца";;"<p><strong>Обручальное кольцо</strong> &mdash; кольцо из драгоценного металла, которое носят на безымянном пальце левой или правой руки (по-разному в разных странах). Обручальное кольцо символизирует брачные узы: супруги носят его как знак верности друг другу.</p>";"12890";"obruchal-nye-kol-ca";"1364756100.jpg";"COL1";"8";"1";"Обручальные кольца";"Обручальные кольца, кольцо";"Обручальное кольцо — кольцо из драгоценного металла, которое носят на безымянном пальце левой или правой руки (по-разному в разных странах). Обручальное кольцо символизирует брачные узы: супруги носят его как знак верности друг другу.";"";"0";"1";"1"
"Ювелирные украшения";"Кольцо со вставками";;"<p>Колечко, украшенное драгоценными камнями, порадует любую девушку. Кольцо с бриллиантом является лучшим средством демонстрации серьезности чувств...</p>";"12890";"kol-co-so-vstavkami";"1364756044.jpg";"COL2";"-1";"1";"Обручальные кольца";"Обручальные кольца, кольцо";"Колечко, украшенное драгоценными камнями, порадует любую девушку. Кольцо с бриллиантом является лучшим средством демонстрации серьезности чувств...";"";"0";"0";"2"
"Ювелирные украшения";"Кольцо для помолвки";;"<p>Помолвка &ndash; это событие. Это маленький праздник любви. Ведь получив согласие, теперь вы будете зваться женихом и невестой, а все мысли и поступки будет направлены на приятную и суматошную подготовку к свадьбе. Именно все эти хлопоты и приготовления приведут к тому, что после свадебного обряда вы сможете назвать ее своей женой.</p>";"12890";"kolco-dlya-pomolvki";"1364756189.jpg";"COL3";"-1";"1";"Кольцо для помолвки";"Кольцо для помолвки";"Помолвка – это событие. Это маленький праздник любви. Ведь получив согласие, теперь вы будете зваться женихом и невестой, а все мысли и поступки будет направлены на приятную и суматошную по";"";"0";"0";"3"
"Ювелирные украшения";"Кольцо для помолвки";;"<p>Помолвка &ndash; это событие. Это маленький праздник любви. Ведь получив согласие, теперь вы будете зваться женихом и невестой, а все мысли и поступки будет направлены на приятную и суматошную подготовку к свадьбе. Именно все эти хлопоты и приготовления приведут к тому, что после свадебного обряда вы сможете назвать ее своей женой.</p>";"12890";"kolco-dlya-pomolvki_16";"1364756189.jpg";"COL3";"-1";"1";"Кольцо для помолвки";"Кольцо для помолвки";"Помолвка – это событие. Это маленький праздник любви. Ведь получив согласие, теперь вы будете зваться женихом и невестой, а все мысли и поступки будет направлены на приятную и суматошную по";"";"0";"0";"3"
"Одежда/Для женщин";"Футболка спорт S2";;"<p>Футболка - сочетание комфорта и стиля. Сочный зеленый цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.</p>";"1390";"futbolka-sport-s2";"1364756694.jpg|1364754799.jpg|1364754670.jpg|1364754612.jpg|1364754870.jpg";"FUBL2";"-1";"1";"Футболка спорт S2";"Футболка спорт S2";"Футболка - сочетание комфорта и стиля. Сочный красный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.";"0";"1";"1";"4"
"Одежда/Для женщин";"Футболка спорт S1";;"<p>Футболка - сочетание комфорта и стиля. Сочный красный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.</p>";"1490";"futbolka-sport-s1";"1364754670.jpg|1364754799.jpg|1364754612.jpg|1364754870.jpg|1364756694.jpg";"FUBL1";"12";"1";"Футболка спорт S1";"Футболка спорт S1";"Футболка - сочетание комфорта и стиля. Сочный красный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.";"";"1";"0";"5"
"Одежда/Для женщин";"Футболка спорт S3";;"<p>Футболка - сочетание комфорта и стиля. Сочный синий цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.</p>";"1490";"futbolka-sport-s3";"1364754870.jpg|1364754799.jpg|1364756694.jpg|1364754670.jpg|1364754612.jpg";"FUBL2";"-1";"1";"Футболка спорт S3";"Футболка спорт S3";"Футболка - сочетание комфорта и стиля. Сочный красный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.";"1700";"0";"0";"6"
"Одежда/Для женщин";"Футболка спорт W1";;"<p>Футболка - сочетание комфорта и стиля. Сочный розовый&nbsp;цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.</p>";"1490";"futbolka-sport-w1";"1364754799.jpg|1364754670.jpg|1364754612.jpg|1364754870.jpg|1364756694.jpg";"FUBW1";"30";"1";"Футболка спорт W1";"Футболка спорт W1";"Футболка - сочетание комфорта и стиля. Сочный красный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.";"1700";"0";"0";"7"
"Одежда/Для мужчин";"Толстовка B2";;"<p>Толстовка - сочетание комфорта и стиля. Сочный розовый&nbsp;цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.</p>";"1490";"tolstovka-b2";"13647546911379243079.png|13647546911379243096.jpg|13647546911379242935.png|1364754691.png|1364754714.jpg";"FUBW3";"30";"1";"Толстовка B2";"Толстовка B2";"Толстовка - сочетание комфорта и стиля. Сочный розовый цвет притягивает внимание. Прямой крой не сковывает движений.";"";"0";"0";"8"
"Одежда/Для мужчин";"Толстовка B3";Без рисунка;"<p>Толстовка - сочетание комфорта и стиля. Сочный синий цвет притягивает внимание. Прямой крой не сковывает движений.&nbsp;&nbsp;</p><p><strong>Доступно нескололько вариантов толстовок!</strong></p>";"1490";"tolstovka-b3";"13647546911379243096.jpg|13647546911379243079.png|13647546911379242935.png|1364754691.png|1364754714.jpg";"FUBL2";"-1";"1";"Толстовка B3";"Толстовка B3";"Толстовка - сочетание комфорта и стиля. Сочный синий цвет притягивает внимание. Прямой крой не сковывает движений.";"";"1";"0";"0"
"Одежда/Для мужчин";"Толстовка B3";С рисунком;"<p>Толстовка - сочетание комфорта и стиля. Сочный синий цвет притягивает внимание. Прямой крой не сковывает движений.&nbsp;&nbsp;</p><p><strong>Доступно нескололько вариантов толстовок!</strong></p>";"1390";"tolstovka-b3";"13647546911379243096.jpg|13647546911379243079.png|13647546911379242935.png|1364754691.png|1364754714.jpg";"FUBL4";"-1";"1";"Толстовка B3";"Толстовка B3";"Толстовка - сочетание комфорта и стиля. Сочный синий цвет притягивает внимание. Прямой крой не сковывает движений.";"";"1";"0";"0"
"Одежда/Для мужчин";"Толстовка B4";;"<p>Толстовка - сочетание комфорта и стиля. Сочный синий цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.</p>";"1490";"tolstovka-b4";"1364754691.png|13647546911379243096.jpg|13647546911379243079.png|13647546911379242935.png|1364754714.jpg";"FUBW3";"10";"1";"Толстовка B4";"Толстовка B4";"Футболка - сочетание комфорта и стиля. Сочный красный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.";"";"0";"0";"10"
"Одежда/Для мужчин";"Толстовка B5";;"<p>Толстовка - сочетание комфорта и стиля. Сочный черный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.</p>";"1490";"tolstovka-b5";"1364754714.jpg|1364754691.png|13647546911379243096.jpg|13647546911379243079.png|13647546911379242935.png";"FUBB5";"10";"1";"Толстовка B5";"Толстовка B5";"Толстовка - сочетание комфорта и стиля. Сочный черный цвет притягивает внимание. Прямой крой не сковывает движений. Натуральный трикотаж позволяет кожу дышать, даря чувство комфорта. Приведенные измерения соответствуют размеру M.";"";"0";"1";"11"
"Женские сумки";"Сумка RT1";;"<p>Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный</p>";"2990";"sumka-rt1";"1364756525.jpg|1364756319.jpg|1364756351.jpg";"RT1";"7";"1";"Сумка RT1";"Сумка RT1";"Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный";"5000";"0";"0";"12"
"Женские сумки";"Сумка RT2";Без аксессуара;"<p>Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный</p>";"3990";"sumka-rt2";"1364756319.jpg|1364756525.jpg|1364756351.jpg";"RT2";"7";"1";"Сумка RT2";"Сумка RT2";"Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный";"6000";"1";"0";"0"
"Женские сумки";"Сумка RT2";С аксессуаром;"<p>Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный</p>";"4230";"sumka-rt2";"1364756319.jpg|1364756525.jpg|1364756351.jpg";"RTF2";"-1";"1";"Сумка RT2";"Сумка RT2";"Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный";"7000";"1";"0";"0"
"Женские сумки";"Сумка RT4";;"<p>Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный</p>";"2990";"sumka-rt4";"1364756351.jpg|1364756525.jpg|1364756319.jpg";"RT4";"7";"1";"Сумка RT4";"Сумка RT4";"Сумка большого размера с одним отделением на молнии.На задней стенке один горизонтальный карман на молнии.Подкладка из качественного синтетического материала.Внутри накладной средник на молнии,два кармана,один из которых на молнии и под мобильный";"5000";"0";"1";"14"
"Сборный товар";"Пицца";Маленькая;"<p><strong>Собери свою пицу на любой вкус!</strong></p><p>Пицца (итал. pizza от итал. pizzicare быть острым) &mdash; итальянское национальное блюдо в виде круглой открытой лепешки, покрытой в классическом варианте томатами и расплавленным сыром.</p>";"200";"pizza";"1302360827_835pizza.jpg";"P_SMAL";"-1";"1";"Пицца";"Пицца";"Пицца (итал. pizza от итал. pizzicare быть острым) — итальянское национальное блюдо в виде круглой открытой лепешки, покрытой в классическом варианте томатами и расплавленным сыром.";"";"0";"1";"0"
"Сборный товар";"Пицца";Средняя;"<p><strong>Собери свою пицу на любой вкус!</strong></p><p>Пицца (итал. pizza от итал. pizzicare быть острым) &mdash; итальянское национальное блюдо в виде круглой открытой лепешки, покрытой в классическом варианте томатами и расплавленным сыром.</p>";"300";"pizza";"1302360827_835pizza.jpg";"P_MID";"-1";"1";"Пицца";"Пицца";"Пицца (итал. pizza от итал. pizzicare быть острым) — итальянское национальное блюдо в виде круглой открытой лепешки, покрытой в классическом варианте томатами и расплавленным сыром.";"";"0";"1";"0"
"Сборный товар";"Пицца";Большая;"<p><strong>Собери свою пицу на любой вкус!</strong></p><p>Пицца (итал. pizza от итал. pizzicare быть острым) &mdash; итальянское национальное блюдо в виде круглой открытой лепешки, покрытой в классическом варианте томатами и расплавленным сыром.</p>";"500";"pizza";"1302360827_835pizza.jpg";"P_BIG";"-1";"1";"Пицца";"Пицца";"Пицца (итал. pizza от итал. pizzicare быть острым) — итальянское национальное блюдо в виде круглой открытой лепешки, покрытой в классическом варианте томатами и расплавленным сыром.";"";"0";"1";"0"';

    echo iconv("UTF-8", "WINDOWS-1251", $csvText);
    exit;
  }

}

