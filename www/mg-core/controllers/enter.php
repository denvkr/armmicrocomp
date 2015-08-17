<?php
/**
 * Контроллер: Enter
 * 
 * Класс Controllers_Enter обрабатывает действия пользователей на странице авторизации.
 * - Аутентифицирует пользовательские данные;
 * - Проверяет корректность ввода данных с формы авторизации;
 * - При успешной авторизации перенаправляет пользователя в личный кабинет.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Enter extends BaseController{

  function __construct(){
    
    if(URL::getQueryParametr('logout')){
      User::logout();
    }

    if(User::isAuth()){
      MG::redirect('/personal');
    }
    
    $data = array (
      'meta_title' => 'Авторизация',
      'meta_keywords' => !empty($model->currentCategory['meta_keywords']) ? $model->currentCategory['meta_keywords'] : "Авторизация,вход, войти в личный кабинет",
      'meta_desc' => !empty($model->currentCategory['meta_desc']) ? $model->currentCategory['meta_desc'] : "Авторизуйтесь на сайте и вы получите дополнительные возможности, недоступные для обычных пользователей.",
    );
   
    // Если пользователь не авторизован.
    if(!User::isAuth() && (isset($_POST['email'])||isset($_POST['pass']))){     
      if(!User::auth(URL::get('email'), URL::get('pass'))){
        $data['msgError'] = '<span class="msgError">'.'Неправильная пара email-пароль! Авторизоваться не удалось.'.'</span>';    
      }else{
        $this->successfulLogon();
      }     
    }
    
    $this->data = $data;
  }


  /**
   * Перенаправляет пользователя на страницу в личном кабинете.
   * @return void
   */
  public function successfulLogon(){

    // Если указан параметр для редиректа после успешной авторизации.
    if($location = URL::getQueryParametr('location')){
      MG::redirect($location);
    }else{

      // Иначе  перенаправляем в личный кабинет.
      MG::redirect('/personal');
    }
  }


  /**
   * Проверяет корректность ввода данных с формы авторизации.
   * @return void
   */
  public function validForm(){
    $email = URL::getQueryParametr('email');
    $pass = URL::getQueryParametr('pass');

    if(!$email || !$pass){
      // При первом показе, не выводить ошибку.
      if(strpos($_SERVER['HTTP_REFERER'], '/enter')){
        $this->data = array (
          'msgError' => '<span class="msgError">'.'Одно из обязательных полей не заполнено!'.'</span>',
          'meta_title' => 'Авторизация',
          'meta_keywords' => !empty($model->currentCategory['meta_keywords']) ? $model->currentCategory['meta_keywords'] : "Авторизация,вход, войти в личный кабинет",
          'meta_desc' => !empty($model->currentCategory['meta_desc']) ? $model->currentCategory['meta_desc'] : "Авторизуйтесь на сайте и вы получите дополнительные возможности, недоступные для обычных пользователей.",
       );
      }
      return false;
    }
    return true;
  }

}