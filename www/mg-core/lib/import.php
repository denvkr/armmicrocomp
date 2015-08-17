<?php

/**
 * Класс Import - предназначен для импорта товаров в каталог магазина.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Import {

  public function __construct() {
    
  }

  /**
   * Запускает загрузку товаров с заданной строки
   * @param type $rowId - id строки для старта
   * @return type
   */
  public function startUpload($rowId = false) {

    if (!$rowId) {
      $rowId = 1;
    }
    if (empty($_SESSION['stopProcessImportCsv'])) {

      $data = $this->importFromCsv($rowId);

      return
        array(
          'percent' => $data['percent'],
          'status' => 'run',
          'rowId' => $data['rowId']
        // 'rowId' => 100
      );
    } else {
      unset($_SESSION['stopProcessImportCsv']);
      return
        array(
          'percent' => 0,
          'status' => 'canseled',
          'rowId' => $rowId
      );
    }
  }

  /**
   * Останавливает процесс импорта.
   * @return type
   */
  public function stopProcess() {
    $_SESSION['stopProcessImportCsv'] = true;
  }

  /**
   * Вычисляет разделитель в CSV файле.
   * @return type
   */
  public function getDelimetr() {
    $delimert = ';';
    return $delimert;
  }

  public function importFromCsv($rowId) {
    $startTimeSql = microtime(true);
    $delimert = $this->getDelimetr();

    $file = new SplFileObject("uploads/importCatalog.csv");
    if ($rowId === 1 || empty($rowId)) {
      $rowId = 0;
    }
    $file->seek($rowId);
    while (!$file->eof()) {

      $data = $file->fgetcsv(";");

      if ($rowId === 0) {
        $rowId = 1;
        continue;
      }

      if ((microtime(true) - $startTimeSql) > 3) {
        break;
      }

      $itemsIn = array(
        'cat_id' => iconv("WINDOWS-1251", "UTF-8", trim($data[0])),
        'title' => iconv("WINDOWS-1251", "UTF-8", trim($data[1])),
        'variant' => iconv("WINDOWS-1251", "UTF-8", trim($data[2])),
        'description' => iconv("WINDOWS-1251", "UTF-8", trim($data[3])),
        'price' => trim($data[4]),
        'url' => trim($data[5]),
        'image_url' => trim($data[6]),
        'code' => iconv("WINDOWS-1251", "UTF-8", trim($data[7])),
        'count' => trim($data[8]),
        'activity' => trim($data[9]),
        'meta_title' => iconv("WINDOWS-1251", "UTF-8", trim($data[10])),
        'meta_keywords' => iconv("WINDOWS-1251", "UTF-8", trim($data[11])),
        'meta_desc' => iconv("WINDOWS-1251", "UTF-8", trim($data[12])),
        'old_price' => trim($data[13]),
        'recommend' => trim($data[14]),
        'new' => trim($data[15]),
        'sort' => trim($data[16])
      );

      $this->prepareLineCsv($itemsIn);
      $rowId++;
    }

    $file = null;

    $percent100 = count(file("uploads/importCatalog.csv"));
    $percent = $rowId;
    $percent = ($percent * 100) / $percent100;

    $data = array(
      'rowId' => $rowId,
      'percent' => floor($percent),
    );

    return $data;
  }

  /**
   * Парсит категории, создает их и продлукт.
   * @param type $itemsIn
   */
  public function prepareLineCsv($itemsIn) {
    $categories = $this->parseCategoryPath($itemsIn['cat_id']);
    $catId = $this->createCategory($categories);

    $this->createProduct($itemsIn);
    // вычисляем  ID категории если она есть
  }

  /**
   * Создает продукт в БД если их небыло
   * @param type $product - массив с данными о продукту
   */
  public function createProduct($product) {

    $variant = $product['variant'];
    unset($product['variant']);
    // 1 находим ID категории по заданному пути   
    $product['cat_id'] = MG::translitIt($product['cat_id'], 1);
    $product['cat_id'] = URL::prepareUrl($product['cat_id']);

    if ($product['cat_id']) {
      $url = URL::parsePageUrl($product['cat_id']);
      $parentUrl = URL::parseParentUrl($product['cat_id']);
      $parentUrl = $parentUrl != '/' ? $parentUrl : '';
      $cat = MG::get('category')->getCategoryByUrl(
        $url, $parentUrl
      );
      $product['cat_id'] = $cat['id'];
    }

    $product['cat_id'] = !empty($product['cat_id']) ? $product['cat_id'] : 0;


    // 2 если URL не задан в файле, то транслитирируем его из названия товара
    $product['url'] = !empty($product['url']) ? $product['url'] : MG::translitIt($product['url'], 1);
    $product['url'] = URL::prepareUrl($product['url']);

    $model = new Models_Product();

    if ($product['cat_id'] == 0) {
      $alreadyProduct = $model->getProductByUrl($product['url']);
    } else {
      $alreadyProduct = $model->getProductByUrl($product['url'], $product['cat_id']);
    }

    // если в базе найден этот продукт, то при обновлении будет сохранен ID и URL 
    if (!empty($alreadyProduct['id'])) {

      $product['id'] = $alreadyProduct['id'];
      $product['url'] = $alreadyProduct['url'];
    }

    // обновляем товар, если его небыло то метод вернет массив с параметрами вновь созданного товара, в том числе и ID. Иначе  вернет true 

    $arrProd = $model->updateProduct($product);
    if (!$variant) {
      return true;
    }

    $product_id = $product['id'] ? $product['id'] : $arrProd['id'];

    $var = $model->getVariants($product['id'], $variant);
    // viewData($var);
    $varUpdate = null;
    foreach ($var as $k => $v) {
      if ($v['title_variant'] == $variant && $v['product_id'] == $product_id) {
        $varUpdate = $v['id'];
      }
    }
    // иначе обновляем существующую запись в таблице вариантов

    $newVariant = array(
      'product_id' => $product_id,
      'title_variant' => $variant,
      'image' => '',
      'sort' => '',
      'price' => $product['price'],
      'old_price' => $product['old_price'],
      'count' => $product['count'],
      'code' => $product['code'],
      'activity' => $product['activity']
    );
    $model->importUpdateProductVariant($varUpdate, $newVariant, $product_id);
    // }
  }

  /**
   * Создает категории в БД если их небыло.
   * @param type $categories - массив категорий полученый из записи вида категория/субкатегория/субкатегория2
   */
  public function createCategory($categories) {

    foreach ($categories as $category) {

      $category['parent_url'] = $category['parent_url'] != '/' ? $category['parent_url'] : '';

      if ($category['parent_url']) {
        $pUrl = URL::parsePageUrl($category['parent_url']);
        $parentUrl = URL::parseParentUrl($category['parent_url']);
        $parentUrl = $parentUrl != '/' ? $parentUrl : '';
      } else {
        $pUrl = $category['url'];
        $parentUrl = $category['parent_url'];
      }

      // вычисляем  ID родительской категории если она есть
      $alreadyParentCat = MG::get('category')->getCategoryByUrl(
        $pUrl, $parentUrl
      );

      // если нашлась  ID родительская категория назначаем parentID для новой
      if (!empty($alreadyParentCat)) {
        $category['parent'] = $alreadyParentCat['id'];
      }

      // проверяем, вдруг такая категория уже существует
      $alreadyExist = MG::get('category')->getCategoryByUrl(
        $category['url'], $category['parent_url']
      );

      if (!empty($alreadyExist)) {
        $category = $alreadyExist;
      }

      MG::get('category')->updateCategory($category);
    }
  }

  /**
   * Парсит путь категории возвращает набор категорий.
   * @param type $itemsIn
   */
  public function parseCategoryPath($path) {

    $i = 1;

    $categories = array();
    if (!$path) {
      return $categories;
    }

    $parent = $path;
    $parentTranslit = MG::translitIt($parent, 1);
    $parentTranslit = URL::prepareUrl($parentTranslit);

    $categories[$parent]['title'] = URL::parsePageUrl($parent);
    $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
    $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
    $categories[$parent]['parent'] = 0;

    while ($parent != '/') {
      $parent = URL::parseParentUrl($parent);
      $parentTranslit = MG::translitIt($parent, 1);
      $parentTranslit = URL::prepareUrl($parentTranslit);
      if ($parent != '/') {
        $categories[$parent]['title'] = URL::parsePageUrl($parent);
        $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
        $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
        $categories[$parent]['parent_url'] = $categories[$parent]['parent_url'] != '/' ? $categories[$parent]['parent_url'] : '';
        $categories[$parent]['parent'] = 0;
      }
    }

    $categories = array_reverse($categories);

    return $categories;
  }

}

