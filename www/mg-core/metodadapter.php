<?php

/**
 * Файл metodadapter.php содержит набор функций, необходимых пользователям
 * для построения собственных скриптов. Все функции этого файла,
 * являются  алиасами, для аналогичных функций из класса MG.
 * Целью использования данного файла является исключение из пользовательских
 * файлов сложного для понимания синтаксиса MG::
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Files
 */

/**
 * Метод addAction Добавляет обработчик для заданного хука.
 *
 * @param  $hookName имя хука на который вешается обработчик.
 * @param  $userFunction пользовательская функци, которая сработает при объявлении хука.
 * @param  $countArg количество аргументов, которое ждет пользовательская функция.
 */
function mgAddAction($hookName, $userFunction, $countArg = 0, $priority = 10) {
  MG::addAction($hookName, $userFunction, $countArg, $priority);
}

/**
 * Метод addAction Добавляет обработчик шорткода .
 */
function mgAddShortcode($shortcode, $userFunction) {
  MG::addShortcode($shortcode, $userFunction);
}

/**
 * Добавляет обработчик для страницы плагина.
 * Назначенная в качестве обработчика пользовательская функция.
 * будет, отрисовывать страницу настроек плагина.
 *
 * @param  $plugin название папки в которой лежит плагин.
 * @param  $userFunction пользовательская функци,
 *         которая сработает при открытии страницы настроек данного плагина.
 */
function mgPageThisPlugin($plugin, $userFunction) {
  MG::addAction($plugin, $userFunction);
}

/**
 * Добавляет обработчик для активации плагина,
 * пользовательская функция будет срабатывать тогда когда
 * в панели администрирования будет активирован плагин.
 *
 * >Является необязательным атрибутом плагина, при отсутствии этого
 * обработчика плагин тоже будет работать.
 *
 * Функция обрабатывающя событие
 * не должна производить вывод (echo, print, print_r, var_dump), это нарушит
 * логику работы AJAX.
 *
 * @param  $dirPlugin директория в которой хранится плагин.
 * @param  $userFunction пользовательская функци, которая сработает при объявлении хука.
 */
function mgActivateThisPlugin($dirPlugin, $userFunction) {
  MG::activateThisPlugin($dirPlugin, $userFunction);
}

/**
 * Добавляет обработчик для ДЕактивации плагина,
 * пользовательская функция будет срабатывать тогда когда
 * в панели администрирования будет выключен  плагин.
 *
 * >Является необязательным атрибутом плагина, при отсутствии этого
 * обработчика плагин тоже будет работать.
 *
 * Функция обрабатывающя событие
 * не должна производить вывод (echo, print, print_r, var_dump), это нарушит
 * логику работы AJAX.
 *
 * @param  $dirPlugin директория в которой хранится плагин.
 * @param  $userFunction пользовательская функци, которая сработает при объявлении хука.
 */
function mgDeactivateThisPlugin($dirPlugin, $userFunction) {
  MG::deactivateThisPlugin($dirPlugin, $userFunction);
}

/**
 * Создает hook -  крючок, для  пользовательских функций и плагинов.
 * может быть вызван несколькими спообами:
 * 1. createHook('userFunction'); - в любом месте программы выполнится пользовательская функция userFunction() из плагина;
 * 2. createHook('userFunction', $args); - в любом месте программы выполнится пользовательская функция userFunction($args) из плагина с параметрами;
 * 3. return createHook('thisFunctionInUserEnviroment', $result, $args); - хук прописывается перед.
 *  возвращением результата какой либо функции,
 *  в качестве параметров передается результат работы текущей функции,
 *  и начальные параметры которые были переданы ей.
 *
 * @param array $arr параметры, которые надо защитить.
 * @return array $arr теже параметры, но уже безопасные.
 */
function mgCreateHook($hookName) {
  MG::createHook($hookName);
}

/**
 * Добавляет переданную строку в секцию <head> </head>
 *
 * @param string $data - строковая переменная, с данными.
 * Например:
 *   mgAddMeta('<script type="text/javascript" src="'.SITE.'/mg-plugins/category-menu/js/category-menu.js"> </script>'); - подключится для каждой страницы
 * 
 * @param mixed $pages - перечень url на которых должен быть подключен файл если $only=true
 * @param bool  $only ? если false то подключаться будет для всех кроме $pages,  если для $only=true то только для $pages
 * @param bool  $sectionMode - проверка на соответствие заданной секции
 * Подключится только для страницы http://localhost/coolmoguta/dostavka
 * mgAddMeta('<script type="text/javascript" src="'.SITE.'/mg-plugins/category-menu/js/category-menu.js"> </script>', array('http://localhost/coolmoguta/dostavka'));
 * 
 * 
 * Подключится только для всех кроме страницы http://localhost/coolmoguta/dostavka
 * mgAddMeta('<script type="text/javascript" src="'.SITE.'/mg-plugins/category-menu/js/category-menu.js"> </script>', array('http://localhost/coolmoguta/dostavka'), false  );


 * @return void.
 */
