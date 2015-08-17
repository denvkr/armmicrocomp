<?php
/**
 * Файл mg-start.php расположен в корне ядра, запускает движок и выводит на экран сгенерированную им страницу сайта.
 *
 * Инициализирует компоненты CMS, доступные из любой точки программы.
 * - DB - класс для работы с БД;
 * - MG - класс содердащий функционал системы;
 * - URL - класс для работы со ссылками;
 * - PM - класс для работы с плагинами.
 * - User - класс для работы с профайлами пользователей;
 * - Mailer - класс для отправки писем.
 * 
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Files
 */

// Если config.ini не существует, происходит попытка запустить инсталятор.
if(!MG::getConfigIni()){
  MG::instalMoguta();
}

// Инициализация компонентов CMS.

DB::init();
PM::init();
MG::init();
URL::init();
User::init();
Mailer::init();


if(MG::isDowntime()){
 /**
  * Если сайт временно закрыт, то выводитя заглушка, хранящаяся в корне двика.
  */
  require_once 'downTime.html';
  exit;
}

MG::logReffererInfo();

// Подключить index.php всех плагинов.
PM::includePlugins();

/**
 *  Хук выполняющийся до запуска движка.
 */
MG::createHook('mg_start');
MG::set('jQuery', true); // будет подключен jQuery

// Запуст движка.
$moguta = new Moguta;
$moguta = $moguta->run();

$getsysteminfo=MG::getOption('sitename');
$getadminEmail=MG::getOption('adminEmail');
$gettemplateName=MG::getOption('templateName');
$getcountСatalogProduct=MG::getOption('countСatalogProduct');
$getcurrency=MG::getOption('currency');
$getstaticMenu=MG::getOption('staticMenu');
$getorderMessage=MG::getOption('orderMessage');
$getdowntime=MG::getOption('downtime');
$getcurrentVersion=MG::getOption('currentVersion');
$gettimeLastUpdata=MG::getOption('timeLastUpdata');
$gettitle=MG::getOption('title');
$getcountPrintRowsProduct=MG::getOption('countPrintRowsProduct');
$getlanguageLocale=MG::getOption('languageLocale');
$getcountPrintRowsPage=MG::getOption('countPrintRowsPage');
$getthemeColor=MG::getOption('themeColor');
$getthemeBackground=MG::getOption('themeBackground');
$getcountPrintRowsOrder=MG::getOption('countPrintRowsOrder');
$getcountPrintRowsUser=MG::getOption('countPrintRowsUser');
$getlicenceKey=MG::getOption('licenceKey');
$getmainPageIsCatalog=MG::getOption('mainPageIsCatalog');
$getcountNewProduct=MG::getOption('countNewProduct');
$getcountRecomProduct=MG::getOption('countRecomProduct');
$getcountSaleProduct=MG::getOption('countSaleProduct');
$getactionInCatalog=MG::getOption('actionInCatalog');
$getprintProdNullRem=MG::getOption('printProdNullRem');
$getprintRemInfo=MG::getOption('printRemInfo');
$getheightPreview=MG::getOption('heightPreview');
$getwidthPreview=MG::getOption('widthPreview');
$getheightSmallPreview=MG::getOption('heightSmallPreview');
$getwidthSmallPreview=MG::getOption('widthSmallPreview');
$getwaterMark=MG::getOption('waterMark');
$getwidgetCode=MG::getOption('widgetCode');
$getnoReplyEmail=MG::getOption('noReplyEmail');
$getdateActivateKey=MG::getOption('dateActivateKey');
$getenabledSiteEditor=MG::getOption('enabledSiteEditor');


if (file_exists('siteinfo.log')) {
      $fp = fopen('siteinfo.log', "w");
   } else {
      $fp = fopen('siteinfo.log', "x+");
   }
fwrite($fp,$getsysteminfo."\r\n");
fwrite($fp,$getadminEmail."\r\n");
fwrite($fp,$gettemplateName."\r\n");
fwrite($fp,$getcountСatalogProduct."\r\n");
fwrite($fp,$getcurrency."\r\n");
fwrite($fp,$getstaticMenu."\r\n");
fwrite($fp,$getorderMessage."\r\n");
fwrite($fp,$getdowntime."\r\n");
fwrite($fp,$getcurrentVersion."\r\n");
fwrite($fp,$gettimeLastUpdata."\r\n");
fwrite($fp,$gettitle."\r\n");
fwrite($fp,$getcountPrintRowsProduct."\r\n");
fwrite($fp,$getlanguageLocale."\r\n");
fwrite($fp,$getcountPrintRowsPage."\r\n");
fwrite($fp,$getthemeColor."\r\n");
fwrite($fp,$getthemeBackground."\r\n");
fwrite($fp,$getcountPrintRowsOrder."\r\n");
fwrite($fp,$getcountPrintRowsUser."\r\n");
fwrite($fp,$getlicenceKey."\r\n");
fwrite($fp,$getmainPageIsCatalog."\r\n");
fwrite($fp,$getprintProdNullRem."\r\n");
fwrite($fp,$getprintRemInfo."\r\n");
fwrite($fp,$getheightPreview."\r\n");
fwrite($fp,$getheightSmallPreview."\r\n");
fwrite($fp,$getwaterMark."\r\n");
fwrite($fp,$getwidgetCode."\r\n");
fwrite($fp,$getnoReplyEmail."\r\n");
fwrite($fp,$getdateActivateKey."\r\n");
fwrite($fp,$getenabledSiteEditor."\r\n");
fclose($fp);
// Вывод результата на экран, предварительно обработав все возможные шорткоды.
echo PM::doShortcode(MG::printGui($moguta));

// Хук выполняющийся после того как отработал движок.
MG::createHook('mg_end');

if(DEBUG_SQL){
  echo DB::console();
}

