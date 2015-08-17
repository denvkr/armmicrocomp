<?php

/**
 * Класс Mailer - предназначен для работы с почтой.
 * - Отправляет письма в корректной кодировке
 * - Доступен из любой точки программы.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Mailer {

  static private $_instance = null;
  static private $dataCharset = 'UTF-8';
  static private $sendCharset = 'KOI8-R';
  static private $endString = "\r\n";
  static private $addHeaders = null;

  private function __construct() {
    
  }

  private function __clone() {
    
  }

  private function __wakeup() {
    
  }

  /**
   * Инициализирует данный класс Mailer.
   * @return void
   */
  public static function init() {
    self::getInstance();
  }

  /**
   * Возвращет единственный экземпляр данного класса.
   * @return object - объект класса Mailer
   */
  static public function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self;
    }
    return self::$_instance;
  }

  /**
   * Функция для отправки писем в UTF-8
   * @param $dataMail - массив с данными
   * <code>
   * array(
   * nameFrom => имя отправителя
   * emailFrom => email отправителя
   * nameTo => имя получателя
   * emailTo => email получателя
   * dataCharset => кодировка переданных данных
   * sendCharset => кодировка письма
   * subject => тема письма
   * body => текст письма
   * html => письмо в виде html или обычного текста
   * addheaders => дополнительные заголовки
   * contentType => если нужен особенный contentType
   * ); 
   * </code>
   * @return bool
   */
  public static function sendMimeMail($dataMail) {
    $to = self::mimeHeaderEncode($dataMail['nameTo']).' <'.$dataMail['emailTo'].'>';
    $subject = self::mimeHeaderEncode($dataMail['subject']);
    $from = self::mimeHeaderEncode($dataMail['nameFrom']).' <'.$dataMail['emailFrom'].'>';

    if (self::$dataCharset != self::$sendCharset) {
      $body = iconv(self::$dataCharset, self::$sendCharset, $dataMail['body']);
    }

    $headers = "From: ".$from.self::$endString;
    $type = ($dataMail['html']) ? 'html' : 'plain';
    $contentType = ($dataMail['contentType']) ? $dataMail['contentType'] : " text/$type; charset=".self::$sendCharset.self::$endString;
    $headers .= "Content-type: ".$contentType;
    $headers .= "Mime-Version: 1.0".self::$endString;
    $headers .= self::$addHeaders;

    // Сбрасываем заголовки, чтобы они не попали в следующее письмо.
    self::$addHeaders = null;

    // Отправляем письмо
    return @mail($to, $subject, $body, $headers);
  }

  /**
   * Метод получает массив с заголовками и их значениями,
   * преобразует все в верную кодировку, и сохраняет в переменную класса.
   * @param array $headers - массив заголовков, ключ значение.   * 
   * @return void
   */
  public static function addHeaders($headers) {
    if (!empty($headers)) {
      foreach ($headers as $key => $value) {
        self::$addHeaders.=$key.": ".$value.self::$endString;
      }
    }
  }

  /**
   * Метод для формирования корректных заголовков в письме.
   * @param type $str - значение заголовка.
   * @return string
   */
  public static function mimeHeaderEncode($header) {
    if (self::$dataCharset != self::$sendCharset) {
      $header = iconv(self::$dataCharset, self::$sendCharset, $header);
    }
    return '=?'.self::$sendCharset.'?B?'.base64_encode($header).'?=';
  }

  /**
   * Метод для отправки писем с вложением.
   * @param $dataMail - массив с данными
   * <code>
   * array(
   * From => email отправителя
   * To => email получателя
   * subject => тема письма
   * text => текст письма
   * filename => Имя файла относительно корневого каталога
   * );
   * @return bool
   */
  public static function sendMimeMailWithFile($dataMail) {
    $text = $dataMail['text'];

    if (self::$dataCharset != self::$sendCharset) {
      $text = iconv(self::$dataCharset, self::$sendCharset, $text);
    }

    $to = $dataMail['to'];
    $from = $dataMail['from'];

    $f = fopen($dataMail['filename'], "rb");
    $un = strtoupper(uniqid(time()));
    $head = "From: $from\n";
    $head .= "To: $to\n";
    $head .= "Subject: $subj\n";
    $head .= "X-Mailer: PHPMail Tool\n";
    $head .= "Reply-To: $from\n";
    $head .= "Mime-Version: 1.0\n";
    $head .= "Content-Type:multipart/mixed;";
    $head .= "boundary=\"----------".$un."\"\n\n";
    $zag = "------------".$un."\nContent-Type:text/plain; charset=\"".self::$sendCharset."\"\n";
    $zag .= "Content-Transfer-Encoding: 8bit\n\n$text\n\n";
    $zag .= "------------".$un."\n";
    $zag .= "Content-Type: application/octet-stream;";
    $zag .= "name=\"".basename($dataMail['filename'])."\"\n";
    $zag .= "Content-Transfer-Encoding:base64\n";
    $zag .= "Content-Disposition:attachment;";
    $zag .= "filename=\"".basename($dataMail['filename'])."\"\n\n";
    $zag .= chunk_split(base64_encode(fread($f, filesize($dataMail['filename']))))."\n";

    return @mail("$to", "$subj", $zag, $head);
  }

}