function mgAddMeta($data, $pages = array(), $only = true, $sectionMode=false) {
  $register = MG::get('register') ? MG::get('register') : array();
  $add = false;
  if (!empty($pages)) {
    // если нужно вывесли по условию только для заданных страниц
    if ($only) {
      if (in_array(URL::getUrl(), $pages)) {
        $add = true;
      }
    }

    // если нужно вывесли по условию для всех кроме заданных страниц
    if (!$only) {
      if (!in_array(URL::getUrl(), $pages)) {
        $add = true;
      }
    }
  } else {
    $add = true;
  }

  if($add){
    if (!in_array($data, $register)) {
      $register[] = $data;
      MG::set('register', $register);
      MG::set('userMeta', MG::get('userMeta')."\n".$data);
    }  
  }

}

/**
 * Устанавливает значение для опции (настройки).
 * @param array $data -  может содержать значения для полей таблицы.
 * <code>
 * $data = array(
 *   option => 'идентификатор опции например: sitename'
 *   value  => 'значение опции например: moguta.ru'
 *   active => 'в будущем будет отвечать за автоподгрузку опций в кеш Y/N'
 *   name => 'Метка для опции например: Имя сайта'
 *   desc => 'Описание опции: Настройа задает имя для сайта'
 * )
 * </code>
 * @return void
 */
function setOption($data) {
  // Если функция вызвана вот так: setOption('option', 'value');
  if (func_num_args()==2) {
    $arg = func_get_args();
    $data = array();
    $data['option'] = $arg[0];
    $data['value'] = $arg[1];
  }
  MG::setOption($data);
}

/**
 * Возвращает значение для запрошенной опции (настройки).
 * имеет два режима:
 * 1. getOption('optionName') - вернет только значени;
 * 2. getOption('optionName' , true) - вернет всю информацию об опции в
 * виде массива.
 * <code>
 * $data = array(
 *   option => 'идентификатор опции например: sitename'
 *   value  => 'значение опции например: moguta.ru'
 *   active => 'в будущем будет отвечать за автоподгрузку опций в кеш Y/N'
 *   name => 'Метка для опции например: Имя сайта'
 *   desc => 'Описание опции: Настройа задает имя для сайта'
 * )
 * </code>
 * @return void
 */
function getOption($option, $data = false) {
  return MG::getOption($option, $data);
}

/**
 * Получить меню в HTML виде.
 * @return object - объект класса Menu.
 */
function mgMenu() {
  echo MG::getMenu();
}

/**
 * Получить полное меню в HTML виде.
 * @return object - объект класса Menu.
 */
function mgMenuFull() {
  echo MG::getMenu(true);
}
/**
 * Получить параметры маленькой корзины.
 * @return object - объект класса SmalCart.
 */
function mgGetCart() {
  return MG::getSmalCart();
}

/**
 * Устанавливает meta данные страницы.
 * @param string|bool $title заголовок страницы.
 * @return void.
 */
function mgMeta() {
  echo MG::meta();
}

/**
 * Устанавливает meta данные страницы title, description, keywords.
 * @param string|bool $title заголовок страницы.
 * @return void.
 */
function mgSEO($data) {
  MG::seoMeta($data);
}

/**
 * Задает заголовок страницы.
 * @return void
 */
function mgTitle($title) {
  MG::titlePage($title);
}

/**
 * Выводит содержимое массива на страницу
 * @return void
 */
function viewData($data) {
  echo "<pre>";
  echo htmlspecialchars(print_r($data, true));
  echo "</pre>";
}

/**
 * Склонение числительных
 * пример
 * echo 'Найдено '.mgDeclensionNum($data['searchData']['count'], array('товар', 'товара', 'товаров'))
 * @param type $number
 * @param type $titles
 * @return type
 */
function mgDeclensionNum($number, $titles) {
  return MG::declensionNum($number, $titles);
}

/**
 * Проверяет является ли страница статичной, созданной из панели администрирования
 * @return void
 */
function isStaticPage() {
 return MG::get('isStaticPage');  
}