/**
 * Модуль для  раздела "Пользователи".
 */
var user = (function () {
  return {


    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init: function() {

      // Вызов модального окна при нажатии на кнопку добавления пользователя.
      $('.admin-center').on('click', '.section-user .add-new-button', function(){
        user.openModalWindow('add');
      });


      // Вызов модального окна при нажатии на кнопку изменения пользователя.
      $('.admin-center').on('click', '.section-user .edit-row', function(){
        user.openModalWindow('edit', $(this).attr('id'));
      });

      // Удаление пользователя.
      $('.admin-center').on('click', '.section-user .delete-order', function(){
        user.deleteUser($(this).attr('id'));
      });

      // Сохранение продукта при на жатии на кнопку сохранить в модальном окне.
      $('body').on('click', '#add-user-wrapper .save-button', function(){
        user.saveUser($(this).attr('id'));
      });


      // Удаление пользователя.
      $('body').on('click', '#add-user-wrapper .editPass', function(){
        user.editPassword();
      }
    );


      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-user .countPrintRowsUser', function(){
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsUser",
          count: count
        },
        (function(response) {
          admin.refreshPanel();
        })
        );

      });

    },


    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового пользователя, либо для редактирования старого.
     */
    openModalWindow: function(type, id) {

      switch (type) {
        case 'edit':{
          $('.users-table-wrapper .user-table-icon').text(lang.TITLE_USER_EDIT);
          user.editUser(id);
          $('.editorPas').css('display','none');
          $('.controlEditorPas').css('display','block');
          break;
        }
        case 'add':{
          $('.users-table-wrapper .user-table-icon').text(lang.TITLE_USER_NEW);
          user.clearFileds();
          $('.controlEditorPas').css('display','none');
          $('.editorPas').css('display','block');
          break;
        }
        default:{
          user.clearFileds();
          break;
        }
      }

      // Вызов модального окна.
      admin.openModal($('.b-modal'));

    },


    /**
     *  Проверка заполненности полей, для каждого поля прописывается свое правило.
     */
    checkRulesForm: function() {
      $('.errorField').css('display','none');
      $('input').removeClass('error-input');
      var error = false;
      // проверка email.
      
      if(!/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]{0,61}\.)+[a-z]{2,6}$/.test($('input[name=email]').val()) || !$('input[name=email]').val()){
        $('input[name=email]').parent("label").find('.errorField').css('display','block');
        $('input[name=email]').addClass('error-input');
        error = true;
      }

      // если активен блок смены пароля
      if($('.editorPas').css('display')=='block'){
        // проверка пароля, в нем не должно быть спец символов и он должен быть  не менее 5 символов.
        if(!admin.regTest(1,$('input[name=pass]').val()) || !$('input[name=pass]').val() || $('input[name=pass]').val().length<5){
          $('input[name=pass]').parent("label").find('.errorField').css('display','block');
          $('input[name=pass]').addClass('error-input');
          error = true;
        }

        // повторение пароля.
        if($('input[name=passconfirm]').val()!=$('input[name=pass]').val()){
          $('input[name=passconfirm]').parent("label").find('.errorField').css('display','block');
          $('input[name=passconfirm]').addClass('error-input');
          error = true;
        }
      }

      if(error == true){
        return false;
      }

      return true;
    },


    /**
     * Сохранение изменений в модальном окне пользователя.
     * Используется и для сохранения редактированных данных и для сохраниеня нового продукта.
     * id - идентификатор пользователя, может отсутсвовать если производится добавление нового товара.
     */
    saveUser: function(id) {

      // Если поля не верно заполнены, то не отправляем запрос на сервер.
      if(!user.checkRulesForm()){
        return false;
      }



      // Пакет характеристик пользователя.
      var packedProperty = {
        mguniqueurl:"action/saveUser",
        id: id,
        email: $('input[name=email]').val(),
        pass: $('input[name=pass]').val(),
        name: $('input[name=name]').val(),
        sname: $('input[name=sname]').val(),
        address: $('textarea[name=address]').val(),
        phone: $('input[name=phone]').val(),
        blocked: $('select[name=blocked]').val(),
        activity: $('select[name=activity]').val(),
        role: $('select[name=role]').val()
      }

      // отправка данных на сервер для сохранеиня
      admin.ajaxRequest(packedProperty,
        (function(response) {
          admin.indication(response.status, response.msg);

          if(response.status=='error'){
            return false;
          }

          //return false;

          var classForTagActivity='activity-product-false';
         // в зависимости от  значения activityвыводим нужную запись
          if(response.data.activity=="1"){
            response.data.activity = lang.USER_ACTYVITY_TRUE;
            classForTagActivity='activity-product-true';
          }
          else{
            response.data.activity = lang.USER_ACTYVITY_FALSE;
            classForTagActivity='activity-product-false';
          }

          var classForTagBloked='activity-product-false';
         // в зависимости от  значения bloked нужную запись
          if(response.data.blocked=="1"){
            response.data.blocked = lang.ACCESS_PERSONAL_TRUE;
            classForTagBloked='activity-product-false';
          }
          else{
            response.data.blocked = lang.ACCESS_PERSONAL_FALSE;
            classForTagBloked='activity-product-true';
          }


         // в зависимости от  значения bloked нужную запись
          if(response.data.role=="1"){
            response.data.role = lang.USER_GROUP_NAME1;
          }
          if(response.data.role=="2"){
            response.data.role = lang.USER_GROUP_NAME2;
          } 
          if(response.data.role=="3"){
            response.data.role = lang.USER_GROUP_NAME3;
          }


          //при обновлении получаем дату из текуще строки
          if(!response.data.date_add){
            response.data.date_add = $('.user-tbody tr[id='+response.data.id+'] .date_add').text();
          }

          // html верстка для  записи в таблице раздела
          var row='\
            <tr id="'+response.data.id+'">\
                <td class="email">'+response.data.email+'</td>\
                  <td class="activity"><span class="'+classForTagActivity+'">'+response.data.activity+'</span></td>\
                <td class="role">'+response.data.role+'</td>\
                <td class="date_add">'+response.data.date_add+'</td>\
                <td class="blocked"><span class="'+classForTagBloked+'">'+response.data.blocked+'</span></td>\
                <td class="actions">\
                  <ul class="action-list">\
                    <li class="edit-row" id="'+response.data.id+'"><a href="#" title="'+lang.EDIT+'"></a></li>\
                    <li class="delete-order" id="'+response.data.id+'"><a href="#" title="'+lang.DELETE+'"></a></li>\
                  </ul>\
                </td>\
             </tr>';

          // Вычисляем, по наличию характеристики 'id',
          // какая операция производится с пользователем, добавление или изменение.
          // Если id есть значит надо обновить запись в таблице.
          if(packedProperty.id){
            $('.user-tbody tr[id='+packedProperty.id+']').replaceWith(row);
          }else{
            // Если id небыло значит добавляем новую строку в начало таблицы.
            if($('.user-tbody tr:first').length>0){
              $('.user-tbody tr:first').before(row);
              
              var newCount = $('.widget-table-title .produc-count strong').text()-0+1;
              if(response.status=='success'){
                $('.widget-table-title .produc-count strong').text(newCount);                
              }
              
            } else{
              $('.user-tbody ').append(row);
            }
          }

          // Закрываем окно
          admin.closeModal($('.b-modal'));
        })
      );
    },

    /**
     * Получает данные о пользователе с сервера и заполняет ими поля в окне.
     */
    editUser: function(id) {
      admin.ajaxRequest({
        mguniqueurl:"action/getUserData",
        id: id
      },
      user.fillFileds(),
      $('.widget-table-body .add-user-form')
      );
    },


    /**
     * Удаляет пользователя из БД сайта и таблицы в текущем разделе
     */
    deleteUser: function(id) {
      if(confirm(lang.DELETE+'?')){
        admin.ajaxRequest({
          mguniqueurl:"action/deleteUser",
          id: id
        },
        function(response) {
          admin.indication(response.status, response.msg);
          $('.user-table tr[id='+id+']').remove();
          var newCount = ($('.widget-table-title .produc-count strong').text()-1);
          if(newCount>=0){
            $('.widget-table-title .produc-count strong').text(($('.widget-table-title .produc-count strong').text()-1));
          }
         }
        );
      }


    },


   /**
    * Заполняет поля модального окна данными
    */
    fillFileds:function() {
      return (function(response) {
        $('input').removeClass('error-input');
        $('input[name=email]').val(response.data.email);
        $('input[name=name]').val(response.data.name);
        $('input[name=sname]').val(response.data.sname);
        $('input[name=phone]').val(response.data.phone);
        $('textarea[name=address]').val(response.data.address);
        $('.activity option[value="'+response.data.activity+'"]').prop("selected", "selected");
        $('.role option[value="'+response.data.role+'"]').prop("selected", "selected");
       // alert(response.data.blocked);
        $('select[name=blocked] option[value="'+response.data.blocked+'"]').prop("selected", "selected");

        $('.save-button').attr('id',response.data.id);
        $('.errorField').css('display','none');
        $('.editPass').text('Изменить');
      })
    },


   /**
    * Чистит все поля модального окна
    */
    clearFileds:function() {
      $('input[name=email]').val(''),
      $('input[name=pass]').val(''),
      $('input[name=name]').val(''),
      $('input[name=sname]').val(''),
      $('textarea[name=address]').val(''),
      $('input[name=phone]').val(''),
      $('select[name=blocked]').val(''),
      $('select[name=activity]').val(''),
      $('select[name=role]').val('')
      $('.save-button').attr('id','');
      $('.editorPas').css('display', 'none');
      // Стираем все ошибки предыдущего окна если они были.
      $('.errorField').css('display','none');
      $('.error-input').removeClass('error-input');
    },


   /**
    * открывает блок для смены пароль
    */
    editPassword: function() {
      $('.editorPas').slideToggle('show', function() {

        $('.editorPas').css('display')=='block'
          ? $('.editPass').text(lang.USER_PASS_NO_EDIT)
          : $('.editPass').text(lang.USER_PASS_EDIT);

        }
      );
    }

  }
})();

// инициализациямодуля при подключении
user.init();