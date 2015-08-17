/**
 * Модуль работы с пользовательскими полями в админке
 */
var userProperty = (function() {

  var savedDataRow = {}; // данные редактируемой строки
  var cansel = false; // использовать возврат значений при отмене

  return {
    delimetr: "|",
    init: function() {

      // редактирования строки свойства
      $('body').on('click', '.userPropertyTable .edit-row', function() {
        userProperty.canselEditRow(savedDataRow.id);
        userProperty.hideActions(savedDataRow.id);
        userProperty.rowToEditRow($(this).attr('id'));
        userProperty.showActions($(this).attr('id'));
      });

      // сохранение строки свойства
      $('body').on('click', '.userPropertyTable .save-row', function() {
        userProperty.saveEditRow($(this).attr('id'));
        userProperty.hideActions($(this).attr('id'));
      });

      // отмена редактирования строки
      $('body').on('click', '.userPropertyTable .cancel-row', function() {
        userProperty.canselEditRow($(this).attr('id'));
        userProperty.hideActions($(this).attr('id'));
      });

      // открыть модалку с привязками к категориям
      $('body').on('click', '.userPropertyTable .see-order', function() {
        userProperty.openModalWindow('edit', $(this).attr('id'));
      });


      // удалить характеристику
      $('body').on('click', '.userPropertyTable .delete-order', function() {
        userProperty.deleteRow($(this).attr('id'));
      });

      // добавить характеристику
      $('body').on('click', '.addProperty', function() {
        userProperty.addRow();
      });

      //обработчик выбора типа
      $('body').on('change', 'select[name=type]', function() {
        userProperty.changetype($(this).val(), $(this).parents('tr').attr('id'));
      });


      //обработчик применения установленных наценок в редактировании продукта
      $('body').on('click', '.userField .apply-margin', function() {
        var tr = $(this).parents('tr');
        userProperty.applyMargin(tr);
        tr.find('select').show();
        tr.find('.setup-margin-product').show();
        tr.find('.fixed-panel-margin').show();
        tr.find('.panelMargin').remove();        
      });

      //обработчик нажатия на ссылку: установить наценки
      $('body').on('click', '.userField .setup-margin-product', function() {
        var select = $(this).parents('tr').find('select');
        select.after(userProperty.panelMargin(select));
        admin.initToolTip();
        select.hide();
        $(this).parents('.fixed-panel-margin').hide();
        $(this).hide();
      });

      //обработчик нажатия на ссылки: установить тип вывода
      $('body').on('click', '.userField .setup-type', function() {      
        var option = $(this).parents('tr');
        option.find('.setup-type').removeClass('selected');
        $(this).addClass('selected');        
      });
      

      //установка значений поумолчанию
      $('body').on('click', '.setDefaultVal', function() {

        var type = $(this).parents('tr').find('td[class=type] select').val();
        // при выбраном типе - список с одним значением
        if (type == 'select') {
          $(this).parents('tr').find('td[class=data] .itemData').removeClass('is-defaultVal');
          $(this).parents('.itemData').addClass('is-defaultVal');
          userProperty.setDefVal($(this).parents('tr').attr('id'), $(this).data('value'));
        }

        // при выбраном типе - набор опций
        if (type == 'assortmentCheckBox' || type == 'assortment') {

          if ($(this).parents('.itemData').hasClass('is-defaultVal')) {
            $(this).parents('.itemData').removeClass('is-defaultVal');
          } else {
            $(this).parents('.itemData').addClass('is-defaultVal');
          }

          var newdefval = '';
          $(this).parents('tr').find('.is-defaultVal .prop').each(function() {           
            newdefval += $(this).find('.propertyDataName').text()+'#'+$(this).find('input').val()+'#'+userProperty.delimetr;         
          });
          newdefval = newdefval.slice(0, -1);

          userProperty.setDefVal($(this).parents('tr').attr('id'), newdefval);
        }
      });


      // Удаляет доступный параметр характеристики
      $('body').on('click', '.delItem', function() {
        var propId = $(this).data('propid');
        var hiddenPropertyData = $('.userPropertyTable tr[id=' + propId + '] .hiddenPropertyData');
        var idItem = $(this).data('number');
        userProperty.dataDelItem(hiddenPropertyData, idItem, propId);

      });


      // Добавляет новый параметр характеристики
      $('body').on('click', '.addItemProp', function() {


        var obj = $(this).parents('td');
        var propid = $(this).data('propid');
        userProperty.saveMagrin(propid); // сохраняем наценки перед перестройкой пунктов
        //
        // отрезаем разделители по краяем
        var newItem = admin.trim(obj.find('input[name=newItem]').val(), userProperty.delimetr);
        // отрезаем разделители пробелы
        newItem = admin.trim(newItem);

        //проверяем не состоит ли строка из одних пробелов и разделителей?
        if (!newItem || admin.regTest(3, newItem)) {
          return false;
        }
        var text = obj.find('.hiddenPropertyData').text();

        if (text != '') {
          text = newItem + userProperty.delimetr + text;
        } else {
          text = newItem;
        }

        userProperty.dataUpdateItem(obj, text, propid);
        
        
        var type = $('.userPropertyTable tr[id=' + propid + '] td[class=type] span');
        var typeVal = type.attr('value');     
        if (typeVal != 'string' && typeVal != 'assortmentCheckBox') {   
          $('.userPropertyTable tr[id=' + propid + '] .setMargin').show();
        }
        
      });


      // Сохранение привязки к категориям.
      $('body').on('click', '#add-list-cat-wrapper .save-button', function() {
        userProperty.savePropertyCat($(this).attr('id'));

      });

    },
    // показывает дополнительные  действия при редактировании
    showActions: function(id) {
      $('.userPropertyTable tr[id=' + id + '] .cancel-row').show();
      $('.userPropertyTable tr[id=' + id + '] .save-row').show();
      $('.userPropertyTable tr[id=' + id + '] .edit-row').hide();      
    },
    // скрывает дополнительные  действия при редактировании
    hideActions: function(id) {
      $('.userPropertyTable tr[id=' + id + '] .cancel-row').hide();
      $('.userPropertyTable tr[id=' + id + '] .save-row').hide();
      $('.userPropertyTable tr[id=' + id + '] .edit-row').show();
      $('.userPropertyTable tr[id=' + id + '] .setMargin').hide();
    },
    /**
     *  Сохранение привязки к категориям.
     */
    savePropertyCat: function(id) {

      var category = '';
      $('select[name=listCat] option').each(function() {
        if ($(this).prop('selected')) {
          category += $(this).val() + userProperty.delimetr;
        }
      });

      category = category.slice(0, -1);

      admin.ajaxRequest({
        mguniqueurl: "action/saveUserPropWithCat",
        id: id,
        category: category
      },
      (function(response) {
        admin.indication(response.status, response.msg);
        admin.closeModal($('.b-modal'));
      }));
    },
    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового товара, либо для редактирования старого.
     */
    openModalWindow: function(type, id) {

      $('#add-list-cat-wrapper .save-button').attr('id', id);

      switch (type) {
        case 'edit':
          {
            var name = $('.userPropertyTable tr[id=' + id + '] td[class=name]');
            var nameVal = name.text();
            $('#modalTitle').text(lang.STNG_LIST_CAT + ': "' + nameVal + '"');
            userProperty.connectionCat(id, nameVal);
            break;
          }
      }

      // Вызов модального окна.
      admin.openModal($('.b-modal'));

    },
    connectionCat: function(id, name) {
      var nameVal = name;

      admin.ajaxRequest({
        mguniqueurl: "action/getConnectionCat",
        id: id
      },
      (function(response) {
        html = response.data.optionHtml;
        $('.user-fields-desc-wrapper .propertyName').text(nameVal);
        $('#select-category-form-wrapper select[name=listCat]').html(html);
        $('.cancelSelect').click(function() {
          $('select[name=listCat] option').prop('selected', false);
        });
        userProperty.convertCategoryIdToOption(response.data.selectedCatIds);
      }),
              $('#select-category-form-wrapper')
              );

    },
    /**
     * Выделяет категории в списке, которые привязаны к характеристике
     */
    convertCategoryIdToOption: function(selectedCatIds) {
      htmlOptionsSelected = selectedCatIds.split(',');
      function buildOption(element, index, array) {
        $('select[name="listCat"] [value="' + element + '"]').prop('selected', 'selected');
      }
      ;
      htmlOptionsSelected.forEach(buildOption);
    },
    /**
     * Получает все значения свойств из модального окна для сохранения в БД
     */
    getUserFields: function() {
      // первым делом работаем с множественными чекбоксовыми характеристиками
      // задача сводится к получению из набора чекбоксов относящихся к одному полю, все значения
      // и записать в строку через запятую.
      // Значения имеется ввиду не статус флага checked а именно значения этих боксов, то что написанно возле них.
      // олучить следует только те значения у который чекбокс с галочкой.
      // получившаяся строка будет иметь такой вид: 'val1,val2,val3'
      $('.userField .assortmentCheckBox').each(function() {

        var concatVal = '';
        $(this).find('.propertyCheckBox').map(function(i, checkbox) {
          if ($(checkbox).prop('checked')) {
            concatVal += $(checkbox).attr('name') + userProperty.delimetr;
          }
        });
        concatVal = concatVal.slice(0, -1);

        // создаем временный контэйнер чтобы метод смог записать эту строку как значение пользовательского свойства
        $(this).append('<input type="hidden" name="' + $(this).data("property-id") + '" class="property tempConteiner" value="' + concatVal + '">');

      });

      // заменяем разделители на кастомные в мультиселекте
      $('.userField select[multiple]').each(function() {
        var concatVal = '';
        if ($(this).val() != null) {
          $(this).val().map(function(element, val) {
            concatVal += element + userProperty.delimetr;
          });
          concatVal = concatVal.slice(0, -1);
          // создаем временный контэйнер чтобы метод смог записать эту строку как значение пользовательского свойства
          $(this).append('<input type="hidden" name="' + $(this).attr("name") + '" class="property tempConteiner" value="' + concatVal + '">');
          $(this).removeClass('property');
        }
      });

      // из мультиселектов и просто селектов получаем знацения наценок для каждой характеристики 
      // отправляем данные в этом же массиве добавляя  префикс margin_ перед индексом характеристики.
      $('.userField select').each(function() {
        var concatVal = '';
        $(this).find('option').each(function() {
          concatVal += $(this).attr('value') + userProperty.delimetr;
        });

        concatVal = concatVal.slice(0, -1);
        // создаем временный контэйнер чтобы метод смог записать эту строку как значение пользовательского свойства с префиксом префикс margin_
        $(this).append('<input type="hidden" name="margin_' + $(this).attr("name") + '" class="property tempConteiner" value="' + concatVal + '">');
        var type = $(this).parents('tr').find('.selected').data('type');   
        $(this).append('<input type="hidden" name="type_' + $(this).attr("name") + '" class="property tempConteiner" value="' + type + '">');
      });

      //собираем  все значения свойств для сохранения в БД
      var obj = '{';
      $('.userField .property').each(function() {
        obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
      });
      obj += '}';
    
      // удаляем временные пользовательские контейнеры
      $('.tempConteiner').remove();

      //преобразуем полученные данные в JS объект для передачи на сервер
      return eval("(" + obj + ")");

    },
    // преобразует системные записи типов в понятные пользователю
    typeToRead: function(type) {
      switch (type) {
        case 'string':
          {
            return lang.STRING
            break;
          }
        case 'select':
          {
            return lang.SELECT
            break;
          }
        case 'assortment':
          {
            return lang.ASSORTMENT
            break;
          }
        case 'assortmentCheckBox':
          {
            return lang.ASSORTMENTCHECKBOX
            break;
          }

      }
    },
    /**
     * Вывод имеющихся настроек в  разделе пользовательские характеристики
     */
    print: function() {
      //если список была ранее загружен, то не повторяем этот процесс
      if ($('.userField-settings-list').text() != "") {
        return false;
      }

      // получаем с сервера все доступные пользовательские параметры
      admin.ajaxRequest(
              {
                mguniqueurl: "action/getUserProperty"
              },
      function(response) {
        var html = '<table id="userPropertySetting" class="widget-table">\
            <thead class="yellow-bg">\
              <th>' + lang.STNG_USFLD_NAME + '</th>\
              <th>' + lang.STNG_USFLD_OPTION + '</th>\
              <th>' + lang.STNG_USFLD_TYPE + '</th>\
              <th style="display:none">' + lang.STNG_USFLD_DEF + '</th>\
              <th>' + lang.ACTIONS + '</th>\
            </thead><tbody class="userPropertyTable">';

        function buildRowsUserField(element, index, array) {
          var is_string = false;
          if (element.type == 'string') {
            is_string = true
          }
          html += '<tr id=' + element.id + '>\
              <td class="name">' + element.name + '</td>\
               <td class="data">' + userProperty.dataParseAndPrint(element.data, element.id, 'none', is_string, element.default) + '</td>\
               <td class="type"><span value="' + element.type + '">' + userProperty.typeToRead(element.type) + '</span></td>\
               <td class="default" style="display:none">' + element.default + '</td>\
                <td class="actions">\
                  <ul class="action-list">\
                      <li class="see-order connection" id="' + element.id + '"><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.STNG_USFLD_CAT + '"></a></li>\
                      <li class="save-row" id="' + element.id + '" style="display:none"><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.SAVE + '"></a></li>\
                      <li class="cancel-row" id="' + element.id + '" style="display:none"><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.CANCEL + '"></a></li>\
                      <li class="edit-row" id="' + element.id + '"><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.EDIT + '"></a></li>\
                      <li class="delete-order" id="' + element.id + '"><a class="tool-tip-bottom" href="javascript:void(0);"  title="' + lang.DELETE + '"></a></li>\
                  </ul>\
                </td>\
             </tr>';
         
        }
        ;



        if (response.data.length != 0) {
          response.data.allProperty.forEach(buildRowsUserField)
        } else {
          html += '<tr class="tempMsg">\
              <td colspan="5" align="center">' + lang.STNG_USFLD_MSG + '</td>\
             </tr>';
        }
        ;
        html += '</tbody></table>';
        $('.userField-settings-list').html(html);
        admin.initToolTip();

      },
              $('.userField-settings-list')
              );
    },
    //Делает поля доступными для редактирования
    rowToEditRow: function(id) {
      cansel = true;
      var name = $('.userPropertyTable tr[id=' + id + '] td[class=name]');
      var nameVal = name.text();
      name.html('<input name=name type=text class="custom-input tool-tip-bottom" title="' + lang.T_TIP_NAME_OPTION + '" value="' + name.text() + '">');

      var type = $('.userPropertyTable tr[id=' + id + '] td[class=type] span');
      var typeVal = type.attr('value');
      type.html('\
          <select name=type class="last-items-dropdown tool-tip-bottom" title="' + lang.T_TIP_TYPE_OPTION + '">\
          <option value="string">' + lang.STRING + '</option>\
          <option value="select">' + lang.SELECT + '</option>\
          <option value="assortment">' + lang.ASSORTMENT + '</option>\
          <option value="assortmentCheckBox">' + lang.ASSORTMENTCHECKBOX + '</option>\
          </select>'
              );

      type.find('option[value=' + typeVal + ']').prop('selected', 'selected');

      var data = $('.userPropertyTable tr[id=' + id + '] td[class=data]');
      var itemData = $('.userPropertyTable tr[id=' + id + '] td[class=data] .hiddenPropertyData').text();
      var dataVal = itemData;
      //data.html('<input name=type type=text class="custom-input tool-tip-bottom" title="'+lang.T_TIP_VAL_OPTION+'" value="'+data.text()+'">');
      $('.itemData').unbind();
 
      if (typeVal != 'string' && typeVal != 'assortmentCheckBox') {        
        userProperty.dataUpdateItem(data, itemData, id, false);
        $('.userPropertyTable tr[id=' + id + '] .setMargin').show();
      } else {       
        userProperty.changetype(typeVal, id, true);
     
      }

      var def = $('.userPropertyTable tr[id=' + id + '] td[class=default]');
      var defVal = def.text();

      savedDataRow = {
        id: id,
        name: nameVal,
        type: typeVal,
        data: dataVal,
        def: defVal
      };

      admin.initToolTip();
    },
    //Сохраняет редактирование
    saveEditRow: function(id) {
      cansel = false;
      var name = $('.userPropertyTable tr[id=' + id + '] td[class=name]');
      var nameVal = name.find('input').val();
      name.text(name.find('input').val());

      var type = $('.userPropertyTable tr[id=' + id + '] td[class=type]');
      var typeVal = type.find('select').val();
      type.html('<span value="' + typeVal + '">' + userProperty.typeToRead(typeVal) + '</span>');

      var data = $('.userPropertyTable tr[id=' + id + '] td[class=data]');

      // если выбран тип перечень, то не сохраняем стоимости
      if (typeVal == 'assortmentCheckBox') {      
        $('.userPropertyTable tr[id=' + id + '] .setMargin input').val('');
      }

      if (typeVal == 'string') {
        var dataVal = data.find('input').val();
        $('.userPropertyTable tr[id=' + id + '] td[class=default]').text(dataVal);
        userProperty.dataUpdateItem(data, dataVal, id, true, true);
      } else {
        userProperty.saveMagrin(id);
        var dataVal = data.find('.hiddenPropertyData').text();
        userProperty.dataUpdateItem(data, dataVal, id, true);
      }


      var def = $('.userPropertyTable tr[id=' + id + '] td[class=default]');
      var defVal = def.text();

      // удаляем обработчик показа установки дефолтного значения
      $('.itemData').unbind();


      admin.ajaxRequest({
        mguniqueurl: "action/saveUserProperty",
        id: id,
        name: nameVal,
        type: typeVal,
        data: dataVal,
        default: defVal
      },
      (function(response) {
        admin.indication(response.status, response.msg);
      }));
    },
    //Отменяет редактирование
    canselEditRow: function(id) {
      if (cansel) {
        var name = $('.userPropertyTable tr[id=' + id + '] td[class=name]');
        name.text(savedDataRow.name);

        var type = $('.userPropertyTable tr[id=' + id + '] td[class=type]');
        type.html('<span value="' + savedDataRow.type + '">' + userProperty.typeToRead(savedDataRow.type) + '</span>');

        var typeVal = $('.userPropertyTable tr[id=' + id + '] td[class=type] span').attr('value');
   
        var data = $('.userPropertyTable tr[id=' + id + '] td[class=data]');  
        if (typeVal != 'string') { 
          userProperty.dataUpdateItem(data, savedDataRow.data, id, true);
        } else {       
          userProperty.dataUpdateItem(data, savedDataRow.data, id, true, true);
        }
      
        

        var def = $('.userPropertyTable tr[id=' + id + '] td[class=default]');
        def.text(savedDataRow.def);
        cansel = false;
        
        
      
        // удаляем обработчик показа установки дефолтного значения
        $('.itemData').unbind();
      
      }
    },
    //Добавляет новую строку
    addRow: function() {
      admin.ajaxRequest({
        mguniqueurl: "action/addUserProperty"
      },
      function(response) {
        admin.indication('error', 'Добавление характеристик ограничено бесплатной версией');   
      }

      );

    },
    deleteRow: function(id) {
      if (confirm(lang.DELETE + '?')) {
        admin.ajaxRequest({
          mguniqueurl: "action/deleteUserProperty",
          id: id
        },
        (function(response) {
          admin.indication(response.status, response.msg);
          $('.userPropertyTable tr[id=' + id + ']').remove();
          if ($(".userPropertyTable tr").length == 0) {
            var html = '<tr class="tempMsg">\
                  <td colspan="5" align="center">' + lang.STNG_USFLD_MSG + '</td>\
                 </tr>';
            $('.userPropertyTable').append(html);

          }
          ;
        }));
      }
    },
    /**
     * Получает весь набор доступных пользовательских характеристик из базы
     */
    getUserFromBD: function() {

    },
    /**
     * сортировка свойств по алфавиту
     */
    propertySort: function(arr) {
      return arr.sort(function(a, b) {

        var compA = a.toLowerCase();
        var compB = b.toLowerCase();
        return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
      })
    },
    /**
     * Парсит строку с допустимыми значениями, приводя в нормльный html вид
     * @param {type} defval - значение по умолчанию
     * @param {type} data - строка с набором значений разделенных запятыми
     * @param {type} propId - id пользовательской характеристики
     * @param {type} display - будут ли показаны элементы редактирования
     * @param {type} is_sting - строковый тип свойста
     * @returns {String}
     */
    dataParseAndPrint: function(data, propId, display, is_sting, defval) {
      var html = '<div class="hiddenPropertyData" style="display:none">' + data + '</div>';
      if (is_sting) {
        html += data;
      } else {

        var strArr = data.split(userProperty.delimetr);

        userProperty.propertySort(strArr);

        // чистим строку дефолтных значений от наценок
        defval = defval.split(userProperty.delimetr);
        for (var i = 0; i <= defval.length - 1; i++) {          
           var propertyData = userProperty.getMarginToProp(defval[i]);  
           defval[i] = propertyData.name;
        }

        for (var i = 0; i <= strArr.length - 1; i++) {
          if (strArr[i]) {
            var clas = 'itemData';
            var propertyData = userProperty.getMarginToProp(strArr[i]);      
            if ($.inArray(propertyData.name, defval) != -1) {
              var clas = 'itemData is-defaultVal';
            }
            

            var currency = $('input[name=currency]').val();
            html += '<div class="' + clas + '"><div class="prop"><span class="propertyDataName">' + propertyData.name + '</span>\
                      <span class="setMargin" style="display:none" > + <input type="text" value="' + propertyData.margin + '"/> ' + currency + ' </span>\
                      <a href="javascript:void(0);" onclick="return false" class="delItem" data-propid="' + propId + '" data-number="' + i + '" style="display:' + display + '">X</a>\
                    </div>\
                      <a href="#" onclick="return false" class="setDefaultVal tool-tip-bottom" style="display:none" title="'+lang.SETUP_DEFAULT+'" data-value="' + propertyData.name + '">'+lang.SETUP_DEFAULT+'</a>\
                    </div>';
          }
        }
      }
      return html;
    },
    /*
     * Возвращает значение наценки из характеристики, которое отделяется от названия #Цена#
     * пример красный#700# получим 700 и название красный.
     */
    getMarginToProp: function(str) {
      var margin = /#([\d\.\,-]*)#$/i.exec(str);
      var parseString = {name: str, margin: 0}
      if (margin != null) {
        parseString = {name: str.slice(0, margin.index), margin: margin[1]}
      }
      return parseString;
    },
    // Обновляет состояние ячейки после добавления или удаления из нее параметра
    // hiddenData - скрытое значение, записанное в одну строку с разделителями,
    // idItem - номер удаляемого варианта,
    // propId - номер характеристики
    dataUpdateItem: function(td, hiddenPropertyData, propid, noedite, is_sting) {
      td.find('.hiddenPropertyData').text(hiddenPropertyData);
      var defval = $('.userPropertyTable tr[id=' + propid + '] td[class=default]').text();
      var html = '';
      if (noedite) {
        td.html(userProperty.dataParseAndPrint(hiddenPropertyData, propid, 'none', is_sting, defval));
      } else {
        td.html(
                '<input name="newItem" type="text" class="custom-input tool-tip-bottom" title="' + lang.T_TIP_VAL_OPTION + '" value=""> <a href="javascript:void(0);" class="addItemProp add-variant" data-propid="' + propid + '"><span>'+lang.ADD_POINT+'</span></a><br/>' +
                userProperty.dataParseAndPrint(hiddenPropertyData, propid, 'inline-block', is_sting, defval));
      }

      //установка значения по умолчанию
      td.find('.itemData').unbind();
      td.find('.itemData').hover(function() {
        $(this).find('.setDefaultVal').css({'display': 'inline-block'});
      },
              function() {
                $(this).find('.setDefaultVal').hide();
              }
      );

    },
    
    //Удаляет из строки с допустимыми значениями, выбраное значение
    // hiddenData - скрытое значение, записанное в одну строку с разделителями,
    // idItem - номер удаляемого варианта,
    // propId - номер характеристики
    dataDelItem: function(hiddenData, idItem, propId) {

      //console.log(hiddenData.text());
      var strArr = hiddenData.text().split(userProperty.delimetr)
      userProperty.propertySort(strArr);
      // console.log(strArr);
      strArr.splice(idItem, 1);
      // console.log(strArr);
      //hiddenData.text(strArr.join(userProperty.delimetr)); 
      // запрос на сервер когда перезаписалось значение, то удаляем вариант
      $('.delItem[data-propId=' + propId + '][data-number=' + idItem + ']').parents('.itemData').remove();
      //перестроение  индексов элементов в соответствии с полученным массивом      
      userProperty.dataUpdateItem(hiddenData.parents('td'), strArr.join(userProperty.delimetr), propId, false, false);
      var type = $('.userPropertyTable tr[id=' + propId + '] td[class=type] span');
      var typeVal = type.attr('value');     
      if (typeVal != 'string' && typeVal != 'assortmentCheckBox') {   
        $('.userPropertyTable tr[id=' + propId + '] .setMargin').show();
      }
      
    },
            
    // Сохраняет установленные наценки для каждого пункта характеристик
    // hiddenData - скрытое значение пунктов, записанное в одну строку с разделителями,
    // propId - номер характеристики        
    saveMagrin: function(propId) {
      var hiddenData = $('.userPropertyTable tr[id=' + propId + '] .hiddenPropertyData');
      var hiddenDataText = hiddenData.text();
   
      if($('.userPropertyTable tr[id=' + propId + '] .itemData ').length!=0){
        hiddenDataText = "";    
     
        $('.userPropertyTable tr[id=' + propId + '] .itemData ').each(function() {

          //если в поле введено число болье нуля то записываем его к характеристикам
          var margin = $(this).find('input[type=text]').val();
          if (margin * 1 != 0 && !isNaN(margin)) {
            margin = '#' + margin + '#';
          } else {
            margin = "";
          }
          hiddenDataText += $(this).find('.propertyDataName').text() + margin + userProperty.delimetr;
          // console.log($(this).find('.propertyDataName').text()+margin);
        });
        hiddenDataText = hiddenDataText.slice(0, -1);
      }
      
      hiddenData.text(hiddenDataText);
    },
            
            
    /**
     * Обрабатывает смену  типа характеристики
     * @param {type} hiddenData
     */
    changetype: function(type, propId, firstEdit) {
     // if(!firstEdit){
        userProperty.saveMagrin(propId); // сохраняем наценки перед перестройкой пунктов     
    //  }
     
      var data = $('.userPropertyTable tr[id=' + propId + '] td[class=data]');
      var text = $('.userPropertyTable tr[id=' + propId + '] td[class=data] .hiddenPropertyData').text();
      if (type == 'string') {
        var html = '<div class="hiddenPropertyData" style="display:none">' + text + '</div> <input type="text" name="data" value="' + text + '"/>';
        data.html(html);
      } else {

        userProperty.dataUpdateItem(data, text, propId, false);
        $('.userPropertyTable tr[id=' + propId + '] .setMargin').show();
        
        if (type == 'select') {
          data.find('.setDefaultVal').first().click();
        }

        if (type == 'assortmentCheckBox') {
          $('.userPropertyTable tr[id=' + propId + '] .setMargin').hide();
        }
        
      }

    },
    /**
     * 
     * @param {type} propId - id характеристики 
     * @param {type} val - значение  по умолчанию
     * @returns {undefined}
     */
    setDefVal: function(propId, val) {
      var data = $('.userPropertyTable tr[id=' + propId + '] td[class=default]').text(val);
    },
    /**
     * Панель для настройки наценок к каждому товару
     * select - объект содержащий все доступные згачения характеристики
     */
    panelMargin: function(select) {

      var html = '<div class = "panelMargin" ><table>';
      select.find('option').each(function() {

        var parseProp = userProperty.getMarginToProp($(this).val());
        var selected = '';
        if ($(this).attr('selected') == 'selected') {
          selected = ' selected="selected" ';
        }
        html += "<tr><td class='panelMargin-unit'><span class='custom-text'>" + parseProp.name + ":</span>"+"</td><td> <input type='text' "
                + selected + " value='" + parseProp.margin + "' data-propname='" + parseProp.name + "' class='price-input'/>"+admin.CURRENCY+'</td></tr>';
      });
      html += '</table><br/> <a href="javascript:void(0);" class="apply-margin custom-btn tool-tip-bottom" title="'+lang.APPLY+'"><span>'+lang.APPLY+'</span></a></div>';
      return html;
    }, 
    /**
     * Применяет установленные в panelMargin наценки
     * tr - строка таблицы полей в которой хранятся наценки и список
     */
    applyMargin: function(tr) {
      var option = '';
      var selected = ' selected="selected" ';
      // формируем новый список из данных в панеле наценок
      tr.find('.panelMargin input[type=text]').each(function() {
        if(isNaN($(this).val())){
          $(this).val('0');
        }
        var selected = '';
        if ($(this).attr('selected') == 'selected') {
          selected = ' selected="selected" ';
        }
        option += '<option value="' + $(this).data('propname') + '#' + $(this).val() + '#' + '"' + selected + '>'
                + $(this).data('propname') + '</option>';

      });

      // вставляем сформированный список  на место
      tr.find('select').html(option);
     
    },
    /**
     * Заполняет поля модального окна продуктов данными
     * allProperty - объект содержащий все доступные пользовательские характеристики
     * userFields - объект содержит  значения пользовательских характеристик для текущего продукта
     */
    createUserFields: function(container, userFields, allProperty) {

     
      if (!allProperty)
        return false;
      var htmlOptions = '';
      var htmlOptionsSelected = '';
      var htmlOptionsSetup = ''; // установленные наценки для текущего продукта
      var htmlUserField = '';
      var htmlCheckBox = '';
      var curentProperty = '';
      //console.log(userFields);
      //строит html элементы из полученных данных
      function printToLog(element, index, array) {
        console.log("a[" + element.id + "] = " +
                " - " + element.name +
                " - " + element.type +
                " - " + element.default +
                " - " + element.data
                );
      }

      // Проверяет,
      // было ли уже установлено пользоватльское свойство,
      // и возвращает его значение
      // propertyId - идентификатор свойства
      function getUserValue(propertyId) {
        var userValue = false;
        if (!userFields){
          return;     
        }
        userFields.forEach(function(element, index, array) {         
          if (element.property_id == propertyId) {
            userValue = {value: element.value, product_margin: element.product_margin, type_view:element.type_view};
          }
        });
        return userValue;
      }


      function buildCheckBox(element, index, array) {
        var checked = '';
        // для мульти списка проверяем наличие  значения в массиве htmlOptionsSelected
        if (htmlOptionsSelected instanceof Array) {
          if (htmlOptionsSelected.indexOf(element) != -1) {
            checked = 'checked="checked"';
          }
        } else {
          // для простого селекта соответствие значению htmlOptionsSelected
          if (htmlOptionsSelected == element) {
            checked = 'checked="checked"';
          }
        }

        // тут надо соорудить что-то для чекбоксов, чтобы потом получить значения
        htmlCheckBox += '<label>' + element + '<input type="checkbox" class="propertyCheckBox" ' + checked + ' name="' + element + '"/></label>';
      }
      

      function buildOption(element, index, array) {
        //  var propertyData = userProperty.getMarginToProp(strArr[i]);
        // отделяем цену от значения свойства
        var dataProp = userProperty.getMarginToProp(element);
        element = dataProp.name;
        var margin = dataProp.margin;

        htmlOptionsSetup.forEach(function(item, index, array) {
          if (item.name == element) {
            margin = item.margin;
          }
        });

        var selected = '';
        // для мульти списка проверяем наличие  значения в массиве htmlOptionsSelected
        if (htmlOptionsSelected instanceof Array) {

          htmlOptionsSelected.forEach(function(item, index, array) {
            if (item.name == element) {
              selected = 'selected="selected"';
              margin = item.margin;
            }
          });

        } else {
          // для простого селекта соответствие значению htmlOptionsSelected
          if (htmlOptionsSelected == element) {
            selected = 'selected="selected"';
          }
        }
        htmlOptions += '<option ' + selected + ' value="' + element + '#' + margin + '#">' + element + '</option>';
      }
      

      //строит html элементы из полученных данных
      function buildElements(property, index, array) {        
        
        // если наименование не задано то не выводить характеристику
        if(property.name==null){          
          return false;
        } 
       
        var html = '';
        var created = false;

        // для пользовательского поля типа string
        if (property.type == 'string') {
          var userValue = getUserValue(property.id);
          var value = (userValue.value) ? userValue.value : property.default;

          html = '<tr><td><span class="custom-text">' + property.name + ': </span></td>'
               + '<td><input class="property custom-input" name="' + property.id + '" type="text" value="' + value + '"></td></tr>';
          created = true;
        }
       
        // для пользовательского поля типа assortment или select
        if (property.type == 'select' || property.type == 'assortment') {
          
          var multiple = (property.type == 'assortment')?'multiple':'';// определяем будет ли строиться мульти список или обычный
          
          html = '<tr><td><span class="custom-text">' + property.name + ': </span></td>'
               + '<td><select class="property last-items-dropdown" name="' + property.id + '"'+multiple+'>';
          // обнуляем список опций
          htmlOptions = '';
          
          // получаем  настройки характеристики (выбранные пункты и их стоимости в текущем товаре)
          var userValue = getUserValue(property.id);
          
          var arrayValues = null;
          // если ранее настройки небыли установлены в товаре, то берутся дефолтные, заданные в разделе характеристик
          if (userValue) {
            arrayValues = userValue.value.split(userProperty.delimetr);
          } else {
            arrayValues = property.default.split(userProperty.delimetr);
          }

       
            htmlOptionsSelected = []; // массив выделеных пунктов списка БЕЗ ЦЕН, чтобы можно было сравнить с дефолтным пунктами и выделить нужные
            arrayValues.forEach(function(element, index, array) {
              var dataProp = userProperty.getMarginToProp(element);
              htmlOptionsSelected.push(dataProp);
            });


            htmlOptionsSetup = []; // массив установленых ранее настроек для текущего товара значений
            if(userValue.product_margin){
              userValue.product_margin.split(userProperty.delimetr).forEach(function(element, index, array) {
                var dataProp = userProperty.getMarginToProp(element);
                htmlOptionsSetup.push(dataProp);
              });
            }
     

          // генерируем список опций
          property.data.split(userProperty.delimetr).forEach(buildOption);

          // присоединяем список опций к основному контенту
          html += htmlOptions;
       
          // закрываем селект
          html += '</select>\
            ';
            // формируем панель кнопок устанавливающих тот или иной тип вывода характеристики
          html += '<div class="fixed-panel-margin"><a href="javascript:void(0);" class="setup-margin-product tool-tip-bottom" title="'+lang.T_TIP_SETUP_MARGIN+'" ><span>'+lang.SETUP_MARGIN+'</span></a>';
         
         
            // формирование панели настроек вывода в публичной части
            var selected = '';

            if(userValue.type_view=="select" || userValue == false){
              selected = 'selected';
            }
            html += '<a href="javascript:void(0);" title="'+lang.T_TIP_PRINT_SELECT+'" class="tool-tip-bottom custom-btn type-select setup-type '+selected+'" data-type="select"><span></span></a>';
            
            //  вывод чекбоксами доступен только для мульти селекта
            if(multiple=='multiple'){
               selected = '';
               if(userValue.type_view=="checkbox"){
                 selected = 'selected';
               }
               html += '<a href="javascript:void(0);" title="'+lang.T_TIP_PIRNT_CHECK+'" class="tool-tip-bottom custom-btn type-checkbox setup-type '+selected+'" data-type="checkbox"><span></span></a>';
            }
            
            selected = '';
            if(userValue.type_view=="radiobutton"){
              selected = 'selected';
            }
            html += '<a href="javascript:void(0);" title="'+lang.T_TIP_PIRNT_RADIO+'" class="tool-tip-bottom custom-btn type-radiobutton setup-type '+selected+'" data-type="radiobutton"><span></span></a>';
                 
       
          html += '</div><td></tr>';
          created = true;
        }
        
       
        // для пользовательского поля типа assortmentCheckBox
        if (property.type == 'assortmentCheckBox') {
          html = '<tr><td><span class="custom-text">' + property.name + ':</span>' 
               + '</td><td><div  class="assortmentCheckBox" data-property-id="' + property.id + '">';
          // обнуляем список опций
          htmlCheckBox = '';

          // устанавливаем выбраный элемент, чтобы отловить
          // его при построении опций и выделить его в списке
          var userValue = getUserValue(property.id);
          htmlOptionsSelected = (userValue.value) ? userValue.value.split(userProperty.delimetr) : property.default.split(userProperty.delimetr);

          curentProperty = property.id;
          // генерируем список чекбоксов
          property.data.split(userProperty.delimetr).forEach(buildCheckBox);

          // присоединяем список опций к основному контенту
          html += htmlCheckBox;

          // закрываем селект
          html += '</div></td></tr>';
          created = true;
        }
        
      //  if(userValue){  
      //    console.log(property);
      //    console.log(index);
      //    console.log(array); 
        
            
          /*Дублирует к каждой характеристики по одному пустом блоку*/
          htmlUserField += '<div class="userfd">' + html + '</div>';
      //  }
      }

      htmlUserField = '';  
     
      allProperty.forEach(buildElements);
     
      container.html('<table class="user-field-table"><tbody>' + htmlUserField + '</tbody></table>');

    }
  }
})();

// инициализация модуля при подключении
userProperty.init();