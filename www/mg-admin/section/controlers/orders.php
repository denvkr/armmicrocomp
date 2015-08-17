<?php

/**
 * Страница управления заказами в админской части сайта.
 * Позволяет управлять заказами пользователей.
 *
 * @autor Авдеев Марк <mark-avdeev@mail.ru>
 */
$lang = MG::get('lang');
$model = new Models_Order;
$listStatus['null']='Не выбрано';
$ls = Models_Order::$status;
foreach ($ls as $key => $value) {
  $listStatus[$key] = $lang[$value];
}


$maxPrice = $model->getMaxPrice();
$minPrice = $model->getMinPrice();


$property = array(
  'id' => array(
    'type' => 'text',
    'label' => 'Номер заказа',
    'value' => !empty($_POST['id']) ? $_POST['id'] : null,
  ),
  
  'status_id' => array(
    'type' => 'select',
    'option' =>$listStatus,
    'selected' => (!empty($_POST['status_id'])||$_POST['status_id']==='0')?$_POST['status_id']:'null', // Выбранный пункт (сравнивается по значению)
    'label' => 'Статус'
  ),
 
  'summ' => array(
    'type' => 'beetwen', //Два текстовых инпута
    'label1' => 'Сумма заказа от',
    'label2' => 'до',
    'min' => !empty($_POST['summ'][0])?$_POST['summ'][0]:$minPrice,
    'max' => !empty($_POST['summ'][1])?$_POST['summ'][1]:$maxPrice,
    'factMin' => $minPrice,
    'factMax' => $maxPrice,
    'special' => 'date',
    'class' =>'price numericProtection'

  ),

  'sorter' => array(
    'type' => 'hidden', //текстовый инпут
    'label' => 'сортировка по полю',
    'value' => !empty($_POST['sorter'])?$_POST['sorter']:null,
  ),
);



if(isset($_POST['applyFilter'])){
  $property['applyFilter'] = array(
    'type' => 'hidden', //текстовый инпут
    'label' => 'флаг примения фильтров',
    'value' => 1,
  );
}

$filter = new Filter($property);

$arr = array(
    'o.id'=> !empty($_POST['id']) ? $_POST['id'] : null,
    'status_id'=> (!empty($_POST['status_id'])||$_POST['status_id']==='0')?$_POST['status_id']:'null',
    'summ+delivery_cost'=>array(!empty($_POST['summ'][0])?$_POST['summ'][0]:$minPrice,!empty($_POST['summ'][1])?$_POST['summ'][1]:$maxPrice),
 );

$userFilter = $filter->getFilterSql($arr, explode('|',$_POST['sorter']));

$sorterData = explode('|',$_POST['sorter']);

if($sorterData[1]>0){
  $sorterData[3] = 'desc';          
} else{
  $sorterData[3] = 'asc';   
}


$page=!empty($_POST["page"])?$_POST["page"]:0;//если был произведен запрос другой страницы, то присваиваем переменной новый индекс

$countPrintRowsOrder = MG::getSetting('countPrintRowsOrder');

if(empty($_POST['sorter'])){
    if(empty($userFilter)){ $userFilter .= ' 1=1 ';}
    $userFilter .= "  ORDER BY `add_date` DESC";
}


$sql = "
  SELECT  o.* ,u.sname, u.name FROM `".PREFIX."order` as o
  LEFT JOIN `".PREFIX."user` as u ON o.user_email = u.email
  WHERE ".$userFilter."
";

$navigator = new Navigator($sql, $page, $countPrintRowsOrder); //определяем класс
$orders = $navigator->getRowsSql();

// Десериализация строки в массив (состав заказа)
foreach($orders as $k=>$order){
  $orders[$k]['order_content'] = unserialize(stripslashes($order['order_content']));
}

$propertyOrder = MG::getOption('propertyOrder');
$propertyOrder = stripslashes($propertyOrder);
$propertyOrder = unserialize($propertyOrder);

$product = new Models_Product();
$exampleName = $product->getProductByUserFilter(' 1=1 LIMIT 0,1');
$ids =  array_keys($exampleName); 
$this->exampleName=$exampleName[$ids[0]]['title'];
$this->assocStatus = Models_Order::$status;
$this->assocStatusClass = array('get-paid', 'get-paid', 'paid', 'get-paid', 'dont-paid', 'paid'); // цветная подсветка статусов
$model = new Models_Order();
$this->assocDelivery = $model->getListDelivery();
$this->assocPay = $model->getListPayment();
$this->orders = $orders;
$this->pager = $navigator->getPager('forAjax');
$this->orderCount = $model->getOrderCount();
$this->countPrintRowsOrder = $countPrintRowsOrder;
$this->displayFilter=($_POST['status_id']!="null" && !empty($_POST['status_id']))||isset($_POST['refreshFilter'])||isset($_POST['applyFilter']); // так проверяем произошол ли запрос по фильтрам или нет
$this->filter = $filter->getHtmlFilter();
$this->sorterData = $sorterData;
$this->propertyOrder = $propertyOrder;


