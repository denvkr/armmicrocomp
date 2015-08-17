<?php

/**
 * Класс User - предназначен для работы с учетными записями пользователей системы.
 * Доступен из любой точки программы.
 * Реализован в виде синглтона, что исключает его дублирование.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class User {

  static private $_instance = null;
  private $auth = array();
  static $accessStatus = array(0 => 'Разрешен', 1 => 'Заблокирован');
  static $groupName = array(1 => 'Администратор', 2 => 'Пользователь', 3 => 'Менеджер');

  private function __construct() {
    // Если пользователь был авторизован, то присваиваем сохраненные данные.
    if (isset($_SESSION['user'])) {
      if ($_SESSION['userAuthDomain'] == $_SERVER['SERVER_NAME']) {
        $this->auth = $_SESSION['user'];
      }
    }
  }

  private function __clone() {
    
  }

  private function __wakeup() {
    
  }

  /**
   * Возвращет единственный экземпляр данного класса.
   * @return object - объект класса URL.
   */
  static public function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self;
    }
    return self::$_instance;
  }

  /**
   * Инициализирует объект данного класса User.
   * @return void
   */
  public static function init() {
    self::getInstance();
  }

  /**
   * Возвращает авторизированнго пользователя.
   * @return void
   */
  public static function getThis() {
    return self::$_instance->auth;
  }

  /**
   * Добавляет новую учетную запись пользователя в базу сайта.
   * @param $userInfo - массив значений для вставки в БД [Поле => Значение].
   * @return bool
   */
  public static function add($userInfo) {
    $result = false;

    // Если пользователя с таким емайлом еще нет.
    if (!self::getUserInfoByEmail($userInfo['email'])) {
      $userInfo['pass'] = crypt($userInfo['pass']);
      if (DB::buildQuery('INSERT INTO  `'.PREFIX.'user` SET ', $userInfo)) {
        $id = DB::insertId();
        $result = $id;
      }
    } else {
      throw new Exception('Неудалось добавить пользователя, т.к. указаный email уже используется');
      $result = false;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Удаляет учетную запись пользователя из базы.
   * @param $id id пользовате, чью запись следует удалить.
   * @return void
   */
  public static function delete($id) {

    // Нельзя удалить первого пользователя, поскольку он является админом
    if ($id > 1) {
      DB::query('DELETE FROM `'.PREFIX.'user` WHERE id = %s', $id);
      return true;
    }
    return false;
  }

  /**
   * Обновляет пользователя учетную запись пользователя.
   * @param $id - id пользователя.
   * @param $data - массив значений для вставки  в БД [Поле => Значение].
   * @param $authRewrite - false = перезапишет данные в сессии детущего пользователя, на полученные у $data.
   * @return void
   */
  public static function update($id, $data, $authRewrite = false) {
    $userInfo = USER::getUserById($id);

    //только если пытаемся разжаловать админа, проверяем,
    // не является ли он последним админом
    // Без админов никак незя!
    if ($userInfo->role == '1' && $data['role'] != '1') {
      $countAdmin = DB::query('
     SELECT count(id) as "count"
      FROM `'.PREFIX.'user`    
      WHERE role = 1
    ');
      if ($row = DB::fetchAssoc($countAdmin)) {
        if ($row['count'] == 1) {// остался один админ    
          $data['role'] = 1; // не даем разжаловать админа, уж лучше плохой чем никакого :-)
        }
      }
    }

    DB::query('
     UPDATE `'.PREFIX.'user`
     SET '.DB::buildPartQuery($data).'
     WHERE id = %d
   ', $id);

    if (!$authRewrite) {
      foreach ($data as $k => $v) {
        self::$_instance->auth->$k = $v;
      }
      $_SESSION['user'] = self::$_instance->auth;
    }

    return true;
  }

  /**
   * Разлогинивает авторизованного пользователя.
   * @param $id - id пользователя.
   * @return void
   */
  public static function logout() {
    self::getInstance()->auth = null;
    unset($_SESSION['user']);
    unset($_SESSION['cart']);
    //Удаляем данные о корзине.
    SetCookie('cart', '', time());
    MG::redirect('/enter');
  }

  /**
   * Аутентифицирует данные, с помощью сриптографического алгоритма
   * @param $email - емайл.
   * @param $pass - пароль.
   * @return bool
   */
  public static function auth($email, $pass) {

    $result = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
      WHERE email = "%s"
    ', $email, $pass);

    if ($row = DB::fetchObject($result)) {
      if ($row->pass == crypt($pass, $row->pass)) {
        self::$_instance->auth = $row;
        $_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
        $_SESSION['user'] = self::$_instance->auth;
        return true;
      }
    }
    return false;
  }

  /**
   * Получает все данные пользователя из БД по ID.
   * @param $id - пользователя.
   * @return void
   */
  public static function getUserById($id) {
    $result = false;
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
      WHERE id = "%s"
    ', $id);

    if ($row = DB::fetchObject($res)) {
      $result = $row;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает все данные пользователя из БД по email.
   * @param $email - пользователя.
   * @return void
   */
  public static function getUserInfoByEmail($email) {
    $result = false;
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
      WHERE email = "%s"
    ', $email);

    if ($row = DB::fetchObject($res)) {
      $result = $row;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Проверяет, авторизован ли текущий пользователь.
   * @return void
   */
  public static function isAuth() {
    if (self::getThis()) {
      return true;
    }
    return false;
  }

  /**
   * Получает список пользователей.
   * @param $id - пользователя.
   * @return void
   */
  public static function getListUser() {
    $result = false;
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
    ', $id);

    while ($row = DB::fetchObject($res)) {
      $result[] = $row;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

}