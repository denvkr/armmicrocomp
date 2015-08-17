<?php

/**
 * Контроллер: Payment
 *
 * Класс Controllers_Payment обрабатывает действия пользователей связанные с оплатой заказа.
 * - Проводит двухшаговый процесс оплаты заказа с сервисами WebMoney, Robokassa, Qiwi, Interkassa;
 * - Автоматически меняет статус заказа при успешном подтверждении оплаты.
 * - Отправляет письмо админам, о проведенной операции.
 * 
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Payment extends BaseController {

  function __construct() {
    $paymentID = URL::getQueryParametr('id');
    $paymentStatus = URL::getQueryParametr('pay');
    $_POST['url'] = URL::getUrl();
    $modelOrder = new Models_Order();
    switch ($paymentID) {
      case 1: //webmoney
        $msg = $this->webmoney($paymentID, $paymentStatus);
        break;
      case 5: //robokassa
        $msg = $this->robokassa($paymentID, $paymentStatus);
        break;
      case 6: //qiwi
        $msg = $this->qiwi($paymentID, $paymentStatus);
        break;
      case 8: //interkassa
        $msg = $this->interkassa($paymentID, $paymentStatus);
        break;
      case 2: //ЯндексДеньги
        $modelOrder->sendMailOfPayed($_POST['operation_id'], $_POST['amount'], 2);
        break;
    }

    $this->data = array(
      'payment' => $paymentID, //id способа оплаты
      'status' => $paymentStatus, //статус ответа платежной системы (result, success, fail)
      'message' => $msg, //статус ответа платежной системы (result, success, fail)
    );
  }

  /**
   * Проверяет платеж через WebMoney.
   * @param $paymentID - id способа оплаты.
   * @param $paymentStatus - статус оплаты заказа.
   */
  public function webmoney($paymentID, $paymentStatus) {

    if ('success' == $paymentStatus) {
      $msg = 'Вы успешно оплатили заказ №'.$_POST['LMI_PAYMENT_NO'];
    } elseif ('result' == $paymentStatus && $_POST) {
      $order = new Models_Order();
      $paymentAmount = trim($_POST['LMI_PAYMENT_AMOUNT']);
      $paymentOrderId = trim($_POST['LMI_PAYMENT_NO']);
      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = ".DB::quote($paymentOrderId, 1)." and summ+delivery_cost = ".DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']);
      }
      $payeePurse = $paymentInfo[0]['value'];
      $secretKey = $paymentInfo[1]['value'];
      // Предварительная проверка платежа.
      if ($_POST['LMI_PREREQUEST'] == 1) {
        $error = false;

        if (empty($orderInfo)) {
          echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          exit;
        }

        if (trim($_POST['LMI_PAYEE_PURSE']) != $payeePurse) {
          echo "ERR: НЕВЕРНЫЙ КОШЕЛЕК ПОЛУЧАТЕЛЯ ".$_POST['LMI_PAYEE_PURSE'];
          exit;
        }
        echo "YES";
        exit;
      } else {
        // Проверка хеша, присвоение нового статуса заказу.
        $chkstring = $_POST['LMI_PAYEE_PURSE'].
          $_POST['LMI_PAYMENT_AMOUNT'].
          $_POST['LMI_PAYMENT_NO'].
          $_POST['LMI_MODE'].
          $_POST["LMI_SYS_INVS_NO"].
          $_POST["LMI_SYS_TRANS_NO"].
          $_POST["LMI_SYS_TRANS_DATE"].
          $secretKey.
          $_POST["LMI_PAYER_PURSE"].
          $_POST["LMI_PAYER_WM"];
        $md5sum = strtoupper(md5($chkstring));

        if ($_POST['LMI_HASH'] == $md5sum) {
          $order->updateOrder(array('id' => $paymentOrderId, 'status_id' => 2));
          $order->sendMailOfPayed($paymentOrderId, $paymentAmount, $paymentID);
          echo "YES";
          exit;
        } else {
          echo "ERR: Произошла ошибка или подмена параметров.";
          exit;
        }
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверяет платеж через ROBOKASSA.
   * @param $paymentID - id способа оплаты.
   * @param $paymentStatus - статус оплаты заказа.
   */
  public function robokassa($paymentID, $paymentStatus) {

    if ('success' == $paymentStatus) {
      $msg = 'Вы успешно оплатили заказ №'.$_POST['InvId'];
    } elseif ('result' == $paymentStatus && isset($_POST)) {
      $order = new Models_Order();
      $paymentAmount = trim($_POST['OutSum']);
      $paymentOrderId = trim($_POST['InvId']);
      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = ".DB::quote($paymentOrderId, 1)." and summ+delivery_cost = ".DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']);
      }
      // Предварительная проверка платежа.
      if (empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }

      $sMerchantPass2 = $paymentInfo[2]['value'];
      $sSignatureValue = $paymentAmount.':'.$paymentOrderId.':'.$sMerchantPass2;
      $md5sum = strtoupper(md5($sSignatureValue));

      if ($_POST['SignatureValue'] == $md5sum) {
        $order->updateOrder(array('id' => $paymentOrderId, 'status_id' => 2));
        $order->sendMailOfPayed($paymentOrderId, $paymentAmount, $paymentID);
        echo "OK".$paymentOrderId;
        exit;
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверяет платеж через QIWI.
   * @param $paymentID - id способа оплаты.
   * @param $paymentStatus - статус оплаты заказа.
   */
  public function qiwi($paymentID, $paymentStatus) {
    if ('success' == $paymentStatus) {
      $msg = 'Вы успешно оплатили заказ №'.$_GET['order'];
    } elseif ('result' == $paymentStatus && isset($_POST)) {
      $i = file_get_contents('php://input');

      $l = array('/<login>(.*)?<\/login>/', '/<password>(.*)?<\/password>/');
      $s = array('/<txn>(.*)?<\/txn>/', '/<status>(.*)?<\/status>/');

      preg_match($l[0], $i, $m1);
      preg_match($l[1], $i, $m2);

      preg_match($s[0], $i, $m3);
      preg_match($s[1], $i, $m4);

      $order = new Models_Order();
      $paymentOrderId = $m3[1];


      if (!empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = ".DB::quote($paymentOrderId, 1));
      } else {
        $orderInfo = NULL;
        echo "Ошибка обработки";
        exit();
      }

      $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']);
      $password = $paymentInfo[1]['value'];

      // Если заказа не существует то отправляем код 150.
      if (empty($orderInfo)) {
        $resultCode = 300;
      } else {

        $hash = strtoupper(md5($m3[1].strtoupper(md5($password))));

        if ($hash !== $m2[1]) { // Сравнение хэшей.
          $resultCode = 150;
        } else {

          $order->updateOrder(array('id' => $paymentOrderId, 'status_id' => 2));
          $order->sendMailOfPayed($paymentOrderId, $orderInfo['summ'], $paymentID);
          $resultCode = 0; // Все прошло успешно оправляем "0".
        }
      }

      header('content-type: text/xml; charset=UTF-8');
      echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://client.ishop.mw.ru/"><SOAP-ENV:Body><ns1:updateBillResponse><updateBillResult>'.$resultCode.'</updateBillResult></ns1:updateBillResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
      exit;
    }

    return $msg;
  }

  /**
   * Проверяет платеж через Interkassa.
   * @param $paymentID - id способа оплаты.
   * @param $paymentStatus - статус оплаты заказа.
   */
  public function interkassa($paymentID, $paymentStatus) {

    if ('success' == $paymentStatus) {
      $msg = 'Вы успешно оплатили заказ №'.$_POST['InvId'];
    } elseif ('result' == $paymentStatus && isset($_POST)) {
      $order = new Models_Order();
      $paymentAmount = trim($_POST['OutSum']);
      $paymentOrderId = trim($_POST['InvId']);

      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = ".DB::quote($paymentOrderId, 1)." and summ+delivery_cost = ".DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']);
      }

      // Предварительная проверка платежа.
      if (empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }

      $sMerchantPass2 = $paymentInfo[2]['value'];
      $sSignatureValue = $paymentAmount.':'.$paymentOrderId.':'.$sMerchantPass2;
      $md5sum = strtoupper(md5($sSignatureValue));

      if ($_POST['SignatureValue'] == $md5sum) {
        $order->updateOrder(array('id' => $paymentOrderId, 'status_id' => 2));
        $order->sendMailOfPayed($paymentOrderId, $paymentAmount, $paymentID);
        echo "OK".$paymentOrderId;
        exit;
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }
}