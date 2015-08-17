<?php
/**
 *
 * Раздел управления пользователями.
 * Позволяет управлять учетными записями пользователей.
 *
 * @autor Авдеев Марк <mark-avdeev@mail.ru>
 */


$page=!empty($_POST["page"])?$_POST["page"]:0;//если был произведен запрос другой страницы, то присваиваем переменной новый индекс

$countPrintRowsUser = MG::getSetting('countPrintRowsUser');

$navigator = new Navigator("SELECT  *  FROM `".PREFIX."user`", $page, $countPrintRowsUser); //определяем класс
$users = $navigator->getRowsSql();

$this->accessStatus = USER::$accessStatus;
$this->groupName = USER::$groupName;

$this->users = $navigator->getRowsSql();
$this->pagination = $navigator->getPager('forAjax');
$this->countPrintRowsUser = $countPrintRowsUser;
$res=DB::query("SELECT id  FROM `".PREFIX."user`"); 
$count=DB::numRows($res);
$this->usersCount = $count;
