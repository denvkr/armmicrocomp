<?php
/**
 * Контроллер Product
 *
 * Класс Controllers_Product обрабатывает действия пользователей на странице товара.
 * - Пересчитывает стоимость товара.
 * - Подготавливает форму для вариантов товара.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Product extends BaseController{

  function __construct(){
    
    $model = new Models_Product;    
    
    // Требуется только пересчет цены товара.
    if(!empty($_REQUEST['calcPrice'])){  
      $model->calcPrice();
      exit;
    }
    
    $settings = MG::get('settings');
    $product = $model->getProduct(URL::getQueryParametr('id')); 
    $product['meta_title'] = $product['meta_title'] ? $product['meta_title'] : $product['title'];    
    $product['currency'] = $settings['currency'];       
    $blockVariants=$model->getBlockVariants($product['id']);
      
    $propertyFormData=$model->createPropertyForm( $param = array(
      'id'=>$product['id'],
      'maxCount'=>$product['count'],
      'productUserFields'=>$product['thisUserFields'], 
      'action' => "/catalog", 
      'method' => "POST", 
      'ajax' => true,
      'blockedProp' => array(), 
      'noneAmount' => false, 
      'titleBtn' => "В корзину", 
      'blockVariants'=>$blockVariants
    ));   
    
    // Легкая форма без характеристик.   
    $liteFormData=$model->createPropertyForm( $param = array(
      'id'=>$product['id'],
      'maxCount'=>$product['count'],
      'productUserFields'=>null, 
      'action' => "/catalog", 
      'method' => "POST", 
      'ajax' => true,
      'blockedProp' => array(), 
      'noneAmount' => false, 
      'titleBtn' => "В корзину", 
      'blockVariants'=>$blockVariants
    ));  
   
    $product['price']+=$propertyFormData['marginPrice'];  
    $product['propertyForm'] = $propertyFormData['html']; 
    $product['liteFormData'] = $liteFormData['html'];   
    $product['description'] = MG::inlineEditor(PREFIX.'product',"description", $product['id'], $product['description']);
    $product['title'] = MG::modalEditor('catalog', $product['title'], 'edit', $product["id"]);
    $product["recommend"] = 0;
    $product["new"] = 0; 
    // Информация об отсутствии товара на складе.
    if(MG::getSetting('printRemInfo')=="true" && $product['count']==0){
      $product['remInfo']="<span class='rem-info'>Товара временно нет на складе!</style>";
    } 
    if($product['count']<0){ $product['count'] = "∞"; };  
    $this->data = $product;  
  }

}