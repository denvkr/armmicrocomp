<?php

/**
 *
 * Раздел управления настройками сайта позволяет внести данные, об администраторе
 * указать  номера электронных кошельков, и настроить почтовый шаблон
 *
 * @var $tablePage - переменная формирующая таблицу в HTML формате
 *
 * @autor Авдеев Марк <mark-avdeev@mail.ru>
 */
//$dir = SITE_DIR.ltrim(URL::getCutPath(), '/').'/mg-templates';
$dir = str_replace(DIRECTORY_SEPARATOR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers', '', dirname(__FILE__));
$dir .=	DIRECTORY_SEPARATOR."mg-templates";


$folderTemplate = scandir($dir);
$templates = array();
foreach($folderTemplate as $key => $foldername){
  if(!in_array($foldername, array(".", ".."))){
    if(file_exists($dir.'/'.$foldername.'/css/style.css')){
      $templates[] = $foldername;
    }
  }
}
$licenceKey = MG::getOption('licenceKey', false); //true

$mOrder = new Models_Order;

$deliveryArray = $mOrder->getDeliveryMethod();
//массив способов оплаты
$paymentArray = array();
$i = 1;
while($payment = $mOrder->getPaymentMethod($i)){
  if($i==7||$i==3||$i==6){
    $paymentArray[$i] = $payment;  
  }  
  $i++;
}
$paymentArray = array_reverse($paymentArray);
$this->data = array(
  'setting-shop' => array(
    'options' => array(
      'sitename' => MG::getOption('sitename', true),
      'adminEmail' => MG::getOption('adminEmail', true),
      'noReplyEmail' => MG::getOption('noReplyEmail', true),
      'templateName' => MG::getOption('templateName', true),
      'countСatalogProduct' => MG::getOption('countСatalogProduct', true),
      'countNewProduct' => MG::getOption('countNewProduct', true),
      'countRecomProduct' => MG::getOption('countRecomProduct', true),
      'countSaleProduct' => MG::getOption('countSaleProduct', true),      
      'mainPageIsCatalog' => MG::getOption('mainPageIsCatalog', true),
      'actionInCatalog' => MG::getOption('actionInCatalog', true),      
      'heightPreview' => MG::getOption('heightPreview', true),      
      'widthPreview' => MG::getOption('widthPreview', true),      
      'orderMessage' => MG::getOption('orderMessage', true),
      'currency' => MG::getOption('currency', true),
      'printRemInfo' => MG::getOption('printRemInfo', true),  
      'printProdNullRem' => MG::getOption('printProdNullRem', true), 
      'waterMark' => MG::getOption('waterMark', true),  
      'widgetCode' => MG::getOption('widgetCode', true),  
    ),
    'templates' => $templates
  ),
  'setting-system' => array(
    'options' => array(
      'downtime' => MG::getOption('downtime', true),
      'licenceKey' => $licenceKey,
    )
  ),
  'setting-template' => array(
    'files' => array(
      'template.php'=>'/template.php',
      'functions.php'=>'/functions.php',
      'template.php'=>'/template.php',
      'ajaxuser.php'=>'/ajaxuser.php',
      '404.php'=>'/404.php',
      'style.css'=>'/css/style.css',
      'script.js'=>'/js/script.js',      
      'cart.php'=>'/views/cart.php',
      'catalog.php'=>'/views/catalog.php',
      'enter.php'=>'/views/enter.php',
      'feedback.php'=>'/views/feedback.php',
      'forgotpass.php'=>'/views/forgotpass.php',
      'index.php'=>'/views/index.php',
      'personal.php'=>'/views/personal.php',
      'product.php'=>'/views/product.php',
      'registration.php'=>'/views/registration.php',
      'order.php'=>'/views/order.php',
      )
  ),
  'interface-settings' => array(
    'options' => array(
      'themeColor' => MG::getOption('themeColor', true),
      'themeBackground' => MG::getOption('themeBackground', true),
      'staticMenu' => MG::getOption('staticMenu', true),
    )
  ),
  'paymentMethod-settings' => array(
      'paymentArray' => $paymentArray,
  ),
  'deliveryMethod-settings' => array(
      'deliveryArray' => $deliveryArray,
  ),
   'numericFields' => array('countСatalogProduct','countNewProduct','countRecomProduct','countSaleProduct')
);


/**
 * Раздел управления системой
 *
 */
$downtime = MG::getOption('downtime');

if('Y' == $downtime){
  $checked = 'checked';
}

$this->checked = $checked;

if(!$checkLibs = MG::libExists()){
  $newVer = Updata::checkUpdata();
  $this->newVersionMsg = $newVer['msg'];
}else{
  
  foreach ($checkLibs as $message){
    $errorUpdata .= $message.'<br>';
  }
  $this->errorUpdata = $errorUpdata;
}

if(32 != strlen($licenceKey['value'])){  
  $this->updataDisabled = 'enabled';//'disabled'
  $this->updataOpacity = ''; //'opacity'
}
