/**
 * Модуль для  раздела "Заказы".
 */
var order = (function() {
  return {
    comment: null,
    searcharray: [], //массив найденых товаров при добавлении в заказ
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init: function() {
      
      // Вызов модального окна при нажатии на кнопку добавления заказа.
      $('.admin-center').on('click', '.section-order .add-new-button', function() {        
        admin.indication('error','Ограничено версией');
        return false;   
      });

      // Вызов модального окна при нажатии на кнопку изменения товаров.
      $('.admin-center').on('click', '.section-order .see-order', function() {
        order.openModalWindow('edit', $(this).attr('id'));
      });

      // Удаление товара.
      $('.admin-center').on('click', '.section-order .delete-order', function() {
        order.deleteOrder($(this).attr('id'));
      });

      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '.section-order .show-filters', function() {
        $('.filter-container').slideToggle(function() {
          $('.property-order-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });

      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '.section-order .show-property-order', function() {
        $('.property-order-container').slideToggle(function() {
          $('.filter-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });


      // Сброс фильтров.
      $('.admin-center').on('click', '.section-order .refreshFilter', function() {
        admin.show("orders.php", "adminpage", "refreshFilter=1", admin.sliderPrice);
        return false;
      });

      // Применение выбраных фильтров
      $('.admin-center').on('click', '.section-order .filter-now', function() {
        admin.indication('error','Ограничено версией');
        return false;
      });

      // Открывает панель настроек заказа
      $('.admin-center').on('click', '.section-order .property-order-container .save-property-order', function() {
        order.savePropertyOrder();
        return false;
      });

      // Выбор картинки
      $('.admin-center').on('click', '.section-order .property-order-container .upload-sign', function() {
        admin.openUploader('order.getSignFile');
      });

      // Выбор картинки
      $('.admin-center').on('click', '.section-order .property-order-container .upload-stamp', function() {
        admin.openUploader('order.getStampFile');
      });

      // Сохранение  при нажатии на кнопку сохранить в модальном окне.
      $('body').on('click', '#add-order-wrapper .save-button', function() {
        order.saveOrder($(this).attr('id'), $(this).parents('.orders-table-wrapper'));
      });

      // Распечатка заказа  
      $('body').on('click', '#add-order-wrapper .order-preview .print-button, .order-to-print a', function() {
        order.printOrder($(this).data('id'));
      });

      // Сохранить в PDF   
      $('body').on('click', '#add-order-wrapper .order-preview .get-pdf-button, .order-to-pdf a', function() {
        window.location.href = mgBaseDir + '/mg-admin?getOrderPdf=' + $(this).data('id');
      });

      // Получить выгрузку счета в CSV   
      $('body').on('click', '#add-order-wrapper .order-preview .csv-button, .order-to-csv a', function() {
        window.location.href = mgBaseDir + '/mg-admin?getExportCSV=' + $(this).data('id');
      });

      // Разблокировать поля для редактирования заказа.
      $('body').on('click', '#add-order-wrapper .order-preview .editor-order', function() {
        order.enableEditor();       
      });

      // Удаляет выбраный продукт из поля для добавления в заказ.
      $('body').on('click', '#add-order-wrapper .clear-product', function() {
        $(".product-block").html('');
      });

      // Применить купон в редактировании заказа.
      $('body').on('change', '#add-order-wrapper select[name=promocode]', function() {
        order.getPromoCode();          
       
      });

      // Подстановка значения стоимости при выборе способа доставки в добавлении заказа.
      $('body').on('change', '#delivery', function() {
        $('#delivery').parent().find('.errorField').css('display', 'none');
        $('#delivery').removeClass('error-input');
        order.calculateOrder();
      });

      // Смена плательщика.
      $('body').on('change', '#customer', function() {
        $(this).val()=='fiz'?$('.yur-list-editor').hide(): $('.yur-list-editor').show();        
      });

      // Действия при выборе способа оплаты.
      $('body').on('change', 'select#payment', function() {
        $('.main-settings-list select#payment').parent().find('.errorField').css('display', 'none');
        $('.main-settings-list select#payment').removeClass('error-input');
      });

      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-order .countPrintRowsOrder', function() {
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsOrder",
          count: count
        },
        function(response) {
          admin.refreshPanel();
        }
        );
      });

      // Поиск товара при создании нового заказа.
      // Обработка ввода поисковой фразы в поле поиска.
      $('.admin-center').on('keyup', '#order-data input[name=searchcat]', function() {
        order.searchProduct($(this).val());
      });
      
      // Подстановка товара из примера в строку поиска.
      $('.admin-center').on('click', '#order-data .example-find', function() {
        $('#order-data input[name=searchcat]').val($(this).text());
        order.searchProduct($(this).text());
      });
      
      
      
      
      // Клик вне поиска.
      $(document).mousedown(function(e) {
        var container = $(".fastResult");
        if (container.has(e.target).length === 0 &&
                $(".search-block").has(e.target).length === 0) {
          container.hide();
        }
      });

      // Пересчет цены товара аяксом в форме добавления заказа.
      $('.admin-center').on('change', '.orders-table-wrapper .property-form input, .orders-table-wrapper .property-form select',
              function() {
                order.refreshPriceProduct();
                return false;
              });

      // Клик по найденым товарам поиске в форме добавления заказа
      $('.admin-center').on('click', '.fast-result-list a', function() {
        order.viewProduct($(this).data('element-index'));
      });

      // Вставка продукта из списка поиска в строку заказа.
      $('.admin-center').on('click', '.orders-table-wrapper .property-form .addToCart', function() {
        order.addToOrder($(this));
        return false;
      });

      // Удаление позиции из заказа.
      $('body').on('click', '.order-history a[rel=delItem]', function() {
        $(this).parents('tr').remove();
        order.calculateOrder();
      });

      // Обработка выбора  способа доставки при добавлении нового заказа.
      $('body').on('change', 'select #delivery', function() {
        $('select #delivery option[name=null]').remove();
      });

      // Обработка выбора  способа оплаты при добавлении нового заказа.
      $('body').on('change', 'select#payment', function() {
        $('select#payment option[name=null]').remove();
      });

      // Перерасчет стоимости при смене количества товара.
      $('body').on('keyup', '#orderContent input', function() {
        if (1 > $(this).val() || !$.isNumeric($(this).val())) {
          $(this).val('1');        
        }
        order.calculateOrder();
      });
      
      // Обработка ввода адреса доставки 
      $('body').on('keyup', '#order-data input[name=address]', function() {   
        $('.map-btn').attr('href','http://maps.yandex.ru/?text='+$(this).val());        
      });
      

    },
    /**
     * Создает строку в таблице заказов
     * @param {type} position - параметры позиции
     * @param {type} type - тип формирования, для имеющегося состава или новой позиции
     * @returns {String}
     */
    createPositionRow: function(position, type) {
     
      var row = '\
          <tr data-id=' + position.id + '>\
          <td class="image"><img src="' + position.image_url + '" style="width:50px;"></td>\
          <td class="title">' + position.title + '</td>\
          <td class="code" data-code="' + position.code + '">' + position.code + '</td>\
          <td class="price" data-price="' + position.price + '"><span class="value">' + position.price + '</span> ' + admin.CURRENCY + '</td>\
          <td class="count">' +
              ((type == "view") ? '<span class="value order-edit-visible">' + position.count + '</span>' : '') +
              '<input order_id="' + position.order_id + '"  type="text" value="' + position.count + '" class="count ' +
              ((type == "view") ? 'order-edit-display' : '')
              + '"> ' + lang.UNIT + '</td>\
          <td class="summ" data-summ="' + position.summ + '"><span class="value">' + position.summ + '</span> ' + admin.CURRENCY + '</td>\
          <td><a class="tool-tip-bottom dell-btn ' +
              ((type == "view") ? 'order-edit-display' : '')
              + '" order_id="' + position.order_id + '" href="javascript:void(0);" rel="delItem"></a></td>\
        </tr>';
      return row;
    },
    /*
     * Получает все выбранные свойства товара перед добавлением в строку заказа  
     * @returns {String}
     */
    getPropPosition: function() {
      var prop = '';
      $('.property-form select, .property-form input[type=checkbox],.property-form input[type=radio]').each(function() {
        if ($(this).attr('name') != 'variant') {
          var val = "";
          var val = $(this).find('option:selected').text();
          if (!val) {
            if ($(this).val() == "true") {
              val = $(this).next("span").text();
              prop += '<br>' + val.replace(eval('/[-+]\\s[0-9]+' + $('#order-data .currency-sp').text() + '/gi'), '');
            }
          } else {
            prop += '<br>' + $(this).attr('name') + ': ' + val.replace(eval('/[-+]\\s[0-9]+' + $('#order-data .currency-sp').text() + '/gi'), '');
          }
        }
      });
      return prop;
    },
    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового товара, либо для редактирования старого.
     */
    openModalWindow: function(type, id) { 
      $('.product-block').html('');
      switch (type) {
        case 'add':
          {      
            $('.add-order-table-icon').text(lang.TITLE_NEW_ORDER);
            order.newOrder();
            break;
          }
        case 'edit':
          {        
            $('.add-order-table-icon').text(lang.TITLE_ORDER_VIEW + ' №' + id + ' от ' + $('tr[order_id=' + id + '] .add_date').text());
            order.editOrder(id);
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
      $('.errorField').css('display', 'none');
      $('#order-data input, select').removeClass('error-input');
      var error = false;

      // покупатель обязательно надо заполнить.
      if (!$('#order-data input[name=name_buyer]').val()) {
        $('#order-data input[name=name_buyer]').parent().find('.errorField').css('display', 'block');
        $('#order-data input[name=name_buyer]').addClass('error-input');
        console.log('покупатель обязательно надо заполнить');
        error = true;
      }

      // email обязательно надо заполнить.
      if (!/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]{0,61}\.)+[a-z]{2,6}$/.test($('#order-data input[name=user_email]').val()) || !$('#order-data input[name=user_email]').val()) {
        $('#order-data input[name=user_email]').parent().find('.errorField').css('display', 'block');
        $('#order-data input[name=user_email]').addClass('error-input');
        error = true;
      }

      // товар обязательно надо добавить
      if ($("#totalPrice").text() == "0") {
        $('.search-block .errorField').css('display', 'block');
        $('.search-block input.search-field').addClass('error-input');
        error = true;
      }

      // проверка реквизитов юр. лица
      if ($('#customer').val() == 'yur') {
        var filds = ['nameyur', 'adress', 'inn', 'kpp', 'bank', 'bik', 'ks', 'rs'];
        filds.forEach(function(element, index, array) {
          if (!$('#order-data input[name=' + element + ']').val()) {
            $('#order-data input[name=' + element + ']').parent().find('.errorField').css('display', 'block');
            $('#order-data input[name=' + element + ']').addClass('error-input');
            error = true;
          }
        });
      }

      if (error == true) {
        return false;

      }
      return true;
    },
    /**
     * Cобираем состав заказа из таблицы   
     * @returns {string}
     */      
    getOrderContent: function() {         
      var obj = '[';
      $('#order-data .order-history tbody#orderContent tr').each(function() {        
        if ($(this).data('id')) {
          obj += '{'
          obj += '"id":"' + $(this).data('id') + '",';
          obj += '"title":"' + $(this).find('.titleProd').text() + '",';
          obj += '"name":"' + $(this).find('.titleProd').text() + '",';
          obj += '"property":"' + $(this).find('.property').text() + '",';
          obj += '"price":"' + $(this).find('.price span').text() + '",';
          obj += '"code":"' + $(this).find('.code').text() + '",';
          obj += '"count":"' + $(this).find('input').val() + '",';
          obj += '"coupon":"' + $("select[name=promocode]").val() + '",';
          obj += '"info":"' + $(".user-info-order-edit").text() + '",';
          obj += '"url":"' + $(this).find(".href-to-prod").data('url') + '",';
          obj += '},';
        }
      });
      obj += ']';      
      return eval("(" + obj + ")");
    }, 
    /**
     * Сохранение изменений в модальном окне заказа.
     * Используется и для сохранения редактированных данных и для сохраниеня нового заказа.
     * id - идентификатор продукта, может отсутсвовать если производится добавление нового заказа.
     */
    saveOrder: function(id, container) {     
      var orderContent = order.getOrderContent();
   
      if (!order.checkRulesForm()) {
       return false;
      }
  
        var yur = $('#customer').val()=='yur'?true:false;
        // Пакет характеристик заказа.
        var packedProperty = {
          mguniqueurl: "action/saveOrder",
          address: $('input[name=address]').val(),
          comment: $('textarea[name=comment]').val(),
          delivery_cost: $('#deliveryCost').text(),
          delivery_id: $('select#delivery :selected').attr('name'),
          id: id,
          name_buyer: $('input[name=name_buyer]').val(),
          order_content: orderContent,
          payment_id: $('select#payment :selected').val(),
          phone: $('input[name=phone]').val(),
          status_id: $('select[name=status_id] :selected').val(),
          summ: $('#totalPrice').text(),
          user_email: $('input[name=user_email]').val(),
          nameyur: (yur?container.find('.yur-list-editor input[name=nameyur]').val():''),
          adress: (yur?container.find('.yur-list-editor input[name=adress]').val():''),
          inn: (yur?container.find('.yur-list-editor input[name=inn]').val():''),
          kpp: (yur?container.find('.yur-list-editor input[name=kpp]').val():''),
          ogrn: (yur?container.find('.yur-list-editor input[name=ogrn]').val():''),
          bank: (yur?container.find('.yur-list-editor input[name=bank]').val():''),
          bik: (yur?container.find('.yur-list-editor input[name=bik]').val():''),
          ks: (yur?container.find('.yur-list-editor input[name=ks]').val():''),
          rs: (yur?container.find('.yur-list-editor input[name=rs]').val():''),
        }
      

      // отправка данных на сервер для сохранеиня
      admin.ajaxRequest(packedProperty,
              function(response) {
                admin.indication(response.status, response.msg);
                order.indicatorCount(response.data.count);
                var assocStatus = ['get-paid', 'get-paid', 'paid', 'get-paid', 'dont-paid', 'paid'];

                if (response.data.newId) {                
               
                  var row = order.drawRowOrder(response.data, assocStatus);

                  if ($('.order-tbody tr').length == 1) {
                    $('.order-tbody tr').remove();
                  }

                  // Если id не было значит добавляем новую строку в начало таблицы.
                  if ($('.order-tbody tr:first').length > 0) {
                    $('.order-tbody tr:first').before(row);
                  } else {
                    $('.order-tbody ').append(row);
                  }

                  var newCount = $('.widget-table-title .produc-count strong').text() - 0 + 1;
                  $('.button-list a[rel=orders]').parent().find('span :first').text(newCount);

                  
                } else {             
                  var row = order.drawRowOrder(response.data, assocStatus);
                  $('tr[order_id=' + response.data.id + ']').replaceWith(row);    
                }


                admin.closeModal($('.b-modal'));
              }
      );
    },
    // меняет индикатор количества новых заказов
    indicatorCount: function(count) {
      if (count == 0) {
        $('.button-list a[rel=orders]').parents('li').find('.message-wrap').hide();
      } else {
        $('.button-list a[rel=orders]').parents('li').find('.message-wrap').show();
        $('.button-list a[rel=orders]').parents('li').find('.message-wrap').text(count);
      }
    },
    // Выводит выпадающий список продуктов по заданному запросу
    searchProduct: function(text) {
      if (text.length >= 2) {
        admin.ajaxRequest({
          mguniqueurl: "action/getSearchData",
          search: text
        },
        function(response) {
          order.searcharray = [];
          var html = '<ul class="fast-result-list">';
          var currency = response.currency;
          var mgBaseDir = $('#thisHostName').text();

          function buildElements(element, index, array) {
            order.searcharray.push(element);
            html +=
                    '<li><a href="javascript:void(0)" data-element-index="' +
                    index + '" data-id="' + element.id + '" data-code="' +
                    element.code + '" data-price="' + element.price + '"> \n\
                <div class="fast-result-img">' +
                    '<img src="' + mgBaseDir + '/uploads/thumbs/30_' + element.image_url
                    + '" ' + 'alt="' + element.title + '"/>' +
                    '</div><div class="search-prod-name">'
                    + element.title +
                    '</div> <span class="product-code">' + element.code +
                    '</span><span>' + element.price + ' ' + currency +
                    '</span></a></li>';
          }

          if ('success' == response.status && response.item.items.catalogItems.length > 0) {
            response.item.items.catalogItems.forEach(buildElements);
            html += '</ul>';
            $('#order-data .fastResult').html(html);
            $('#order-data .fastResult').show();
          } else {
            $('#order-data .fastResult').hide();
          }
        }
        );
      } else {
        $('.fastResult').hide();
      }
    },
    
    /**
     * Удаляет запись из БД сайта и таблицы в текущем разделе
     */
    deleteOrder: function(id) {
      if (confirm(lang.DELETE + '?')) {        
        admin.ajaxRequest({
          mguniqueurl: "action/deleteOrder",
          id: id
        },
        function(response) {         
          admin.indication(response.status, response.msg);
          order.indicatorCount(response.data.count - 1);
          $('tr[order_id=' + id + ']').remove();
          var newCount = ($('.widget-table-title .produc-count strong').text() - 1);
          if (newCount >= 0) {
            $('.widget-table-title .produc-count strong').text(newCount);
          }

          if ($('.product-table tr').length == 1) {
            var row = "<tr><td colspan=" + $('.product-table th').length + " class='noneOrders'>" + lang.ORDER_NONE + "</td></tr>"
            $('.order-tbody').append(row);
          }

        }
        );
      }
    },
   
    /**
     * Редактирует заказ
     * @param {type} id
     * @returns {undefined}
     */
    editOrder: function(id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getOrderData",
        id: id
      },
      order.fillFileds(),
      $('.order-preview')
      );
    },
            
     newOrder: function(id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getOrderData",
        id: null
      },
      order.fillFileds('newOrder'),
      $('.order-preview')
      );
    },
    /**
     * Заполняет поля модального окна данными.
     */
    fillFileds: function(type) {
      return function(response) {   
        $('.order-edit-display').hide();
        $('.order-edit-visible').show();
        /* заполнение выпадающих списков */
        $('.order-preview .save-button').attr('id', response.data.order.id);
        $('.order-preview .print-button').data('id', response.data.order.id);
        $('.order-preview .get-pdf-button').data('id', response.data.order.id);
        $('.order-preview .csv-button').data('id', response.data.order.id);     
        $('#orderStatus').val(response.data.order.status_id);
        
        var deliveryCurrentName = '';
        //список способов доставки
        var deliveryList = '<select id="delivery">';
        var selected = '';
        $.each(response.data.deliveryArray, function(i, delivery) {
          selected = '';
          if (delivery.id == response.data.order.delivery_id) {
            deliveryCurrentName = delivery.name;
            selected = 'selected';
          }
          deliveryList += '<option value="' + delivery.cost + '" name="' + delivery.id + '" ' + selected + '>' + delivery.name + '</option>';
        });
        deliveryList += '</select>';


        var paymentCurrentName = '';
        //список способов оплаты
        var paymentList = '<select id="payment">';
        $.each(response.data.paymentArray, function(i, payment) {
          selected = '';
          if (payment.id == response.data.order.payment_id) {
            paymentCurrentName = payment.name;
            selected = 'selected';
          }
          paymentList += '<option value="' + payment.id + '" ' + selected + '>' + payment.name + '</option>';

        });
        paymentList += '</select>';

        var coupon = '';
        var info = '';
        var orderContentTable = '';
      
        if(response.data.order.order_content){
          $.each(response.data.order.order_content, function(i, element) {          
            coupon = element.coupon ? element.coupon : '';
            info = element.info ? element.info : '';
          
            var position = {
              order_id: response.data.order.id,
              id: element.id,
              title: '<a href="' + mgBaseDir + '/' + element.url + '" data-url="' + element.url + '" class="href-to-prod"><span class="titleProd">' + element.name + '</span></a>' + '<span class="property"></br>' + element.property + '</span>',
              prop: element.property,
              code: element.code,
              price: element.price,
              count: element.count,
              summ: element.count * element.price,
              image_url:  mgBaseDir + '/uploads/thumbs/30_' + element.image_url,
            };

            orderContentTable += order.createPositionRow(position, 'view');

          });
        }
        
         var data = {
           paymentList:paymentList,
           deliveryList:deliveryList,
           coupon:coupon,
           info:info,
           orderContentTable:orderContentTable,
           paymentCurrentName:paymentCurrentName,
           deliveryCurrentName:deliveryCurrentName
         }
       
        $('.order-history').html(order.drawOrder(response,data));
        
        // Если открыта модалка добавления нового заказа.
        if(type=='newOrder'){          
          $('.order-history input').val('');
          $('.order-history #orderContent').html('');
          order.enableEditor();
          $('#delivery option[value=0]').prop('selected','selected');
          order.calculateOrder();
          $('.order-preview .save-button').attr('id', "");       
        }
        
      }
    },
            
     /**
     * Создает верстку для модального окна, редактирования и добавления заказа
     * @param {type} id
     * @returns {undefined}
     */
    drawOrder: function(response,data) {
       /* заполнение состава заказа  */

        var editorBlock = '\
          <div class="order-edit-display fl-left editor-block">\
            <p>\
              <span>' + lang.ORDER_BUYER + ':</span>\
              <input type="text" name="name_buyer" value="' + response.data.order.name_buyer + '" >\
            </p>\
            <p>\
              <span>' + lang.ORDER_ADDRESS + ':</span>\
               <input type="text" name="address" value="' + response.data.order.address + '" ><a target="_blank" class="map-btn" title="Посмотреть на карте" href="http://maps.yandex.ru/?text=' + response.data.order.address + '" ></a></strong>\
            </p>\
            <p>\
              <span>' + lang.ORDER_PAYMENT + ':</span>\
              ' + data.paymentList + '\
            </p>\
            <p>\
              <span>' + lang.ORDER_EMAIL + '</span>\
              <input type="text" name="user_email" value="' + response.data.order.user_email + '">\
            </p>\
            <p>\
              <span>' + lang.ORDER_PHONE + '</span>\
              <input type="text" name="phone" value="' + response.data.order.phone + '">\
            </p>\
            <p>\
              <span>Плательщик:</span>\
              <select id="customer" name="customer">\
                <option value="fiz">Физическое лицо</option>\
                <option value="yur" '+(response.data.order.yur_info.inn ? 'selected' : '')+'>Юридическое лицо</option>\
              </select>\
            </p>\
         ';
          
         editorBlock += '\
            <ul class="yur-list-editor">\
              <li><span>Юр. лицо:</span><input type="text" name="nameyur" value="' + (response.data.order.yur_info.nameyur?response.data.order.yur_info.nameyur:'') + '"></li>\
              <li><span>Юр. адрес:</span><input type="text" name="adress" value="' + (response.data.order.yur_info.adress?response.data.order.yur_info.adress:'') + '"></li>\
              <li><span>ИНН:</span><input type="text" name="inn" value="' + (response.data.order.yur_info.inn?response.data.order.yur_info.inn:'') + '"></li>\
              <li><span>КПП:</span><input type="text" name="kpp" value="' + (response.data.order.yur_info.kpp?response.data.order.yur_info.kpp:'') + '"></li>\
              <li><span>Банк:</span><input type="text" name="bank" value="' + (response.data.order.yur_info.bank?response.data.order.yur_info.bank:'') + '"></li>\
              <li><span>БИК:</span><input type="text" name="bik" value="' + (response.data.order.yur_info.bik?response.data.order.yur_info.bik:'') + '"></li>\
              <li><span>К/Сч:</span><input type="text" name="ks" value="' + (response.data.order.yur_info.ks?response.data.order.yur_info.ks:'') + '"></li>\
              <li><span>Р/Сч:</span><input type="text" name="rs" value="' + (response.data.order.yur_info.rs?response.data.order.yur_info.rs:'') + '"></li>\
          </ul>'
  
        editorBlock += '</div>';
      
        var selectPromocode = '<select name="promocode">';    
          selectPromocode += '<option>Не указан</option>';
          $.each(response.data.order.promoCodes, function(i, element) {             
            selectPromocode += '<option '+(element==data.coupon?'selected':'')+'>'+element+'</option>';
          });          
          selectPromocode += '</select>';
          
        var orderHtml = '\
                   <p class="change-pass-title th-'+changeTheme.color+'">' + lang.ORDER_CONТENT + ':</p>\
                     <table class="status-table">\
                       <thead>\
                        <tr>\
                          <th></th>\
                          <th>' + lang.ORDER_PROD + '</th>\
                          <th>' + lang.ORDER_CODE + '</th>\
                          <th>' + lang.ORDER_PRICE + '</th>\
                          <th>' + lang.ORDER_COUNT + '</th>\
                          <th>' + lang.ORDER_SUMM + '</th>\
                          <th></th>\
                        </tr>\
                      </thead>\
                      <tbody id="orderContent">' + data.orderContentTable + '</tbody>\
                     </table>\
                       <div class="order-payment-sum">\
                          <p>\
                              <span>' + lang.ORDER_TOTAL_PRICE + ':</span>\
                              <span>' + '<span id="totalPrice">' + response.data.order.summ * 1 + '</span>' + " " + admin.CURRENCY + '</span>\
                          </p>\
                           <p class="promocode-order">\
                            <span>Промокод: </span>\
                            <span class="order-edit-visible"><strong>' + data.coupon + '</strong></span>\
                            <span class="order-edit-display code-block">' + selectPromocode + '</span>\
                          </p>\
                          <p>\
                              <span>' + 'Скидка по промокоду' + ':</span>\
                              <span class="promocode-percent"><span>' + (response.data.discount ? response.data.discount : '0') + '</span>%</span>\
                          </p>\
                          <p>\
                              <span>' + lang.ORDER_DELIVERY + ':</span>\
                              <span class="order-edit-visible"><strong>' + data.deliveryCurrentName + '</strong></span>\
                              <span class="order-edit-display">' + data.deliveryList + '</span>\
                          </p>\
                           <p>\
                              <span>' + 'Стоимость доставки' + ':</span>\
                              <span>' + '<span id="deliveryCost">' + response.data.order.delivery_cost + '</span>' + " " + admin.CURRENCY + '</span>\
                          </p>\
                          <p>\
                              <span>' + lang.ORDER_SUMM + ':</span>\
                              <span class="total-price">' + '<span id="fullCost">' + (response.data.order.summ * 1 + response.data.order.delivery_cost * 1) + '</span>' + " " + admin.CURRENCY + '</span>\
                        </p>\
                     </div>\
                        </div>'
                + editorBlock +
                '<div class="order-other-info order-edit-visible">\
                        <p>\
                            <span>' + lang.ORDER_BUYER + ':</span>\
                            <strong>' + response.data.order.name_buyer + '</strong>\
                        </p>\
                        <p>\
                            <span>' + lang.ORDER_ADDRESS + ':</span>\
                            <strong><a target="_blank" href="http://maps.yandex.ru/?text=' + response.data.order.address + '">' + response.data.order.address + '</a></strong>\
                        </p>\
                        <p>\
                            <span>' + lang.ORDER_PAYMENT + ':</span>\
                            <strong><span class="icon-payment-' + response.data.order.payment_id + '"></span>' + data.paymentCurrentName + '</strong>\
                        </p>\
                        <p>\
                            <span>' + lang.ORDER_EMAIL + ':</span>\
                            <strong>' + response.data.order.user_email + '</strong>\
                        </p>\
                        <p>\
                            <span>' + lang.ORDER_PHONE + ':</span>\
                            <strong>' + response.data.order.phone + '</strong>\
                        </p>';
      // console.log(response.data.order.yur_info);
        if (response.data.order.yur_info.inn) {
          orderHtml += '\
            <ul class="order-edit-visible">\
              <li><span>Юр. лицо:</span> <strong>' + (response.data.order.yur_info.nameyur?response.data.order.yur_info.nameyur:'') + '</strong></li>\
              <li><span>Юр. адрес:</span> <strong>' + (response.data.order.yur_info.adress?response.data.order.yur_info.adress:'') + '</strong></li>\
              <li><span>ИНН:</span> <strong>' + (response.data.order.yur_info.inn?response.data.order.yur_info.inn:'') + '</strong></li>\
              <li><span>КПП:</span> <strong>' + (response.data.order.yur_info.kpp?response.data.order.yur_info.kpp:'') + '</strong></li>\
              <li><span>Банк:</span> <strong>' + (response.data.order.yur_info.bank?response.data.order.yur_info.bank:'') + '</strong></li>\
              <li><span>БИК:</span> <strong>' + (response.data.order.yur_info.bik?response.data.order.yur_info.bik:'') + '</strong></li>\
              <li><span>К/Сч:</span> <strong>' + (response.data.order.yur_info.ks?response.data.order.yur_info.ks:'') + '</strong></li>\
              <li><span>Р/Сч:</span> <strong>' + (response.data.order.yur_info.rs?response.data.order.yur_info.rs:'')+ '</strong></li>\
          </ul>'
        }
        orderHtml += '</div><div class="clear"></div>';
        if(data.info){
          orderHtml += '<div class="order-comment-block" >\
                  <span>' + lang.COMMENT + ' пользователя:</span>\
                  <span class="user-info-order" style="color: rgb(223, 16, 16);">' +  data.info + '</span></div>';
         }
        orderHtml += '<div class="order-comment-block '+(response.data.order.comment ? response.data.order.comment : 'order-edit-display')+'" >\
                  <span>' + lang.COMMENT + ' менеджера:</span>\
                  <span class="order-edit-visible">' + (response.data.order.comment ? response.data.order.comment : ' ') + '</span>\
                  <textarea name="comment" class="cancel-order-reason order-edit-display">' + (response.data.order.comment ? response.data.order.comment : '') + '</textarea>\
                </div>\
               ';
               
          
      return orderHtml;
    },      
            
    /**
     * Сохраняет настройки к заказам.
     */        
    savePropertyOrder: function() {
      var request = "mguniqueurl=action/savePropertyOrder&" + $("form[name=requisites]").formSerialize();

      admin.ajaxRequest(
              request,
              function(response) {
                admin.indication(response.status, response.msg);
                $('.property-order-container').slideToggle(function() {
                  $('.widget-table-action').toggleClass('no-radius');
                });
              }
      );

      return false;
    },
    /**
     * Просчитывает стоимость заказа, обновляет поля.
     */
    calculateOrder: function() {

      var totalSumm = 0;
      $('tbody#orderContent tr').each(function(i, element) {
        var price = $(this).find('td.price').data('price');
        var count = $(this).find('td.count input').val();
        var summ = count * price;
        $(this).find('td.summ').data('summ', summ);
        $(this).find('td.summ span').text(summ);
        totalSumm += summ;
      });

      var deliveryCost = $('#delivery option:selected').val();
      totalSumm = order.applyPromoCode(totalSumm); // применяем купон   
      var fullCost = totalSumm * 1 + deliveryCost * 1;
      $('#deliveryCost').text(deliveryCost);
      $('#totalPrice').text(totalSumm);
      $('#fullCost').text(fullCost);

      return false;
    },
    /**
     * Получает данные из формы фильтров и перезагружает страницу
     */
    getProductByFilter: function() {
      var request = $("form[name=filter]").formSerialize();
      admin.show("orders.php", "adminpage", request + '&applyFilter=1', admin.sliderPrice);
      return false;
    },
    /**
     * Получает скидку по промокоду.       
     */
    getPromoCode: function() {      
      $.ajax({
        type: "POST",
        url: mgBaseDir + "/ajax",
        data: {
          mguniqueurl: "action/getPromoCode",
          promocode: $("select[name=promocode]").val()
        },
        dataType: "json",
        cache: false,
        success: function(response) {
          admin.indication(response.status, response.msg);
          var percent = response.data.percent ? response.data.percent : '0';
          $(".promocode-percent span").text(percent);   
          order.calculateOrder();
        }
      });
      
    },
    /**
     * Применяет введенный купон.   
     */
    applyPromoCode: function(cost) {      
      var percent = $(".promocode-percent span").text() * 1;      
      return cost - (cost * percent / 100);
    },
    /**
     * изменяет строки в таблице товаров при редактировании изменении.                    
     */
    drawRowOrder: function(element, assocStatus) {
  
      var deliveryText = $('.order-preview #delivery option[name=' + element.delivery_id + ']').text();
      var paymentText = $('.order-preview #payment option[value=' + element.payment_id + ']').text();
      var statusName = $('.order-preview #orderStatus option:selected').text();
      var orderSumm = parseFloat(element.summ) + parseFloat(element.delivery_cost);

      // html верстка для  записи в таблице раздела  

      var row = '\
       <tr class="" order_id="' + element.id + '">\
       <td > ' + element.id + '</td>\
       <td class="add_date"> ' + element.date + '</td>\
       <td > ' + element.user_email + '</td>\
       <td > ' + deliveryText + '</td>\
       <td > <span class="icon-payment-'+element.payment_id+'"></span>' + paymentText + '</td>\
       <td > ' + orderSumm +' '+ admin.CURRENCY + '</td>\
       <td class="statusId id_' + element.status_id + '">\
       <span class="' + assocStatus[element.status_id] + '">' + statusName + '</span>\
       </td>\
       <td class="actions">\
       <ul class="action-list">\
       <li class="see-order" id="' + element.id + '">\
       <a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.SEE + '"></a>\
       </li>\
       <li class="order-to-csv"><a  data-id="' + element.id + '" class="tool-tip-bottom" href="javascript:void(0);" title="Сохранить в CSV"></a></li>\
       <li class="order-to-pdf"><a data-id="' + element.id + '" class="tool-tip-bottom" href="javascript:void(0);" title="Сохранить в PDF"></a></li>\
       <li class="order-to-print"><a  data-id="' + element.id + '" class="tool-tip-bottom" href="javascript:void(0);" title="Печать заказа"></a></li>\
       \
       <li class="delete-order" id="' + element.id + '">\
       <a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.DELETE + '"></a>\
       </li>\
       </ul>\
       </tr>';
    
      return row;
    
    },
    /**
     * функция для приема подписи из аплоадера
     */
    getSignFile: function(file) {
      var src = file.url;
      src = 'uploads' + src.replace(/(.*)uploads/g, '');
      $('.section-order .property-order-container input[name="sing"]').val(src);
      $('.section-order .property-order-container .singPreview').attr("src", file.url);
    },
    /**
     * функция для приема печати из аплоадера
     */
    getStampFile: function(file) {
      var src = file.url;
      src = 'uploads' + src.replace(/(.*)uploads/g, '');
      $('.section-order .property-order-container input[name="stamp"]').val(src);
      $('.section-order .property-order-container .stampPreview').attr("src", file.url);
    },
    /**
     * Печать заказа
     */
    printOrder: function(id) {
      admin.ajaxRequest({
        mguniqueurl: "action/printOrder",
        id: id
      },
      function(response) {
        //admin.indication(response.status, response.msg);     
        $('.block-print').html(response.data.html);
        window.print();
      }
      );
    },
    
    /**
     * Включает режим редактирования заказа
     */
    enableEditor: function() {
      $(".order-edit-display").show();
      $(".order-edit-visible").hide();
      $("#customer").change();
      includeJS(mgBaseDir+'/mg-core/script/jquery.maskedinput.min.js');
      $("input[name=phone]").mask("+7 (999) 999-99-99");
    },
    
          
    /**
     * Пересчет цены товара аяксом в форме добавления заказа.
     */
    refreshPriceProduct: function() {
      var request = $('.property-form').formSerialize();
      $('.orders-table-wrapper .property-form .addToCart').hide();
      // Пересчет цены.        
      $.ajax({
        type: "POST",
        url: mgBaseDir + "/product",
        data: "calcPrice=1&" + request,
        dataType: "json",
        cache: false,
        success: function(response) {
          if ('success' == response.status) {
            $('#order-data .product-block .price-sp').text(response.data.price_wc);
            $('#order-data .product-block .code-sp').text(response.data.code);
            $('.orders-table-wrapper .property-form .addToCart').show();
          }
        }
      });
    },
    /**
     * Клик по найденым товарам поиске в форме добавления заказа
     */
    viewProduct: function(elementIndex) {
      $('.search-block .errorField').css('display', 'none');
      $('.search-block input.search-field').removeClass('error-input');
      var product = order.searcharray[elementIndex];
      var html = '<a href="javascript:void(0)" class="custom-btn clear-product"><span>Очистить</span></a><div class="clear"></div>\
          <div class="image-sp"><img src="' + admin.SITE + '/uploads/thumbs/30_' + product.image_url + '"></div>';
      html +=
              '<div class="product-info"><div class="title-sp">' +
              '<a href="' + mgBaseDir + '/' + product.category_url + "/" + product.url +
              '" data-url="' + product.category_url +
              "/" + product.url + '" class="url-sp" target="_blanc">' +
              product.title + '</a>' +
              '</div>';
      html += '<div class="id-sp" style="display:none">' + product.id + '</div>';
      html += '<span class="price-sp">' + product.price + '</span>';
      html += '<span class="currency-sp"> ' + product.currency + '</span>';
      html += '<div class="code-sp">' + product.code + '</div>';
      html += '<div class="form-sp">' + product.propertyForm + '</div>';
      html += '</div>';
      html += '<div class="desc-sp">' + product.description + '</div>';
      html += '<div class="clear"></div>';
      $('#order-data .product-block').html(html);
      $('input[name=searchcat]').val('');
      $('.fastResult').hide();
    },
    /**
     * Добавляет товар в заказ
     */

    addToOrder: function(obj) {
      $('.search-block .errorField').css('display', 'none');
      $('.search-block input.search-field').removeClass('error-input');

      // Собираем все выбранные характеристики для записи в заказ.
      var prop = order.getPropPosition(obj);

      var position = {
        order_id: $('#order-data .id-sp').text(),
        id: $('#order-data .id-sp').text(),
        title: '<a href="' + mgBaseDir + '/' + $('#order-data .url-sp').data('url') + '" data-url="' + $('#order-data .url-sp').data('url') + '" class="href-to-prod"><span class="titleProd">' + $('#order-data .title-sp').text() + $('.property-form input[name=variant]:checked').next("span").text() + '</span></a>' + '<span class="property">' + prop + '</span>',
        prop: prop,
        code: $('#order-data .code-sp').text(),
        price: $('#order-data .price-sp').text(),
        count: 1,
        summ: $('#order-data .price-sp').text(),
        url: $('#order-data .url-sp').data('url'),
        image_url: $('#order-data .image-sp img').attr('src'),
      };

      var row = order.createPositionRow(position);
      var update = false;

      // сравним добавляемую строку с уже имеющимися, возможно нужно только увеличить количество
      $('.status-table tbody#orderContent tr').each(function(i, element) {
        var title1 = $(this).find('.title').html();
        var title2 = position.title;

        if ($(this).data('id') == position.id && title1 == title2) {
          var count = $(this).find('.count input').val();
          $(this).find('.count input').val(count * 1 + 1)
          update = true;
        }
      });

      // если не обновляем, то добавляем новую строку
      if (!update) {
        $('.status-table tbody#orderContent').append(row);
      }

      order.calculateOrder();
      $('.fastResult').hide();
      $('input[name=searchcat]').val('');
    },
  }
})();

// инициализациямодуля при подключении
order.init();