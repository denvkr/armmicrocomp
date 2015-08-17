<?php
/*
  Plugin Name: Выбор языка страницы
  Description: На страницах появляется возможность выбрать язык информации. В разметкe страницы товара необходимо вставить шорт код: [lang-select]
  Author: Krasavin Denis
  Version: 1.0
 */
 
/* 
  Пример использования.
  В разметке страница товара необходимо вставить шорт код:
  [lang-select]
*/
function languageselect(){   
  $pagelang='<script type="text/javascript" src="mg-plugins/language/switch_lang.js"></script> <div class="language-init">
  <img id="rus_flag_img" src="mg-plugins/language/rus_flag.jpg" onmouseover="showborder(\'rus_flag_img\');" onmouseleave="hideborder(\'rus_flag_img\');" onclick="switch_lang(\'RU\');"/><img id="en_flag_img" src="mg-plugins/language/union-jack_flag.jpg" onmouseover="showborder(\'en_flag_img\');" onmouseleave="hideborder(\'en_flag_img\');" onclick="switch_lang(\'EN\');"/>
  </div>';
  return $pagelang;
}

mgAddShortcode('lang-select', 'languageselect');