<?php

/**
 *
 * Раздел статистика магазина.
 * Позволяет отследить динамику развития сайта.
 *
 * @autor Авдеев Марк <mark-avdeev@mail.ru>
 */

$model = new Models_Order;


$d = new DateTime(date("Y-m-d"));
$d->modify( 'first day of this month' );
$_POST['from_date_stat'] =  $d->format("d.m.Y");

$d->modify( 'first day of +1 month' );
$_POST['to_date_stat'] = $d->format("d.m.Y");

$this->data = $model->getStatisticPeriod($_POST['from_date_stat'],$_POST['to_date_stat']);

