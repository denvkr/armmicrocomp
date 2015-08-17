-- phpMyAdmin SQL Dump
-- version 4.0.10.7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Июл 24 2015 г., 04:04
-- Версия сервера: 5.5.42-cll
-- Версия PHP: 5.4.31

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `micrpchi_mg`
--
CREATE DATABASE IF NOT EXISTS `micrpchi_mg` DEFAULT CHARACTER SET latin1 COLLATE latin1_general_ci;
USE `micrpchi_mg`;

-- --------------------------------------------------------

--
-- Структура таблицы `mg_category`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_category`;
CREATE TABLE IF NOT EXISTS `mg_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `parent_url` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL,
  `html_content` longtext NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_keywords` varchar(512) NOT NULL,
  `meta_desc` text NOT NULL,
  `invisible` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Не выводить в меню',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

--
-- Дамп данных таблицы `mg_category`
--

INSERT INTO `mg_category` (`id`, `title`, `url`, `parent`, `parent_url`, `sort`, `html_content`, `meta_title`, `meta_keywords`, `meta_desc`, `invisible`) VALUES
(11, 'Микрокомпьютеры', 'mikrokompyuteri', 0, '', 11, '<p>Микрокомпьютеры: Cubieboard,Cubietruck,Raspberry Pi,SP200S</p>\n', '', '', '', 0),
(12, 'Raspberry PI mod A', 'raspberry-pi-mod-a', 11, 'mikrokompyuteri/', 12, '<p>Raspberry PI mod A</p>\n', '', '', '', 0),
(13, 'Cubieboard 1', 'cubieboard1', 11, 'mikrokompyuteri/', 14, '<p>Cubieboard A10,Cubieboard 1</p>\n', '', '', '', 0),
(14, 'Cubietruck', 'cubietruck', 11, 'mikrokompyuteri/', 16, '<p>Cubietruck, Cubieboard 3</p>\n', '', '', '', 0),
(15, 'Raspberry PI mod B', 'raspberry-pi-mod-b', 11, 'mikrokompyuteri/', 13, '<p>Raspberry PI mod B</p>\n', '', '', '', 0),
(16, 'Cubieboard 2', 'cubieboard-2', 11, 'mikrokompyuteri/', 15, '<p>Cubieboard A20,Cubieboard 2</p>\n', '', '', '', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_category_user_property`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_category_user_property`;
CREATE TABLE IF NOT EXISTS `mg_category_user_property` (
  `category_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `mg_category_user_property`
--

INSERT INTO `mg_category_user_property` (`category_id`, `property_id`) VALUES
(1, 3),
(5, 2),
(4, 2),
(1, 2),
(0, 1),
(5, 7),
(4, 7),
(1, 7),
(6, 10),
(6, 11),
(2, 12),
(1, 13),
(4, 13),
(5, 13),
(2, 13),
(4, 3),
(5, 3),
(3, 4),
(10, 6);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_delivery`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_delivery`;
CREATE TABLE IF NOT EXISTS `mg_delivery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `cost` float NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `activity` int(1) NOT NULL DEFAULT '0',
  `free` float NOT NULL COMMENT 'Бесплатно от',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='таблица способов доставки товара' AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `mg_delivery`
--

INSERT INTO `mg_delivery` (`id`, `name`, `cost`, `description`, `activity`, `free`) VALUES
(1, 'Курьер', 200, 'Курьерская служба', 1, 3000),
(2, 'Почта', 200, 'Почта России', 1, 0),
(3, 'Без доставки', 0, 'Самовывоз', 1, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_delivery_payment_compare`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_delivery_payment_compare`;
CREATE TABLE IF NOT EXISTS `mg_delivery_payment_compare` (
  `payment_id` int(10) DEFAULT NULL,
  `delivery_id` int(10) DEFAULT NULL,
  `compare` int(1) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `mg_delivery_payment_compare`
--

INSERT INTO `mg_delivery_payment_compare` (`payment_id`, `delivery_id`, `compare`) VALUES
(1, 1, 1),
(5, 1, 1),
(2, 2, 1),
(3, 1, 1),
(1, 2, 1),
(2, 1, 1),
(3, 2, 1),
(4, 2, 1),
(4, 3, 1),
(3, 3, 1),
(2, 3, 1),
(1, 3, 1),
(4, 1, 1),
(5, 2, 1),
(6, 1, 1),
(6, 2, 1),
(6, 3, 1),
(5, 3, 1),
(7, 1, 1),
(7, 2, 1),
(7, 3, 1),
(8, 1, 1),
(8, 2, 1),
(8, 3, 1),
(3, 1, 1),
(6, 1, 1),
(7, 1, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_order`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_order`;
CREATE TABLE IF NOT EXISTS `mg_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `updata_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `add_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `close_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_email` varchar(50) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text,
  `summ` varchar(255) DEFAULT NULL COMMENT 'Общая сумма товаров в заказе ',
  `order_content` longtext,
  `delivery_id` int(11) unsigned DEFAULT NULL,
  `delivery_cost` float DEFAULT NULL COMMENT 'Стоимость доставки',
  `payment_id` int(11) DEFAULT NULL,
  `paided` int(1) NOT NULL DEFAULT '0',
  `status_id` int(11) DEFAULT NULL,
  `comment` text,
  `confirmation` varchar(50) DEFAULT NULL,
  `yur_info` text NOT NULL,
  `name_buyer` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `mg_order`
