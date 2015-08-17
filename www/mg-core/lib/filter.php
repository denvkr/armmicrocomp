<?php

/**
 * Класс Filter - конструктор для фильтров в адинке.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Filter{

  // Массив категрорий.
  private $categories;
  private $property;

  public function __construct($property){

    $this->property = $property;
  }

  /**
   * Получает примерно такой массив
   *  $array = array(
   *    'category' => '2',
   *    'price'=>array(10,100),
   *    'code'=> 'ABC',
   *    'rows'=> 20,
   *  );
   * @param type $data - массив параметров по фильтрам
   * @param type $sorter - массви содержищий поле, и направление сортировки
   *              $sorter = array('id', 'asc' );
   * по которому следует отсортировать выборку например ID и направление сортировки
   * 
   * @param bool $insideCat - учитывать вложенные категории или нет
   * @return string - часть запроса  WHERE
   */
   public function getFilterSql($data, $sorter=array(), $insideCat=true){
    
    return false;

   }
  /**
   * 
   *
   * @param array $array  массив с даннми категории.
   * @return int|bool id новой категории.
   */
  public function getHtmlFilter(){
    $html = '';

    foreach($this->property as $name => $prop){
      switch($prop['type']){
        case 'select':{
          
            $html .= '<div class="filter-select"><div class="select">'.$prop['label'].': <select name="'.$name.'" class="last-items-dropdown">';
            foreach($prop['option'] as $value => $text){              
              $selected = ($prop['selected'] === $value."") ? 'selected="selected"' : '';
              $html .= '<option value="'.$value.'" '.$selected.'>'.$text.'</option>';
            }
            $html .= '</select></div>';
            if($name=='cat_id'){
              $checked = '';
              if($_POST['insideCat']){
                $checked = 'checked=checked';
              }
              $html .= '<div class="checkbox">Учитывать товары вложенных категорий<input type="checkbox"  name="insideCat" '.$checked.' /></div>';
            }
             $html .= '</div>';
            break;
          }

        case 'beetwen':{

         $html .=  '<div class="price-slider-wrapper">
                <ul class="price-slider-list">
                 <li><span>'.$prop['label1'].'</span><input type="text" id="minCost" class="price-input start-'.$prop['class'].'  price-input" data-fact-min="'.$prop['factMin'].'" name="'.$name.'[]" value="'.$prop['min'].'" /></li>
                 <li><span>'.$prop['label2'].'</span><input type="text" id="maxCost" class="price-input end-'.$prop['class'].'  price-input" data-fact-max="'.$prop['factMax'].'" name="'.$name.'[]" value="'.$prop['max'].'" /><span>'.MG::getSetting('currency').'</span></li>
                </ul>
                <div class="clear"></div>
                <div id="price-slider"></div>
              </div>';
  
            if(!empty($prop['special'])){
              $html .= '<input type="hidden"  name="'.$name.'[]" value="'.$prop['special'].'" />';
            }

            break;
        }

        case 'hidden':{
            $html .= ' <input type="hidden" name="'.$name.'" value="'.$prop['value'].'" class="price-input"/>';
            break;
        }
        
        case 'text':{
            $html .= $prop['label'].': <input type="text" name="'.$name.'" value="'.$prop['value'].'" class="price-input"/>';
            break;
        }


        default:
          break;
      }
    }

    $html.='<a class="filter-now"><span>Фильтровать</span></a>';

   // echo $html;
    return '<form name="filter" class="filter-form">'.$html.'</form>';
  }

}