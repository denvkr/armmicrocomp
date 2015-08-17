<?php
$lang = MG::get('lang');

$arrayCategories = MG::get('category')->getHierarchyCategory(0);

$listCategory['null'] = $lang['NO_SELECT'];
$lc = MG::get('category')->getTitleCategory($arrayCategories, URL::get('category_id'), true);

if(!empty($lc)){
  foreach ($lc as $key => $value) {
    $listCategory[$key] = $value;
  }
}


$model = new Models_Catalog;

$catalog = array();

$maxPrice = $model->getMaxPrice();
$minPrice = $model->getMinPrice();
$property = array(
  'cat_id' => array(
    'type' => 'select',
    'option' => $listCategory,
    'selected' => (!empty($_POST['cat_id'])||$_POST['cat_id']==='0') ? $_POST['cat_id'] : 'null', // Выбранный пункт (сравнивается по значению)
    'label' => $lang['CATEGORIES']
  ),
  'price' => array(
    'type' => 'beetwen', //Два текстовых инпута
    'label1' => $lang['PRICE_FROM'],
    'label2' => $lang['PRICE_TO'],
    'min' => !empty($_POST['price'][0]) ? $_POST['price'][0] : $minPrice,
    'max' => !empty($_POST['price'][1]) ? $_POST['price'][1] : $maxPrice,
    'factMin' => $minPrice,
    'factMax' => $maxPrice,
    'special' => 'date',
    'class' => 'price numericProtection'
  ),
  
  'activity' => array(
    'type' => 'select',
    'option' => array('null' => $lang['NO_SELECT'], '1' => $lang['ACTIVE'], '0' => $lang['NO_ACTIVE']),
    'selected' => !empty($_POST['activity']) ? $_POST['activity'] : 'null', // Выбранный пункт (сравнивается по значению)
    'label' => $lang['ACTIVITY']
  ),
  
  
  'sorter' => array(
    'type' => 'hidden', //текстовый инпут
    'label' => 'сортировка по полю',
    'value' => !empty($_POST['sorter']) ? $_POST['sorter'] : null,
  ),
);



if (isset($_POST['applyFilter'])) {
  $property['applyFilter'] = array(
    'type' => 'hidden', //текстовый инпут
    'label' => 'флаг примения фильтров',
    'value' => 1,
  );
}

$filter = new Filter($property);

$arr = array(
  'cat_id' => (!empty($_POST['cat_id'])||$_POST['cat_id']==='0') ? $_POST['cat_id'] : 'null',
  'p.price' => array(!empty($_POST['price'][0]) ? $_POST['price'][0] : $minPrice, !empty($_POST['price'][1]) ? $_POST['price'][1] : $maxPrice),
  'activity' => (isset($_POST['activity'])) ? $_POST['activity'] : 'null',
  //  'code' => !empty($_POST['code'])?$_POST['code']:null,
);

$userFilter = $filter->getFilterSql($arr, explode('|', $_POST['sorter']), $_POST['insideCat']);

$sorterData = explode('|', $_POST['sorter']);
if ($sorterData[1]>0) {
  $sorterData[3] = 'desc';
} else {
  $sorterData[3] = 'asc';
}

if(empty($_POST['sorter'])){
  if (empty($userFilter)){
    $userFilter = " 1=1 ORDER BY `sort` ASC";
  }else{
    $userFilter .= " ORDER BY `sort` ASC";
  }
}



//получаем все вложенные ID подкатегорий
$model->categoryId = MG::get('category')->getCategoryList(URL::get('category_id'));
//дописываем в массив id категорий, выбраную категорию
$model->categoryId[] = URL::get('category_id');

$countPrintRowsProduct = MG::getSetting('countPrintRowsProduct');

if (!empty($userFilter)) {
  $catalog = $model->getListByUserFilter($countPrintRowsProduct, $userFilter, true);
} else {
  $catalog = $model->getList($countPrintRowsProduct, true);  
}

//категории:

$listCategories = MG::get('category')->getCategoryTitleList();
$arrayCategories = $model->categoryId = MG::get('category')->getHierarchyCategory(0);

$categoriesOptions = MG::get('category')->getTitleCategory($arrayCategories, URL::get('category_id'));



$product = new Models_Product;
$this->productsCount = $product->getProductsCount();
$this->filter = $filter->getHtmlFilter();
//print_r($filter->getHtmlFilter());
$this->catalog = $catalog['catalogItems'];
$this->listCategories = $listCategories;
$this->categoriesOptions = $categoriesOptions;
$this->countPrintRowsProduct = $countPrintRowsProduct;
$this->pagination = $catalog['pager'];
$this->displayFilter = ($_POST['cat_id']!="null"&&!empty($_POST['cat_id']))||isset($_POST['refreshFilter'])||isset($_POST['applyFilter']); // так проверяем произошоk ли запрос по фильтрам или нет
$this->settings = MG::get('settings');
$this->sorterData = $sorterData;