--

INSERT INTO `mg_order` (`id`, `updata_date`, `add_date`, `close_date`, `user_email`, `phone`, `address`, `summ`, `order_content`, `delivery_id`, `delivery_cost`, `payment_id`, `paided`, `status_id`, `comment`, `confirmation`, `yur_info`, `name_buyer`) VALUES
(1, '2014-02-26 08:11:27', '2014-02-26 08:11:27', '0000-00-00 00:00:00', 'krasavin_denis@mail.ru', '+7 (905) 514-24-23', '', '2400', 'a:1:{i:0;a:10:{s:2:\\"id\\";s:2:\\"17\\";s:4:\\"name\\";s:12:\\"Cubieboard 1\\";s:3:\\"url\\";s:40:\\"mikrokompyuteri/cubieboard1/cubieboard-1\\";s:4:\\"code\\";s:9:\\"А0000001\\";s:5:\\"price\\";s:4:\\"2400\\";s:5:\\"count\\";s:1:\\"1\\";s:8:\\"property\\";s:0:\\"\\";s:6:\\"coupon\\";N;s:8:\\"discount\\";i:0;s:4:\\"info\\";s:0:\\"\\";}}', 3, 0, 3, 0, 0, NULL, '$1$KzQcCM9e$2cy8mYK.gKRIV0PWZwmXO/', 'a:8:{s:7:\\"nameyur\\";s:0:\\"\\";s:6:\\"adress\\";s:0:\\"\\";s:3:\\"inn\\";s:0:\\"\\";s:3:\\"kpp\\";s:0:\\"\\";s:4:\\"bank\\";s:0:\\"\\";s:3:\\"bik\\";s:0:\\"\\";s:2:\\"ks\\";s:0:\\"\\";s:2:\\"rs\\";s:0:\\"\\";}', 'Denis'),
(2, '2014-06-22 11:07:37', '2014-06-22 11:07:37', '0000-00-00 00:00:00', 'krasavin_denis@mail.ru', '+7 (905) 514-24-23', '', '250', 'a:1:{i:0;a:10:{s:2:\\"id\\";s:2:\\"18\\";s:4:\\"name\\";s:54:\\"Плата расширения cubieboard - COM RS232\\";s:3:\\"url\\";s:54:\\"mikrokompyuteri/cubieboard1/cubieboard-board-com-rs232\\";s:4:\\"code\\";s:7:\\"0000002\\";s:5:\\"price\\";s:3:\\"250\\";s:5:\\"count\\";s:1:\\"1\\";s:8:\\"property\\";s:0:\\"\\";s:6:\\"coupon\\";N;s:8:\\"discount\\";i:0;s:4:\\"info\\";s:0:\\"\\";}}', 3, 0, 6, 0, 0, NULL, '$1$fcMzrZ5w$U0qdsoyxQ9A9SL7awxo.q0', 'a:8:{s:7:\\"nameyur\\";s:0:\\"\\";s:6:\\"adress\\";s:0:\\"\\";s:3:\\"inn\\";s:0:\\"\\";s:3:\\"kpp\\";s:0:\\"\\";s:4:\\"bank\\";s:0:\\"\\";s:3:\\"bik\\";s:0:\\"\\";s:2:\\"ks\\";s:0:\\"\\";s:2:\\"rs\\";s:0:\\"\\";}', 'Denis'),
(3, '2014-07-17 17:40:01', '2014-07-17 17:38:27', '0000-00-00 00:00:00', 'admin@armmicrocomp.com', '+7 (916) 141-21-80', 'test', '250', 'a:1:{i:0;a:10:{s:2:\\"id\\";s:2:\\"18\\";s:4:\\"name\\";s:54:\\"Плата расширения cubieboard - COM RS232\\";s:3:\\"url\\";s:54:\\"mikrokompyuteri/cubieboard1/cubieboard-board-com-rs232\\";s:4:\\"code\\";s:7:\\"0000002\\";s:5:\\"price\\";s:3:\\"250\\";s:5:\\"count\\";s:1:\\"1\\";s:8:\\"property\\";s:0:\\"\\";s:6:\\"coupon\\";N;s:8:\\"discount\\";i:0;s:4:\\"info\\";s:4:\\"test\\";}}', 3, 0, 6, 0, 1, NULL, '$1$SVApD53U$5RgN/2ch1dZgBqkUSbeIL1', 'a:8:{s:7:\\"nameyur\\";s:0:\\"\\";s:6:\\"adress\\";s:0:\\"\\";s:3:\\"inn\\";s:0:\\"\\";s:3:\\"kpp\\";s:0:\\"\\";s:4:\\"bank\\";s:0:\\"\\";s:3:\\"bik\\";s:0:\\"\\";s:2:\\"ks\\";s:0:\\"\\";s:2:\\"rs\\";s:0:\\"\\";}', 'Администратор');

