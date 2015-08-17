
/**
 * Модуль для  раздела "Товары".
 */
var catalog = (function () {
  return {
    errorVariantField: false,
    memoryVal: null, // HTML редактор для   редактирования страниц
    deleteImage: '', // список картинок помеченых на удаление, при сохранении товара, данный список передается на сервер и картинки удаляются физически
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init: function() {
        includeJS(admin.SITE+'/mg-core/script/jquery.bxslider.min.js');
      // Вызов модального окна при нажатии на кнопку добавления товаров.
      $('.admin-center').on('click', '.section-catalog .add-new-button', function(){
        catalog.openModalWindow('add');        
      });

      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '.section-catalog .show-filters', function(){
        $('.import-container').slideUp();
        $('.filter-container').slideToggle(function(){
          $('.widget-table-action').toggleClass('no-radius');
        });  
      });


      // Применение выбраных фильтров
      $('.admin-center').on('click', '.section-catalog .filter-now', function(){
        admin.indication('error','Ограничено версией');
        return false;
      });
      
       $('body').on('click', '.section-catalog .get-csv', function(){
        admin.indication('error','Ограничено версией');
        return false;      
      });
      
      $('body').on('click', '.section-catalog .get-yml-market', function(){
        admin.indication('error','Ограничено версией');
        return false;      
      });
      
      // Вызов модального окна при нажатии на кнопку изменения товаров.
      $('.admin-center').on('click', '.section-catalog .clone-row', function(){
        catalog.cloneProd($(this).attr('id'));

      });

      // Вызов модального окна при нажатии на кнопку изменения товаров.
      $('.admin-center').on('click', '.section-catalog .import-csv', function(){
        $('.filter-container').slideUp();
        $('.import-container').slideToggle(function(){
          $('.widget-table-action').toggleClass('no-radius');
        });
       
      });
      
       // Обработчик для загрузки изображения на сервер, сразу после выбора.
      $('body').on('change', '.section-catalog input[name="upload"]', function(){
     
         catalog.uploadCsvToImport();        
      });
      
      // Обработчик для загрузки изображения на сервер, сразу после выбора.
      $('body').on('click', '.section-catalog .repeat-upload-csv', function(){
        $('.import-container input[name="upload"]').val('');
        $('.block-upload-сsv').show();
        $('.block-importer').hide();        
        $('.repat-upload-file').show();
        $('.cancel-importing').hide();        
        $('.message-importing').text('');
        catalog.STOP_IMPORT=false;;
       
      });
           
      
      // Обработчик для загрузки изображения на сервер, сразу после выбора.
      $('body').on('click', '.section-catalog .start-import', function(){
         $('.repat-upload-file').hide();        
         $('.block-importer').hide();
         $('.cancel-importing').show();     
         catalog.startImport($('.block-importer .uploading-percent').text());        
      });
      
      // Останавливает процесс загрузки товаров.
      $('body').on('click', '.section-catalog .cancel-import', function(){      
         catalog.canselImport();        
      });
      
      

      // Вызов модального окна при нажатии на кнопку изменения товаров.
      $('.admin-center').on('click', '.section-catalog .edit-row', function(){
        catalog.openModalWindow('edit', $(this).attr('id'));
      });

      // Удаление товара.
      $('.admin-center').on('click', '.section-catalog .delete-order', function(){
        catalog.deleteProduct(
          $(this).attr('id'),
          $('tr[id='+$(this).attr('id')+'] .uploads').attr('src'),
          false          
        );
      });
      
            
      // Нажатие на кнопку - рекомендуемый товар
      $('.admin-center').on('click', '.section-catalog .recommend', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');

        if($(this).hasClass('active')) {       
          catalog.recomendProduct(id, 1);                 
          $(this).attr('title', lang.PRINT_IN_RECOMEND);
        }
        else {       
          catalog.recomendProduct(id, 0);
          $(this).attr('title', lang.PRINT_NOT_IN_RECOMEND);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Нажатие на кнопку - активный товар
      $('.admin-center').on('click', '.section-catalog .visible', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');

        if($(this).hasClass('active')) {       
          catalog.visibleProduct(id, 1); 
          $(this).attr('title', lang.ACT_V_PROD);
        }
        else {       
          catalog.visibleProduct(id, 0);
          $(this).attr('title', lang.ACT_UNV_PROD);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
       // Нажатие на кнопку - новый товар
      $('.admin-center').on('click', '.section-catalog .new', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');

        if($(this).hasClass('active')) {       
          catalog.newProduct(id, 1);                 
          $(this).attr('title', lang.PRINT_IN_NEW);
        }
        else {       
          catalog.newProduct(id, 0);
          $(this).attr('title', lang.PRINT_NOT_IN_NEW);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      
      // Выделить все товары.
      $('.admin-center').on('click', '.section-catalog .checkbox-cell input[name=product-check]', function(){

        if($(this).val()!='true'){
          $('.product-tbody input[name=product-check]').prop('checked','checked');
          $('.product-tbody input[name=product-check]').val('true');
        }else{
          $('.product-tbody input[name=product-check]').prop('checked', false);
          $('.product-tbody input[name=product-check]').val('false');
        }
      });
      
      // Удаление всех выведенных товаров на странице.
      $('.admin-center').on('click', '.section-catalog .removeAllProduct', function(){

        if($('.product-tbody input[name=product-check][value=true]').length>0){
        if(confirm(lang.DELETE_ALL_PROD+'?')){    
       
          $('.product-tbody tr').each(function(){              
            if($(this).find('input[name=product-check]').prop('checked')){           
              catalog.deleteProduct(
                $(this).attr('id'),
                $('tr[id='+$(this).attr('id')+'] .uploads').attr('src'),
                true
              );
            }
          });        
          $('.id-product input[name=product-check]').prop('checked', false);
          admin.indication('success', lang.DELETED_PROD);    
          
        } else{
          return false;
        }
       }else{
          admin.indication('error', lang.NOT_DELETED_PROD);         
          }
      });
      

      // Сброс фильтров.
      $('.admin-center').on('click', '.section-catalog .refreshFilter', function(){
        admin.show("catalog.php","adminpage","refreshFilter=1",admin.sliderPrice);
        return false;
      });

     // Обработка выбраной категории (перестраивает пользовательские характеристики).
      $('body').on('change', '#productCategorySelect', function(){
        //достаем id редактируемого продукта из кнопки "Сохранить"
        var product_id=$(this).parents('.add-product-form-wrapper').find('.save-button').attr('id');
        var category_id=$(this).val();
        catalog.generateUserProreprty(product_id, category_id);
     
      });

      // Обработчик для загрузки изображения на сервер, сразу после выбора.
      $('body').on('change', 'input[name="photoimg"]', function(){
        var img_container = $(this).parents('.product-upload-img');
        if($(this).val()){
          catalog.addImageToProduct(img_container);
        }
      });


      // Удаляение изображения товара, как из БД таи физически с сервера.
      $('body').on('click', '.cancel-img-upload', function(){
        var img_container = $(this).parents('.product-upload-img');
        catalog.delImageProduct($(this).attr('id'),img_container);
        
      });

      // Сохранение продукта при на жатии на кнопку сохранить в модальном окне.
      $('body').on('click', '#add-product-wrapper .save-button', function(){
        catalog.saveProduct($(this).attr('id'));       
      });

       // Нажатие ентера при вводе в строку поиска товара
      $('body').on('keypress', '.widget-table-action input[name=search]', function(e){
        if(e.keyCode==13){  
          catalog.getSearch($(this).val());          
        }
      });


       // Добавить вариант товара
      $('body').on('click', '.product-table-wrapper .add-position', function(){             
        catalog.addVariant($('.variant-table'));
      });
      
       // Удалить вариант товара
      $('body').on('click', '.product-table-wrapper .del-variant', function(){        
        if($('.variant-table tr').length==3){
        
          $('.variant-table .hide-content').hide();
          $('.variant-table').data('have-variant','0');
          $('.variant-table-wrapper').css('width','383px');
          $('.variant-table tr td').css('padding','5px 18px 5px 0');
          $(this).parents('tr').remove();        
        } else{
          $(this).parents('tr').remove();
        }
        
      });
      
       // при ховере на иконку картинки варианта  показывать  имеющееся изображение
       $('body').on('mouseover mouseout', '.product-table-wrapper .img-variant',  function(event) {
        if (event.type == 'mouseover') {
          $(this).parents('td').find('.img-this-variant').show();
        } else {
          $(this).parents('td').find('.img-this-variant').hide();
        }
      });
      
      // При получении фокуса в поля для изменения значений, запоминаем каким было  исходное значение
      $('.admin-center').on('focus', '.section-catalog .fastsave', function(){       
        catalog.memoryVal = $(this).val();   
      });
      
      // сохранение параметров товара прямо из общей таблицы товаров при потере фокуса
      $('.admin-center').on('blur', '.section-catalog .fastsave', function(){       
        //если введенное отличается от  исходного, то сохраняем.    
        if(catalog.memoryVal!=$(this).val()){          
          catalog.fastSave($(this).data('packet'), $(this).val(),$(this));                 
        }
        catalog.memoryVal = null; 
      });
      
      // сохранение параметров товара прямо из общей таблицы товаров при нажатии ентера
      $('.admin-center').on('keypress', '.section-catalog .fastsave', function(e){
        if(e.keyCode==13){
          $(this).blur();
        }
      });         
    
     // Обработчик для загрузки изображения на сервер, сразу после выбора.
      $('body').on('change', 'input[name="photoimg"]', function(){
        // отправка картинки на сервер
        var imgContainer = $(this).parents('td');     
        var mguniqueurl = "action/addImage";
        var nowatermark = $(this).hasClass('img-variant')?1:0;
        if(nowatermark) {
          mguniqueurl = "action/addImageNoWaterMark";
        }
        $(this).parent('form').ajaxForm({
          type:"POST",
          url: "ajax",
          data: {
            mguniqueurl: mguniqueurl      
          },
          cache: false,
          dataType: 'json',
          success: function(response){
         
            admin.indication(response.status, response.msg);
            if(response.status != 'error'){
              var src=admin.SITE+'/uploads/'+response.data.img;
              imgContainer.find('img').attr('src',src).attr('filename', response.data.img);
            }else{
              var src=admin.SITE+'/mg-admin/design/images/no-img.png';
              imgContainer.find('img').attr('src',src).attr('filename', 'no-img.png');
            }
          }
        }).submit();
      });

      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-catalog .countPrintRowsProduct', function(){
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsProduct",
          count: count
        },
        function(response) {         
          admin.refreshPanel();
        }
        );

      });


      // Подобрать продукты по поиску
      $('.admin-center').on('click', '.section-catalog .searchProd', function(){
        var keyword =  $('input[name="search"]').val();
        catalog.getSearch(keyword);
      });
      
      
       //Добавить изображение для продукта
       $('body').on('click', '.add-product-form-wrapper .add-image', function(){        
         var src=admin.SITE+'/mg-admin/design/images/no-img.png';
         var row = catalog.drawControlImage(src, true);         
         $('.small-img-wrapper').prepend(row);    
         admin.initToolTip();
       });
       
       // для главной картинки меняем классы сохраняем в буфер и удаляем
        
       //Сделать основной картинку продукта
       $('body').on('click', '.main-image', function(){     
          var obj = $(this).parents('.product-upload-img');         
          catalog.upMainImg(obj);
       });
       
       //Клик по кнопке Яндекс Маркет
       $('body').on('click', '.get-yml-market', function(){        
     
       });
        
    
    },

             
    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового товара, либо для редактирования старого.
     */
    openModalWindow: function(type, id) {
   
      switch (type) {
        case 'edit':{
          catalog.clearFileds();
          $('.add-product-table-icon').text('Редактирование товара');
          catalog.editProduct(id);
          break;
        }
        case 'add':{

          $('.add-product-table-icon').text('Добавление нового товара');
          catalog.clearFileds();
          
          var src=admin.SITE+'/mg-admin/design/images/no-img.png'
          var row = catalog.drawControlImage(src, false);                  
          $('.small-img-wrapper').before(row);
          $('.main-img-prod .main-image').hide();

          // получаем набор общих характеристик и выводим их
          catalog.generateUserProreprty(0, 0);

          break;
        }
        default:{
          catalog.clearFileds();
          break;
        }
      }
      
      // Вызов модального окна.
      admin.openModal($('.b-modal'));
      $('textarea[name=html_content]').ckeditor();   
    },

    /**
     *  Изменяет список пользовательских свойств для выбранной категории в редактировании товара
     */
     generateUserProreprty: function(produtcId,categoryId) {
       
       //alert(produtcId+"=="+categoryId);
       admin.ajaxRequest({
          mguniqueurl:"action/getProdDataWithCat",
          produtcId: produtcId,
          categoryId: categoryId
        },
        function(response) {
          //console.log(response.data.thisUserFields);
          //console.log(response.data.allProperty);
          userProperty.createUserFields($('.userField'), response.data.thisUserFields, response.data.allProperty);
          admin.initToolTip();
          /*$('.userField').find('select').each(function(){
            $(this).after(userProperty.panelMargin($(this))); 
          })*/
        },
        $('.userField')
       );

     },
    /**
     *  Проверка заполненности полей, для каждого поля прописывается свое правило.
     */
    checkRulesForm: function() {
      $('.errorField').css('display','none');
      $('.product-text-inputs input').removeClass('error-input');
      var error = false;

      // наименование не должно иметь специальных символов.
      if(!$('.product-text-inputs input[name=title]').val()){
        $('.product-text-inputs input[name=title]').parent("label").find('.errorField').css('display','block');
        $('.product-text-inputs input[name=title]').addClass('error-input');
        error = true;
      }

      // наименование не должно иметь специальных символов.
      if(!admin.regTest(2, $('.product-text-inputs input[name=url]').val()) || !$('.product-text-inputs input[name=url]').val()){
        $('.product-text-inputs input[name=url]').parent("label").find('.errorField').css('display','block');
        $('.product-text-inputs input[name=url]').addClass('error-input');
        error = true;
      }

      // артикул обязательно надо заполнить.
      if(!$('.product-text-inputs input[name=code]').val()){
        $('.product-text-inputs input[name=code]').parent("label").find('.errorField').css('display','block');
        $('.product-text-inputs input[name=code]').addClass('error-input');
        error = true;
      }

      // Проверка поля для стоимости, является ли текст в него введенный числом.
      if(isNaN(parseFloat($('.product-text-inputs input[name=price]').val()))){
        $('.product-text-inputs input[name=price]').parent("label").find('.errorField').css('display','block');
        $('.product-text-inputs input[name=price]').addClass('error-input');
        error = true;
      }
      
      // Проверка поля для старой стоимости, является ли текст в него введенный числом.
      $('.product-text-inputs input[name=old_price]').each(function(){
        var val = $(this).val();
        if(isNaN(parseFloat(val))&&val!=""){
          $(this).parent("label").find('.errorField').css('display','block');
          $(this).addClass('error-input');
          error = true;
        }
      });

      // Проверка поля количество, является ли текст в него введенный числом.
      $('.product-text-inputs input[name=count]').each(function(){
        var val = $(this).val();
        if(val=='\u221E'||val==''||parseFloat(val)<0){val = "-1"; $(this).val('∞'); }
        if(isNaN(parseFloat(val))){
          $(this).parent("label").find('.errorField').css('display','block');
          $(this).addClass('error-input');
          error = true;
        }
      });
      if(error == true){
        return false;
      }

      return true;
    },


    /**
     * Сохранение изменений в модальном окне продукта.
     * Используется и для сохранения редактированных данных и для сохраниеня нового продукта.
     * id - идентификатор продукта, может отсутсвовать если производится добавление нового товара.
     */
    saveProduct: function(id) {
   
      // Если поля не верно заполнены, то не отправляем запрос на сервер.
      if(!catalog.checkRulesForm()){
        return false;
      }
  
      var recommend = $('.save-button').data('recommend');
      var activity =  $('.save-button').data('activity');
      var newprod =  $('.save-button').data('new');
      //определяем имеются ли варианты товара 
      var variants=catalog.getVariant();      
      

      if(catalog.errorVariantField){    
        admin.indication('error', lang.ERROR_VARIANT); 
        return false;
      }
     
      if($('textarea[name=html_content]').val()==''){
        if(!confirm(lang.ACCEPT_EMPTY_DESC+'?')){
          return false;
        }
      }
     
      if(!variants){     
        
        // Пакет характеристик товара.
        var packedProperty = {
          mguniqueurl:"action/saveProduct",
          id: id,
          title: $('.product-text-inputs input[name=title]').val(),
          url: $('.product-text-inputs input[name=url]').val(),
          code: $('.product-text-inputs input[name=code]').val(),
          price: $('.product-text-inputs input[name=price]').val(),
          old_price: $('.product-text-inputs input[name=old_price]').val(),
          image_url: catalog.createFieldImgUrl(),
          delete_image: catalog.deleteImage,
          count: $('.product-text-inputs input[name=count]').val(),
          cat_id: $('.product-text-inputs select[name=cat_id]').val(),
          description: $('textarea[name=html_content]').val(),          
          meta_title: $('.seo-wrapper input[name=meta_title]').val(),
          meta_keywords: $('.seo-wrapper input[name=meta_keywords]').val(),
          meta_desc: $('.seo-wrapper textarea[name=meta_desc]').val(),
          recommend: recommend,
          activity: activity,
          new:newprod,
          userProperty: userProperty.getUserFields(),
          variants:null
        }
      } else {            
        var packedProperty = {
          mguniqueurl:"action/saveProduct",
          id: id,
          title: $('.product-text-inputs input[name=title]').val(),
          code: $('.variant-table tr').eq(1).find('input[name=code]').val(),
          price: $('.variant-table tr').eq(1).find('input[name=price]').val(),
          old_price: $('.variant-table tr').eq(1).find('input[name=old_price]').val(),
          count: $('.variant-table tr').eq(1).find('input[name=count]').val(),
          url: $('.product-text-inputs input[name=url]').val(),         
          image_url: catalog.createFieldImgUrl(),
          delete_image: catalog.deleteImage,        
          cat_id: $('.product-text-inputs select[name=cat_id]').val(),
          description: $('textarea[name=html_content]').val(),       
          meta_title: $('.seo-wrapper input[name=meta_title]').val(),
          meta_keywords: $('.seo-wrapper input[name=meta_keywords]').val(),
          meta_desc: $('.seo-wrapper textarea[name=meta_desc]').val(),
          recommend: recommend,
          activity: activity,
          new:newprod,
          userProperty: userProperty.getUserFields(),
          variants:variants
        }
         
      }


      // отправка данных на сервер для сохранеиня
      admin.ajaxRequest(packedProperty,
        function(response) {
          admin.indication(response.status, response.msg);
           
          var row = catalog.drawRowProduct(response.data);

          // Вычисляем, по наличию характеристики 'id',
          // какая операция производится с продуктом, добавление или изменение.
          // Если id есть значит надо обновить запись в таблице.
          if(packedProperty.id){
            $('.product-tbody tr[id='+packedProperty.id+']').replaceWith(row);
          }else{
            // Если id небыло значит добавляем новую строку в начало таблицы.
            if($('.product-tbody tr:first').length>0){
              $('.product-tbody tr:first').before(row);
            } else{
              $('.product-tbody ').append(row);
            }
            
            var newCount = $('.widget-table-title .produc-count strong').text()-0+1;
            if(response.status=='success'){
              $('.widget-table-title .produc-count strong').text(newCount);
            }
          }


           $('.no-results').remove();

          // Закрываем окно
          admin.closeModal($('.b-modal'));
          admin.initToolTip();
        }
      );
    },

    cloneProd: function(id) {
     // получаем с сервера все доступные пользовательские параметры
      admin.ajaxRequest({
         mguniqueurl:"action/cloneProduct",
         id:id
       },
       function(response) {
            admin.indication(response.status, response.msg);  
              var row = catalog.drawRowProduct(response.data);

                // Если id небыло значит добавляем новую строку в начало таблицы.
                if($('.product-tbody tr:first').length>0){
                  $('.product-tbody tr:first').before(row);
                } else{
                  $('.product-tbody ').append(row);
                }
                
              var newCount = $('.widget-table-title .produc-count strong').text()-0+1;
              if(response.status=='success'){
                $('.widget-table-title .produc-count strong').text(newCount);
              }

        }
       );
    },
    /**
     * изменяет строки в таблице товаров при редактировании изменении.
     */
    drawRowProduct: function(element) {
     
      // получаем название категории из списка в форме, чтобы внести в строку в таблице
          var cat_name = $('.product-text-inputs select[name=cat_id] option[value='+element.cat_id+']').text();
          cat_name = admin.trim(cat_name, '-');
          // получаем URL имеющейся картинки товара, если она была
          var src=$('tr[id='+element.id+'] .image_url .uploads').attr('src');
          
          if(element.image_url){
            // если идет процесс обновления и картинка новая то обновляем путь к ней
            src=admin.SITE+'/uploads/'+element.image_url;
          }else {
            src=admin.SITE+'/mg-admin/design/images/no-img.png'
          }

          if(element.image_url=='no-img.png') {
            src=admin.SITE+'/mg-admin/design/images/no-img.png'
          }

          // переменная для хранения класса для подсветки активности товара
          var classForTagActivity='activity-product-true';
     
          var recommend = element.recommend==='1'?'active':'';        
          var titleRecommend = element.recommend?lang.PRINT_IN_RECOMEND:lang.PRINT_NOT_IN_RECOMEND;  
          
          var $new = element.new==='1'?'active':'';        
          var titleNew = element.new?lang.PRINT_IN_NEW:lang.PRINT_NOT_IN_NEW;  
          
          var activity = element.activity==='1'?'active':'';        
          var titleActivity = element.activity?lang.ACT_V_CAT:lang.ACT_UNV_CAT;  
          
          // построение  ячейки с ценами
          var tdPrice ='<td  class="price">';
        
           if(element.variants){
             element.variants.forEach(function (variant, index, array) { 
               tdPrice +='<span style="color:#08C;display:inline-block;width:125px;height:17px;overflow:hidden;">'+variant.title_variant+'</span> <input style="width: 45px;" class="variant-price fastsave" type="text" value="'+variant.price+'" data-packet="{variant:1,id:'+variant.id+',field:\'price\'}"/> '+element.currency+'<div class="clear"></div>';
             }
           );                                
          }else{
            tdPrice += '<input style="width: 45px;" type="text" value="'+element.price+'" class="fastsave" data-packet="{variant:0,id:'+element.id+',field:\'price\'}"/> '+element.currency;                               
          }
          tdPrice += '</td>'
         
         
         // построение  ячейки с остатками вариантов товара
          var tdCount ='<td style="text-align:right; border-left: none;" class="count">';          
           if(element.variants){
             element.variants.forEach(function (variant, index, array) {                
               if(variant.count<0){variant.count='∞'}
               tdCount +='<input style="width: 25px;" class="variant-count fastsave" type="text" value="'+variant.count+'" data-packet="{variant:1,id:'+variant.id+',field:\'count\'}"/> '+lang.UNIT+'<div class="clear"></div>';
             }
           );                                
          }else{
            if(element.count<0){element.count='∞'}
            tdCount += '<input style="width: 25px;" type="text" value="'+element.count+'" class="fastsave" data-packet="{variant:0,id:'+element.id+',field:\'count\'}"/> '+lang.UNIT;                               
          }
          tdCount += '</td>'
         
          // html верстка для  записи в таблице раздела
          var row='\
            <tr id="'+element.id+'" data-id="'+element.id+'">\
             <td class="check-align"><input type="checkbox" name="product-check"></td>\
              <td class="id">'+element.id+'</td>\
              <td cat_id="'+element.cat_id+'" class="cat_id">'+cat_name+'</td>\
              <td class="product-picture image_url">\
                <img class="uploads" src="'+src+'"/>\
              </td>\
              <td class="name">'+element.title+'<a class="link-to-site tool-tip-bottom" title="'+lang.PRODUCT_VIEW_SITE+'" href="'+mgBaseDir+'/'+(element.category_url?element.category_url:"catalog")+'/'+element.product_url+'"  target="_blank" ><img src="'+mgBaseDir+'/mg-admin/design/images/icons/link.png" alt="" /></a></td>\
              '+tdPrice+'\
              '+tdCount+'\
              <td class="actions">\
                <ul class="action-list">\
                  <li class="edit-row" id="'+element.id+'"><a href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
                  <li class="tool-tip-bottom new '+$new+'" data-id="'+element.id+'" title="'+titleNew+'" ><a href="javascript:void(0);"></a></li>\
                  <li class="tool-tip-bottom recommend '+recommend+'" data-id="'+element.id+'" title="'+titleRecommend+'" ><a href="javascript:void(0);"></a></li>\
                  <li class="clone-row" id="'+element.id+'"><a href="javascript:void(0);" title="'+lang.CLONE+'"></a></li>\
                  <li class="visible tool-tip-bottom '+activity+'" data-id="'+element.id+'" title="'+titleActivity+'" ><a href="javascript:void(0);"></a></li>\
                  <li class="delete-order" id="'+element.id+'"><a href="javascript:void(0);"  title="'+lang.DELETE+'"></a></li>\
                </ul>\
              </td>\
           </tr>';          
     
        return row;
    },

    /**
     * Получает данные о продукте с сервера и заполняет ими поля в окне.
     */
    editProduct: function(id) {
      admin.ajaxRequest({
        mguniqueurl:"action/getProductData",
        id: id
      },
      catalog.fillFileds(),
      $('.add-product-form-wrapper .add-img-form')      
      );
    },


    /**
     * Удаляет продукт из БД сайта и таблицы в текущем разделе
     */
    deleteProduct: function(id,imgFile,massDel) {
      var confirmed = false;
      if(!massDel){
        if(confirm(lang.DELETE+'?')){
          confirmed = true;
        }
      } else {
        confirmed = true;
      }
      if(confirmed){
        admin.ajaxRequest({
          mguniqueurl:"action/deleteProduct",
          id: id,
          imgFile: imgFile,
          msgImg: true
        },
        function(response) {
          if(!massDel){admin.indication(response.status, response.msg);}
          $('.product-table tr[id='+id+']').remove();
          var newCount = ($('.widget-table-title .produc-count strong').text()-1);
          if(newCount>=0){
            $('.widget-table-title .produc-count strong').text(($('.widget-table-title .produc-count strong').text()-1));
          }
            if($(".product-table tr").length==1){
                var html ='<tr class="no-results">\
                <td colspan="10" align="center">'+lang.PROD_NONE+'</td>\
               </tr>';
              $(".product-table").append(html);
            };
          }
        );
      }

    },

    /**
    * Формирует HTML для добавления и удалени картинки
    */
    drawControlImage:function(url,main,filename) {
      var mainclass="main-img-prod";
      if(main==true){
        mainclass='small-img';
      } 
      
      return '\
        <div class="product-upload-img '+mainclass+'" data-filename="'+filename+'">\
           <a href="javascript:void(0);" class="main-image tool-tip-bottom" title="По умолчанию"><span></span></a>\
            <div class="product-img-prev">\
              <div class="img-loader" style="display:none"></div>\
              <div class="prev-img"><img src="'+url+'" alt="'+filename+'" /></div>\
             <form class="imageform" method="post" noengine="true" enctype="multipart/form-data">\
                <a href="javascript:void(0);" class="add-img-wrapper">\
                <span>Загрузить</span>\
                  <input type="file" name="photoimg" class="add-img tool-tip-top" title="Загрузить фото">\
                </a>\
              </form>\
              <a href="javascript:void(0);" class="cancel-img-upload tool-tip-top" title="'+lang['T_TIP_DEL_IMG_PROD']+'"><span>Удалить</span></a>\
              <div class="clear"></div>\
            </div>\
      </div>';
      
    },

   /**
    * Заполняет поля модального окна данными
    */
    fillFileds:function() {
      return function(response) {
     
        $('.product-text-inputs input').removeClass('error-input');
        $('.product-text-inputs input[name=title]').val(response.data.title);
        $('.product-text-inputs select[name=cat_id]').val(response.data.cat_id);
        $('.product-text-inputs input[name=url]').val(response.data.url);
        
        catalog.cteateTableVariant(response.data.variants);
        //console.log(response.data.variants);
        if(!response.data.variants){          
          $('.product-text-inputs input[name=code]').val(response.data.code);
          $('.product-text-inputs input[name=price]').val(response.data.price);
          $('.product-text-inputs input[name=old_price]').val(response.data.old_price);   
          
          //превращаем минусовое значение в знак бесконечности
          var val = response.data.count;
          if((val=='\u221E'||val==''||parseFloat(val)<0)){val = '∞';}
          $('.product-text-inputs input[name=count]').val(val);          
        }
        
        var rowMain = '';
        var rows = '';
        response.data.images_product.forEach(        
          function (element, index, array) {   
            var src=admin.SITE+'/mg-admin/design/images/no-img.png';
            if(element){
              var src=admin.SITE+'/uploads/'+element;
            }
            
            if(index!=0){
              rows += catalog.drawControlImage(src, true, element);        
            } else {
              rowMain = catalog.drawControlImage(src, false, element);  
            }
           
          }
        );
        
        $('.small-img-wrapper').before(rowMain);
        $('.small-img-wrapper').prepend(rows);
        $('.main-img-prod .main-image').hide();   
        $('textarea[name=html_content]').val(response.data.description);
        $('.seo-wrapper input[name=meta_title]').val(response.data.meta_title);
        $('.seo-wrapper input[name=meta_keywords]').val(response.data.meta_keywords);
        $('.seo-wrapper textarea[name=meta_desc]').val(response.data.meta_desc);
        $('.save-button').attr('id',response.data.id);    
        $('.save-button').data('recommend',response.data.recommend);
        $('.save-button').data('activity',response.data.activity);
        $('.save-button').data('new',response.data.new);        
        $('.cancel-img-upload').attr('id',response.data.id);
        $('.userField').html('');

        try{
          $('.symbol-count').text($('.seo-wrapper textarea[name=meta_desc]').val().length);
        }catch(e){
          $('.symbol-count').text('0');
        }

        catalog.generateUserProreprty(response.data.id, response.data.cat_id);


      }
    },


   /**
    * Чистит все поля модального окна
    */
    clearFileds:function() {


      $('.product-text-inputs input[name=title]').val('');
      $('.product-text-inputs input[name=url]').val('');
      $('.product-text-inputs input[name=code]').val('');
      $('.product-text-inputs input[name=price]').val('');
      $('.product-text-inputs input[name=old_price]').val('');   
      $('.product-text-inputs input[name=count]').val('');
      $('.product-text-inputs select[name=cat_id]').val('0');
      $('.prod-gallery').html('<div class="prod-gallery"><div class="small-img-wrapper"></div></div>');
      $('textarea[name=html_content]').val(''); 
      $('.seo-wrapper input[name=meta_title]').val('');
      $('.seo-wrapper input[name=meta_keywords]').val('');
      $('.seo-wrapper textarea[name=meta_desc]').val('');
      $('.product-text-inputs .variant-table').html('');
      $('.userField').html('');
      $('.symbol-count').text('0');
      $('.save-button').attr('id','');
      $('.save-button').data('recommend','0');
      $('.save-button').data('activity','1');
      $('.save-button').data('new','0');
      catalog.cteateTableVariant(null);
      catalog.deleteImage ='';

      // получаем с сервера все доступные пользовательские параметры
      admin.ajaxRequest({
         mguniqueurl:"action/getUserProperty"
       },
       function(response) {
          // выводим поля для редактирования пользовательских характеристик
          userProperty.createUserFields(null,response.data.allProperty);
        },
       $('.error-input').removeClass('error-input')        
       );

      // Стираем все ошибки предыдущего окна если они были.
      $('.errorField').css('display','none');
      $('.error-input').removeClass('error-input');


    },


   /**
    * Добавляет изображение продукта
    */
    addImageToProduct:function(img_container) {

      img_container.find('.img-loader').show();

      // отправка картинки на сервер
      img_container.find('.imageform').ajaxForm({
        type:"POST",
        url: "ajax",
        data: {
          mguniqueurl:"action/addImage"
        },
        cache: false,
        dataType: 'json',
        success: function(response){
          admin.indication(response.status, response.msg);
          if(response.status != 'error'){
            var src=admin.SITE+'/uploads/'+response.data.img;
            img_container.find('.prev-img').html('<img src="'+src+'" alt="'+response.data.img+'" />');
          
          }else{
            var src=admin.SITE+'/mg-admin/design/images/no-img.png';
            img_container.find('.prev-img').html('<img src="'+src+'" alt="'+response.data.img+'" />');
          }
         img_container.find('.img-loader').hide();
        }
      }).submit();
    },
    
    /**
     *  собирает названия файлов всех картинок чтобы сохранить их в БД в поле image_url
     */  
    createFieldImgUrl: function() {         
       var image_url = "";   
       $('.prod-gallery .prev-img img').each(function(){    
         if($(this).attr('alt') && $(this).attr('alt')!='undefined'){
           image_url+=$(this).attr('alt')+'|';
         }
       });   

       if(image_url){
         image_url = image_url.slice(0,-1);
       }
       
       return image_url;
    }, 

   /**
     * Помещает  выбранную основной картинку в начало ленты  
     * removemain = true - была удалена главная и требуется поднять из лены первую на место главной
     */
    upMainImg: function(obj, removemain) {
      var oldMain = ''; 
      if(!removemain){
        // для главной картинки меняем классы сохраняем в буфер и удаляем      
        oldMain = $('.main-img-prod'); 
        oldMain.find('.main-image').show();
        oldMain.removeClass('main-img-prod').addClass('small-img');         
      }   
      $('.main-img-prod').remove();

      
      // выбранную картинку удаляем из ленты, добавляем классы как для главной и помещаем на место главной
      var bufer = obj;  
      obj.remove();
      bufer.removeClass('small-img').addClass('main-img-prod');
      bufer.find('.main-image').hide();
      
      $('.small-img-wrapper').before(bufer);
      $('.small-img-wrapper').prepend(oldMain);
   
    },

   /**
    * Удаляет изображение продукта
    */
    delImageProduct: function(id,img_container) {      
      var imgFile = img_container.find('.prev-img img').attr('src');            
      
      if(confirm(lang.DELETE_IMAGE+'?')){      
        catalog.deleteImage += "|"+imgFile;
        // удаляем текущий блок управления картинкой        
        if($('.prod-gallery .prev-img img').length>1){ 
          if(img_container.hasClass('main-img-prod')){
              catalog.upMainImg($('.small-img').eq(0), true);
          }else{
            img_container.remove();
          }
        } else{
          // если блок единственный, то просто заменяем в нем картнку на заглушку
          var src=admin.SITE+'/mg-admin/design/images/no-img.png';
          img_container.find('.prev-img img').attr('src',src).attr('alt',''); 
          img_container.data('filename','');           
        }            
      $('#tiptip_holder').hide();
      }
    },

   /**
    * Поиск товаров
    */
    getSearch: function(keyword) {
      admin.ajaxRequest({
          mguniqueurl:"action/searchProduct",
          keyword:keyword
      },
      function(response) {
        admin.indication(response.status, response.msg);     
        $('.product-tbody tr').remove();
        response.data.forEach(
          function (element, index, array) {
             var row = catalog.drawRowProduct(element);
             $('.product-tbody').append(row);
          });
          // Если в результате поиска ничего не найдено
          if(response.data.length==0){    
            var row = "<tr><td class='no-results' colspan='"+$('.product-table th').length+"'>"+lang.SEARCH_PROD_NONE+"</td></tr>"
            $('.product-tbody').append(row);
          }
          $('.mg-pager').hide();
        }
      );
    },


    //  Получает данные из формы фильтров и перезагружает страницу
    getProductByFilter: function(){
       var request = $("form[name=filter]").formSerialize();
       admin.show("catalog.php","adminpage",request+'&applyFilter=1',catalog.callbackProduct);    
       return false;
    },
    
    // Устанавливает статус продукта - рекомендуемый
     recomendProduct:function(id, val) {
      admin.ajaxRequest({
        mguniqueurl:"action/recomendProduct",
        id: id,
        recommend: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
    // Устанавливает статус - видимый
     visibleProduct:function(id, val) {
      admin.ajaxRequest({
        mguniqueurl:"action/visibleProduct",
        id: id,
        activity: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
    // вывод в новинках
    newProduct:function(id, val) {
      admin.ajaxRequest({
        mguniqueurl:"action/newProduct",
        id: id,
        new: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
     // Добавляет строку в таблицу вариантов
    cteateTableVariant:function(variants) {      
      // строим первую строку заголовков        
      $('.product-text-inputs .variant-table').html('');
      if(variants){
        var position ='\
        <tr>\
          <th class="hide-content">'+lang.NAME_VARIANT+'</th>\
          <th>'+lang.CODE_PRODUCT+'</th>\
          <th>'+lang.PRICE_PRODUCT+'/'+admin.CURRENCY+'</th>\
          <th>'+lang.OLD_PRICE_PRODUCT+'</th>\
          <th>'+lang.REMAIN+'</th>\
          <th class="hide-content"></th>\
        </tr>\ ';
        $('.variant-table').append(position);  
        // заполняем вариантами продукта
        variants.forEach(function(variant, index, array) {     
          var src = admin.SITE+"/mg-admin/design/images/no-img.png";
          if(variant.image){
            src = admin.SITE+'/uploads/'+variant.image;
          }
          
          if(variant.count<0){variant.count='∞'};
          var position ='\
          <tr data-id="'+variant.id+'"  class="variant-row">\
             <td class="hide-content">\
              <label for="title_variant"><input style="width: 120px;" type="text" name="title_variant" value="'+variant.title_variant+'" class="product-name-input tool-tip-right" title="'+lang.NAME_PRODUCT+'" ><div class="errorField">'+lang.NAME_PRODUCT+'</div></label>\
            </td>\
            <td>\
              <label for="code"><input style="width: 60px;" type="text" name="code" value="'+variant.code+'" class="product-name-input tool-tip-right" title="'+lang.T_TIP_CODE_PROD+'" ><div class="errorField">'+lang.ERROR_EMPTY+'</div></label>\
            </td>\
            <td>\
              <label for="price"><input style="width:60px;" type="text" name="price" value="'+variant.price+'" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_PRICE_PROD+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
            </td>\
            <td>\
              <label for="old_price"><input style="width:60px;" type="text" name="old_price" value="'+variant.old_price+'" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_OLD_PRICE+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
            </td>\
            <td>\
              <label for="count"><input style="width:60px;" type="text" name="count" value="'+variant.count+'" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_COUNT_PROD+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
            </td>\
            <td class="hide-content">\
            <div class="variant-dnd"></div>\
            <div class="img-this-variant" style="display:none;">\
            <img src="'+src+'" style="width:50px; min-height:100%" data-filename="'+variant.image+'">\
            </div>\
              <form method="post" noengine="true" enctype="multipart/form-data" class="img-button">\
              <span class="add-img-clone"></span>\
                <input type="file" name="photoimg" class="add-img-var img-variant">\
              </form>\
            <a href="javascript:void(0);" class="del-variant">Удалить</a>\
            </td>\
          </tr>\ ';
          $('.variant-table').append(position);
          $('.variant-table-wrapper').css('width','590px');
          $('.variant-table tr td').css('padding','5px 10px 5px 0');
        });      
        $('.variant-table').data('have-variant','1');
      }else{
        
        var position ='\
        <tr>\
          <th style="display:none" class="hide-content">'+lang.NAME_VARIANT+'</th>\
          <th>'+lang.CODE_PRODUCT+'</th>\
          <th>'+lang.PRICE_PRODUCT+'/'+admin.CURRENCY+'</th>\
          <th>'+lang.OLD_PRICE_PRODUCT+'</th>\
          <th>'+lang.REMAIN+'</th>\
         <th style="display:none" class="hide-content"></th>\
        </tr>\ ';
        $('.variant-table').append(position);  
          var position ='\
          <tr class="variant-row">\
            <td style="display:none" class="hide-content">\
              <label for="title_variant"><input style="width: 120px;" type="text" name="title_variant" value="" class="product-name-input tool-tip-right" title="'+lang.NAME_PRODUCT+'" ><div class="errorField">'+lang.ERROR_EMPTY+'</div></label>\
            </td>\
            <td>\
              <label for="code"><input style="width: 60px;" type="text" name="code" value="" class="product-name-input tool-tip-right" title="'+lang.T_TIP_CODE_PROD+'" ><div class="errorField">'+lang.ERROR_EMPTY+'</div></label>\
            </td>\
            <td>\
              <label for="price"><input style="width:60px;" type="text" name="price" value="" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_PRICE_PROD+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
            </td>\
            <td>\
              <label for="old_price"><input style="width:60px;" type="text" name="old_price" value="" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_OLD_PRICE+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
            </td>\
            <td>\
              <label for="count"><input style="width:60px;" type="text" name="count" value="∞" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_COUNT_PROD+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
            </td>\
            <td style="display:none" class="hide-content">\
            <div class="variant-dnd"></div>\
            <div class="img-this-variant" style="display:none;">\
            <img src="'+admin.SITE+'/mg-admin/design/images/no-img.png" data-filename="" style="width:50px; min-height:100%">\
            </div>\
              <form method="post" noengine="true" enctype="multipart/form-data" class="img-button">\
                <span class="add-img-clone"></span>\
                <input type="file" name="photoimg" class="add-img-var img-variant">\
              </form>\
            <a href="javascript:void(0);" class="del-variant">Удалить</a>\
            </td>\
          </tr>\ ';        
          $('.variant-table').append(position);
          $('.variant-table-wrapper').css('width','383px');
          $('.variant-table tr td').css('padding','5px 18px 5px 0');
          $('.variant-table').data('have-variant','0');
          $('.variant-table').sortable({        
            opacity: 0.6,
            axis: 'y',
            handle: '.variant-dnd',   
            items: "tr+tr",           
            }
          );


      }
    },
    
    
    // Добавляет строку в таблицу вариантов
    addVariant:function(table) {
      if($('.variant-table').data('have-variant')=="0"){
        $('.variant-table .hide-content').show();
        $('.variant-table').data('have-variant','1');
      }
      var position ='\
      <tr class="variant-row">\
         <td class="hide-content">\
          <label for="title_variant"><input style="width: 120px;"  type="text" name="title_variant" class="product-name-input tool-tip-right" title="'+lang.NAME_PRODUCT+'" ><div class="errorField">'+lang.ERROR_EMPTY+'</div></label>\
        </td>\
        <td>\
          <label for="code"><input style="width: 60px;"  type="text" name="code" class="product-name-input tool-tip-right" title="'+lang.T_TIP_CODE_PROD+'" ><div class="errorField">'+lang.ERROR_EMPTY+'</div></label>\
        </td>\
        <td>\
          <label for="price"><input style="width:60px;" type="text" name="price" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_PRICE_PROD+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
        </td>\
        <td>\
          <label for="old_price"><input style="width:60px;" type="text" name="old_price" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_OLD_PRICE+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div> </label>\
        </td>\
        <td>\
          <label for="count"><input style="width:60px;" type="text" name="count" value="∞" class="product-name-input qty tool-tip-right" title="'+lang.T_TIP_COUNT_PROD+'" ><div class="errorField">'+lang.ERROR_NUMERIC+'</div></label>\
        </td>\
        <td class="hide-content">\
          <div class="variant-dnd"></div>\
          <div class="img-this-variant" style="display:none">\
          <img src="'+admin.SITE+'/mg-admin/design/images/no-img.png"  data-filename=""  style="width:50px; min-height:100%">\
          </div>\
          <form method="post" noengine="true" enctype="multipart/form-data" class="img-button">\
            <span class="add-img-clone"></span>\
            <input type="file" name="photoimg" class="add-img-var img-variant">\
           </form>\
          <a href="javascript:void(0);" class="del-variant">Удалить</a>\
        </td>\
      </tr>\ ';
      table.append(position);
      $('.variant-table-wrapper').css('width','590px');
      $('.variant-table tr td').css('padding','5px 10px 5px 0');
      admin.initToolTip();
    
    },
    
    
    // возвращает пакет  вариантов собранный из таблицы вариантов
    getVariant: function(){
      catalog.errorVariantField = false;
       console.log($('.variant-table').data('have-variant'));
      if($('.variant-table').data('have-variant')=="1"){
       
        var result = [];        
        $('.variant-table .variant-row').each(function(){
           
          //собираем  все значения полей варианта для сохранения в БД
          
          var id =$(this).data('id');
          var obj = '{';
          $(this).find('input').removeClass('error-input');        
          $(this).find('input').each(function() {   
            
            if($(this).attr('name')!='photoimg'){
              
              var val = $(this).val();
              if((val=='\u221E'||val==''||parseFloat(val)<0)&&$(this).attr('name')=="count"){val = "-1";}
              
              if(val==""&&$(this).attr('name')!='old_price'){               
                $(this).addClass('error-input');
                catalog.errorVariantField = true;
              }
              obj += '"' + $(this).attr('name') + '":"' + val + '",';
            }
          });
          obj += '"activity":"1",';
          obj += '"id":"'+id+'",';
       
          var filename = $(this).find('img[filename]').attr('filename');
          if(!filename){filename = $(this).find('img').data('filename')}
          obj += '"image":"'+filename+'",';         
          
          obj += '}';          
          //преобразуем полученные данные в JS объект для передачи на сервер
          result.push(eval("(" + obj + ")"));
        });
        
        return result;
      }
      return null;
    },
        
    // сохраняет параметры товара прямо со страницы каталога в админке
    fastSave:function(data, val, input){
      var obj = eval("(" + data + ")");
      // Проверка поля для стоимости, является ли текст в него введенный числом.
     
      // знак бесконечности 
      if((val=='\u221E'||val==''||parseFloat(val)<0)&&obj.field=="count"){val = "-1"; input.val('∞'); }
 

      if(isNaN(parseFloat(val))){ 
        admin.indication('error', lang.ENTER_NUM);   
        input.addClass('error-input');        
        return false;   
      } else {
        input.removeClass('error-input');
      }
      
      // получаем с сервера все доступные пользовательские параметры
      admin.ajaxRequest({
        mguniqueurl:"action/fastSaveProduct",
        variant:obj.variant,
        id:obj.id,
        field:obj.field,
        value:val,
        product_id: input.parents('tr').attr('id')
      },
      function(response) {
        admin.indication(response.status, response.msg);       
      });
      
    },
    
    
    importFromCsv:function(){
      admin.ajaxRequest({
        mguniqueurl:"action/importFromCsv",  
      },
      function(response) {
        admin.indication(response.status, response.msg);       
      });
    },
    
    /**
     * Загружает CSV файл на сервер для последующего импорта
     */
    uploadCsvToImport:function() {      
      // отправка файла CSV на сервер
      $('.message-importing').text('Идет передача файла на сервер. Подождите, пожалуйста...'); 
      $('.upload-csv-form').ajaxForm({
        type:"POST",
        url: "ajax",
        data: {
          mguniqueurl:"action/uploadCsvToImport"
        },
        cache: false,
        dataType: 'json',
        success: function(response){
          admin.indication(response.status, response.msg);      
          if(response.status=='success'){
            $('.block-upload-сsv').hide();
            $('.block-importer').show();
            $('.message-importing').text('Файл готов к импорту товаров в каталог'); 
          }else{
            $('.message-importing').text('');
            $('.import-container input[name="upload"]').val('');
          }
        }
      }).submit();
    },
    
    /**
     * Контролирует процесс импорта, выводит индикатор в процентах обработки каталога.
     */
    startImport:function(rowId, percent) {
     var delCatalog=null;
      if(!rowId){        
        if(!$('.loading-line').length) {     
          $('.process').append('<div class="loading-line"></div>');   
        }
        rowId = 0;
        delCatalog = $('input[name=no-merge]').val();
      }
      if(!percent){       
        percent = 0;
      }
      
           
      if(!catalog.STOP_IMPORT){
        $('.message-importing').html('Идет процесс импорта товаров. Загружено:'+percent+'%<div class="progress-bar"><div class="progress-bar-inner" style="width:'+percent+'%;"></div></div>');
      }else{
        $('.loading-line').remove();
      }     
      
      // отправка файла CSV на сервер      
      admin.ajaxRequest({
        mguniqueurl:"action/startImport",
        rowId:rowId,
        delCatalog:delCatalog
      },
      function(response){
        if(response.status=='error'){ 
          admin.indication(response.status, response.msg);  
        }
       
        if(response.data.percent<100){        
          if(response.data.status=='canseled'){
            $('.message-importing').html('Процесс импорта остановлен пользователем! Загружено: '+response.data.rowId+' товаров  [<a href="javascript:void(0);" class="repeat-upload-csv">Загрузить другой файл</a>]' );
            $('.block-importer').hide(); 
            $('.loading-line').remove();
          }else{            
            catalog.startImport(response.data.rowId,response.data.percent);
          }
        } else{
           $('.message-importing').html('Импорт товаров успешно завершен! <a class="refresh-page custom-btn" href="'+mgBaseDir+'/mg-admin/"><span>Обновите страницу</span></a>');
           $('.block-importer').hide();
           $('.loading-line').remove();
        } 
    
      });
    },
    
    /**
     * Останавливает процесс импорта в каталог товаров
     */
    canselImport:function() { 
      $('.message-importing').text('Происходит остановка импорта!');
      catalog.STOP_IMPORT=true;    
      admin.ajaxRequest({
        mguniqueurl:"action/canselImport",    
      },
      function(response){
        admin.indication(response.status, response.msg);          
      });
    },
    
    /**
     *Пакет выполняемых действий после загрузки раздела товаров
     */
    callbackProduct:function() {   
    admin.sliderPrice();
    admin.AJAXCALLBACK = [      
      {callback:'admin.sortable', param:['.product-table tbody','product']},       
    ]; 
    },
    
  }
})();

// инициализациямодуля при подключении
catalog.init();