-- --------------------------------------------------------

--
-- Структура таблицы `mg_page`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_page`;
CREATE TABLE IF NOT EXISTS `mg_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_url` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `html_content` text NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_keywords` varchar(512) NOT NULL,
  `meta_desc` text NOT NULL,
  `sort` int(11) NOT NULL,
  `print_in_menu` tinyint(4) NOT NULL DEFAULT '0',
  `invisible` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Не выводить в меню',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Дамп данных таблицы `mg_page`
--

INSERT INTO `mg_page` (`id`, `parent_url`, `parent`, `title`, `url`, `html_content`, `meta_title`, `meta_keywords`, `meta_desc`, `sort`, `print_in_menu`, `invisible`) VALUES
(1, '', 0, 'Главная', 'index', '<h3 style="text-align: center;">Добро пожаловать в наш интернет-магазин!</h3>\n\n<p>Магазин Armmicrocomp.com открыт в феврале 2014 г. Это не просто место где продается оборудование определенного типа, но своего рода стартап в котором основной упор сделан на заложенный в устройство функционал. Позиция авторов идеи заключается в том чтобы продать готовый продукт под ключ, который при минимальноим времени настройки готов к работе, не требует к себе внимания длительный срок, а в случае неисправности может быть легко восстановлен или заменен на аналогичный или даже больший по функционалу.</p>\n\n<p>Миникомпьютеры это действительно маленькие, практически незаметные устройства, которые тем не менее способны выполнять функции настольных компьютеров и даже серверов. Миниатюризация в современном мире идет не в ущерб скорости работы и функционалу. При это стоимость самого железа ничтожно мала по сравнению полноразмерным оборудованием.</p>\n\n<p>При этом сервисное обслуживание сведено к минимуму, его может выполнять как штатный системный администратор компании так и приходящий или даже удаленно работающий системный администратор.</p>\n\n<p>Все устройства контролируются через веб сервис так что даже руководитель может в любую минуту посмотреть на параметры работы устройства.</p>\n\n<p>В течении последующего времени мы предлагаем апгрейд устройств с повышением функционала за минимальную стоимость.</p>\n\n<p>Какие функции могут выполнять такие устройства? Для примера можно привести следующий перечень:</p>\n\n<p>файл сервер,почтовый сервер,веб сервер,файервол,прокси сервер, медиа сервер с раздачей контента, учет интернет трафика,контроллер устройств в умном доме, контроль видеонаблюдения.</p>\n', 'Главная', 'Главная', '', 5, 0, 0),
(2, '', 0, 'Доставка', 'dostavka', '<div><b>Условия доставки по Российской Федерации:</b></div>\n\n<div>&nbsp;Условия одинаковы для юридических и физических лиц;</div>\n\n<div>&nbsp;Доставка осуществляется через транспортные компании:</div>\n\n<blockquote style="margin: 0 0 0 40px;border: none;padding: 0px;">- Почта России</blockquote>\n\n<div></div>\n\n<div></div>\n', 'Доставка', 'Доставка', '', 2, 1, 0),
(3, '', 0, 'Обратная связь', 'feedback', '', 'Обратная связь', 'Обратная связь', '', 3, 1, 0),
(4, '', 0, 'Контакты', 'contacts', '<table border="0" cellpadding="1" cellspacing="1" id="vcard_info" style="height: 150px; width: 300px;">\n	<tbody>\n		<tr>\n			<td id="vcard_info_td">Микрокомпьютеры<br />\n			г. Москва, &nbsp;ул. Довженко, д. 8/1<br />\n			Телефон: +7 (916) 141-21-80<br />\n			Мы работаем ежедневно с 9:00 до 21:00<br />\n			почта: admin@armmicrocomp.com<br />\n			ICQ 344-360-162</td>\n		</tr>\n	</tbody>\n</table>\n\n<pre id="line1">\n<span>\n</span>[lang-select]\n</pre>\n', 'Контакты', 'Контакты', '', 4, 1, 0),
(5, '', 0, 'Каталог', 'catalog', '', 'Каталог', 'Каталог', '', 1, 1, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_payment`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_payment`;
CREATE TABLE IF NOT EXISTS `mg_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(512) NOT NULL,
  `activity` int(1) NOT NULL DEFAULT '0',
  `paramArray` varchar(512) DEFAULT NULL,
  `urlArray` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Дамп данных таблицы `mg_payment`
--

INSERT INTO `mg_payment` (`id`, `name`, `activity`, `paramArray`, `urlArray`) VALUES
(1, 'WebMoney', 1, '{"Номер кошелька":"","Секретный ключ":""}', '{"result URL:":"/payment?id=1&pay=result","success URL:":"/payment?id=1&pay=success","fail URL:":"/payment?id=1&pay=fail"}'),
(2, 'Яндекс.Деньги', 1, '{"Номер счета":""}', '{"result URL:":"/payment?id=2&pay=result","success URL:":"/payment?id=2&pay=success","fail URL:":"/payment?id=2&pay=fail"}'),
(3, 'Наложенный платеж', 1, '{"Примечание":""}', ''),
(4, 'Наличные (курьеру)', 1, '{"Примечание":""}', ''),
(5, 'ROBOKASSA', 1, '{"Логин":"","пароль 1":"","пароль 2":""}', '{"result URL:":"/payment?id=5&pay=result","success URL:":"/payment?id=5&pay=success","fail URL:":"/payment?id=5&pay=fail"}'),
(6, 'QIWI', 1, '{"Логин в Qiwi":"+79161412180","Пароль в Qiwi":"Postman1"}', '{"result URL:":"/payment?id=6&pay=result","success URL:":"/payment?id=6&pay=success","fail URL:":"/payment?id=6&pay=fail"}'),
(7, 'Оплата по реквизитам', 1, '{"Юридическое лицо":"", "ИНН":"","КПП":"", "Адрес":"", "Банк получателя":"", "БИК":"","Расчетный счет":"","Кор. счет":""}', ''),
(8, 'Интеркасса', 1, '{"Идентификатор кассы":""}', '{"result URL:":"/payment?id=8&pay=result","success URL:":"/payment?id=8&pay=success","fail URL:":"/payment?id=8&pay=fail"}');

-- --------------------------------------------------------

--
-- Структура таблицы `mg_plugins`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_plugins`;
CREATE TABLE IF NOT EXISTS `mg_plugins` (
  `folderName` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  UNIQUE KEY `name` (`folderName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `mg_plugins`
--

INSERT INTO `mg_plugins` (`folderName`, `active`) VALUES
('breadcrumbs', 1),
('yandex-share', 1),
('language', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_product`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_product`;
CREATE TABLE IF NOT EXISTS `mg_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sort` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` float NOT NULL,
  `url` varchar(255) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  `activity` tinyint(1) NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_keywords` varchar(512) NOT NULL,
  `meta_desc` text NOT NULL,
  `old_price` varchar(255) NOT NULL,
  `recommend` tinyint(4) NOT NULL DEFAULT '0',
  `new` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `SEARCHPROD` (`title`,`description`,`code`,`meta_title`,`meta_keywords`,`meta_desc`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

--
-- Дамп данных таблицы `mg_product`
--

INSERT INTO `mg_product` (`id`, `sort`, `cat_id`, `title`, `description`, `price`, `url`, `image_url`, `code`, `count`, `activity`, `meta_title`, `meta_keywords`, `meta_desc`, `old_price`, `recommend`, `new`) VALUES
(16, 16, 15, 'Raspberry Pi mod B', '<p><strong>Raspberry Pi модификация B</strong></p>\n\n<p><span style="font-size: small;"><span style="line-height: 27.0px;"><span style="font-size: 13.0px;">CPU: 700 MHz ARM1176JZF-S (ARM11 family)</span><br />\n<span style="font-size: 13.0px;">GPU: Broadcom VideoCore IV, OpenGL ES 2.0, 1080p30 h.264/MPEG-4 AVC high-profile decoder</span><br />\n<span style="font-size: 13.0px;">Memory (SDRAM): 512 Megabytes (MiB)</span><br />\n<span style="font-size: 13.0px;">Video outputs: Composite RCA, HDMI</span><br />\n<span style="font-size: 13.0px;">Audio outputs: 3.5 mm jack, HDMI</span><br />\n<span style="font-size: 13.0px;">Onboard storage: SD, MMC, SDIO card slot</span><br />\n<span style="font-size: 13.0px;">10/100 Ethernet RJ45 onboard network</span><br />\n<span style="font-size: 13.0px;">Storage via SD/ MMC/ SDIO card slot</span></span></span></p>\n\n<p>[yandex-share]</p>\n', 2400, 'raspberry-pi-mod-b', 'DSC03313-8001393865102.JPG', '6956665508884', 1, 1, '', '', '', '', 0, 0),
(17, 17, 13, 'Cubieboard 1', '<div class="description description-text">\n<div id="desc_text" itemprop="description">\n<p><strong>Cubieboard модификация 1:</strong></p>\n\n<p>1G ARM cortex-A8 processor, 256KB L2 cache</p>\n\n<p>Mali400, OpenGL ES GPU</p>\n\n<p>1GB DDR3 @ 480MHz</p>\n\n<p>HDMI 1080p HD output</p>\n\n<p>100M NIC</p>\n\n<p>4GB Nand Flash</p>\n\n<p>В комплекте сам PC, SATA шлейф, USB кабель, безкорпусное исполнение.</p>\n\n<p>[yandex-share]</p>\n</div>\n</div>\n', 2400, 'cubieboard-1', 'DSC03315-8001393865123.JPG', 'А0000001', 1, 1, '', '', '', '2700', 0, 0),
(18, 18, 13, 'Плата расширения cubieboard - COM RS232', '<p>Плата расширения cubieboard - COM RS232</p>\n\n<p>Преобразует сигнал последовательного интерфейса RS232 в USB интерфейз в комплекте идет кабель.<br />\n&nbsp;</p>\n', 250, 'cubieboard-board-com-rs232', '2014-05-14-16.28.36.jpg', '0000002', 3, 1, '', '', '', '', 0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_product_user_property`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_product_user_property`;
CREATE TABLE IF NOT EXISTS `mg_product_user_property` (
  `product_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `value` text NOT NULL,
  `product_margin` text NOT NULL COMMENT 'наценка продукта',
  `type_view` enum('checkbox','select','radiobutton','') NOT NULL DEFAULT 'select',
  KEY `product_id` (`product_id`),
  KEY `property_id` (`property_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Таблица пользовательских свойств продуктов';

--
-- Дамп данных таблицы `mg_product_user_property`
--

INSERT INTO `mg_product_user_property` (`product_id`, `property_id`, `value`, `product_margin`, `type_view`) VALUES
(18, 1, 'Китай', '', 'select'),
(17, 1, 'Китай', '', 'select'),
(16, 1, 'Китай', '', 'select');

-- --------------------------------------------------------

--
-- Структура таблицы `mg_product_variant`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_product_variant`;
CREATE TABLE IF NOT EXISTS `mg_product_variant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `title_variant` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL,
  `price` float NOT NULL,
  `old_price` varchar(255) NOT NULL,
  `count` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `activity` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `title_variant` (`title_variant`),
  FULLTEXT KEY `code` (`code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Структура таблицы `mg_property`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_property`;
CREATE TABLE IF NOT EXISTS `mg_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `default` text NOT NULL,
  `data` text NOT NULL,
  `all_category` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

--
-- Дамп данных таблицы `mg_property`
--

INSERT INTO `mg_property` (`id`, `name`, `type`, `default`, `data`, `all_category`) VALUES
(1, 'Страна производитель', 'string', 'Китай', 'Китай', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `mg_setting`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Май 10 2015 г., 20:11
-- Последняя проверка: Май 01 2015 г., 05:31
--

DROP TABLE IF EXISTS `mg_setting`;
CREATE TABLE IF NOT EXISTS `mg_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  `active` varchar(1) NOT NULL DEFAULT 'N',
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=36 ;

--
-- Дамп данных таблицы `mg_setting`
--

INSERT INTO `mg_setting` (`id`, `option`, `value`, `active`, `name`) VALUES
(1, 'sitename', 'www.armmicrocomp.com', 'Y', 'SITE_NAME'),
(2, 'adminEmail', 'admin@armmicrocomp.com', 'Y', 'EMAIL_ADMIN'),
(3, 'templateName', '.default', 'Y', 'SITE_TEMPLATE'),
(4, 'countСatalogProduct', '6', 'Y', 'CATALOG_COUNT_PAGE'),
(5, 'currency', 'руб.', 'Y', 'SETTING_CURRENCY'),
(6, 'staticMenu', 'true', 'N', 'SETTING_STATICMENU'),
(7, 'orderMessage', 'Оформлен заказ № #ORDER# на сайте #SITE#', 'Y', 'TPL_EMAIL_ORDER'),
(8, 'downtime', 'false', 'N', 'DOWNTIME_SITE'),
(9, 'currentVersion', '{"last":"v3.8.0","final":"v5.3.1","disc":"\\u041f\\u043b\\u0430\\u043d\\u043e\\u0432\\u044b\\u0439 \\u0440\\u0435\\u043b\\u0438\\u0437 \\u043e\\u0442 15 \\u044f\\u043d\\u0432\\u0430\\u0440\\u044f 2014 \\u0433.","author":"\\u041a\\u043e\\u043c\\u0430\\u043d\\u0434\\u0430 Moguta.CMS","dateActivateKey":"0000-00-00 00:00:00"}', 'N', 'INFO_CUR_VERSION'),
(10, 'timeLastUpdata', '1431288703', 'N', 'LASTTIME_UPDATE'),
(11, 'title', ' Лучший магазин | Moguta.CMS', 'N', 'SETTING_PAGE_TITLE'),
(12, 'countPrintRowsProduct', '10', 'Y', 'ADMIN_COUNT_PROD'),
(13, 'languageLocale', 'ru_RU', 'N', 'ADMIN_LANG_LOCALE'),
(14, 'countPrintRowsPage', '10', 'Y', 'ADMIN_COUNT_PAGE'),
(15, 'themeColor', 'blue-theme', 'N', 'ADMIN_THEM_COLOR'),
(16, 'themeBackground', 'bg_7', 'N', 'ADMIN_THEM_BG'),
(17, 'countPrintRowsOrder', '20', 'N', 'ADMIN_COUNT_ORDER'),
(18, 'countPrintRowsUser', '30', 'N', 'ADMIN_COUNT_USER'),
(19, 'licenceKey', '', 'N', 'LICENCE_KEY'),
(20, 'mainPageIsCatalog', 'true', 'N', 'SETTING_CAT_ON_INDEX'),
(21, 'countNewProduct', '5', 'N', 'COUNT_NEW_PROD'),
(22, 'countRecomProduct', '5', 'N', 'COUNT_RECOM_PROD'),
(23, 'countSaleProduct', '5', 'N', 'COUNT_SALE_PROD'),
(24, 'actionInCatalog', 'true', 'N', 'VIEW_OR_BUY'),
(25, 'printProdNullRem', 'true', 'N', 'PRINT_PROD_NULL_REM'),
(26, 'printRemInfo', 'true', 'N', 'PRINT_REM_INFO'),
(27, 'heightPreview', '150', 'Y', 'PREVIEW_HEIGHT'),
(28, 'widthPreview', '300', 'Y', 'PREVIEW_WIDTH'),
(29, 'heightSmallPreview', '50', 'N', 'PREVIEW_HEIGHT'),
(30, 'widthSmallPreview', '80', 'N', 'PREVIEW_WIDTH'),
(31, 'waterMark', 'false', 'N', 'WATERMARK'),
(32, 'widgetCode', '<!-- В это поле необходимо прописать код счетчика посещаемости Вашего сайта. Например, Яндекс.Метрика или Google analytics -->', 'N', 'WIDGETCODE'),
(33, 'noReplyEmail', 'admin@armmicrocomp.com', 'Y', 'NOREPLY_EMAIL'),
(34, 'dateActivateKey ', '0000-00-00 00:00:00', 'N', ''),
(35, 'enabledSiteEditor', 'false', 'N', '');

-- --------------------------------------------------------

--
-- Структура таблицы `mg_user`
--
-- Создание: Фев 10 2014 г., 10:10
-- Последнее обновление: Фев 17 2015 г., 15:53
-- Последняя проверка: Фев 17 2015 г., 15:53
--

DROP TABLE IF EXISTS `mg_user`;
CREATE TABLE IF NOT EXISTS `mg_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(30) DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `name` varchar(30) DEFAULT NULL,
  `sname` varchar(30) DEFAULT NULL,
  `address` text,
  `phone` varchar(50) DEFAULT NULL,
  `date_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blocked` int(1) NOT NULL DEFAULT '0',
  `restore` varchar(150) DEFAULT NULL,
  `activity` int(1) DEFAULT '0',
  `inn` text NOT NULL,
  `kpp` text NOT NULL,
  `nameyur` text NOT NULL,
  `adress` text NOT NULL,
  `bank` text NOT NULL,
  `bik` text NOT NULL,
  `ks` text NOT NULL,
  `rs` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `mg_user`
--

INSERT INTO `mg_user` (`id`, `email`, `pass`, `role`, `name`, `sname`, `address`, `phone`, `date_add`, `blocked`, `restore`, `activity`, `inn`, `kpp`, `nameyur`, `adress`, `bank`, `bik`, `ks`, `rs`) VALUES
(1, 'admin@armmicrocomp.com', '$1$XHcDlnCP$cOjsTZ6zHjokB50EPehUg/', 1, 'Администратор', NULL, NULL, NULL, '2014-02-10 10:10:44', 0, NULL, 1, '', '', '', '', '', '', '', ''),
(2, 'krasavin_denis@mail.ru', '$1$FujKBIdS$oExdphu7yylig8p7FIUAD0', 2, 'Denis', '', '', '', '2014-02-26 07:59:08', 0, '$1$B5V0BOWq$dPbmkF3HlgmoQ5ljZjTZb.', 1, '', '', '', '', '', '', '', '');
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
