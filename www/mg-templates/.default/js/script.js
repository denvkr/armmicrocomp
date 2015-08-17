$(document).ready(function() {

  //Маска телефона для России
  $("input[name=phone]").mask("+7 (999) 999-99-99");

  //Маска телефона для Украины
  //$("input[name=phone]").mask("+38 (999) 999-99-99");

  var slider_width = $('.menu-block').width() + 2;
  var deviceWidth = $(window).width();

  /*Mobile menu*/
  $(".mobile-categories h2").on("click", function() {
    $(this).toggleClass("open-menu");
    $(this).next(".mobile-cat-list").slideToggle("fast");
  });

  $(".mobile-cat-list li .slider_btn, .mobile-top-panel .top-menu-list li .slider_btn").on("click", function() {
    $(this).toggleClass("opened");
    $(this).parent().find(".sub_menu:first").slideToggle("fast");
  });

  $(".show-menu-toggle").on("click", function() {
    $(this).parent().find(".mobile-top-menu").slideToggle("fast");
    ;
  });

  /*Fix mobile top menu position if login admin*/
  if ($("body").hasClass("admin-on-site")) {
    $("body").find(".mobile-top-panel").addClass("position-fix");
  }

  $(function() { // Когда страница загрузится
    $('.top-menu-list li a').each(function() { // получаем все нужные нам ссылки
      var location = window.location.href; // получаем адрес страницы
      var link = this.href;                // получаем адрес ссылки
      if (location == link) {               // при совпадении адреса ссылки и адреса окна
        $(this).addClass('active-menu-item');  //добавляем класс
      }
    });
  });
  
  // Вкладки в личном кабинете  
  var tabCookieName = "mytabs";
  $(".personal-tabs").tabs({
    active: ($.cookie(tabCookieName) || 0),
    activate: function(event, ui) {
      var newIndex = ui.newTab.parent().children().index(ui.newTab);
      $.cookie(tabCookieName, newIndex, {expires: 1});
    }
  });

  //Слайдер новинок на главной странице
  $('.m-p-products-slider-start').bxSlider({
    minSlides: 4,
    maxSlides: 4,
    slideWidth: 222,
    slideMargin: 15,
    moveSlides: 1,
    pager: false,
    auto: false,
    pause: 6000,
    useCSS: false
  });

  $('.m-p-slider-container').bxSlider({
    minSlides: 1,
    maxSlides: 1,
    pager: true,
    auto: true,
    pause: 6000,
    useCSS: false
  });

  //Слайдер картинок в карточке товаров
  $('.main-product-slide').bxSlider({
    pagerCustom: '.slides-inner',
    controls: false,
    mode: 'fade',
    useCSS: false
  });
  $('.slides-inner').bxSlider({
    minSlides: 3,
    maxSlides: 3,
    slideWidth: 75,
    pager: false,
    slideMargin: 10,
    useCSS: false
  });

  $('.slides-inner a').click(function() {
    $(this).each(function() {
      $('.slides-inner a').removeClass('active-item');
      $(this).addClass('active-item');
    });
  });

  //Инициализация fancybox
  $(".close-order, a.fancy-modal").fancybox({
    'overlayShow': false,
    tpl: {
      next: '<a title="Вперед" class="fancybox-nav fancybox-next" href="javascript:;"><span></span></a>',
      prev: '<a title="Назад" class="fancybox-nav fancybox-prev" href="javascript:;"><span></span></a>'
    }
  });

  //Показать маленькую корзину
  $('.desktop-cart .cart').hover(function(event) {
      event.stopPropagation();
      if ($('.small-cart-table tbody tr').length > 0) {
        $('.small-cart').show();
        $('.cart').css({border: '1px solid #CCCCCC'});
        $('.cart-inner').css({background: '#fff'});
      };
    },
    function() {
      $('.cart').css({border: '1px solid transparent'});
      $('.cart-inner').css({background: 'none'});
      $('.small-cart').hide();
    }
  );

  // Обработка ввода поисковой фразы в поле поиска
  $('body').on('keyup', 'input[name=search]', function() {

    var text = $(this).val();
    if (text.length >= 2) {
      $.ajax({
        type: "POST",
        url: "ajax",
        data: {
          action: "getSearchData", // название действия в пользовательском класса Ajaxuser
          actionerClass: "Ajaxuser", // ajaxuser.php - в папке шаблона
          search: text
        },
        dataType: "json",
        cache: false,
        success: function(data) {
          var html = '<ul class="fast-result-list">';
          var currency = data.currency;
          function buildElements(element, index, array) {
            html += '<li><a href="' + mgBaseDir + '/' + (element.category_url ? element.category_url : 'catalog') + '/' + element.product_url + '"> <div class="fast-result-img"><img src="' + mgBaseDir + '/uploads/thumbs/30_' + (element.image_url ? element.image_url : 'no-img.jpg') + '" alt="' + element.title + '"/></div>' + element.title + '<span>' + element.price + ' ' + currency + '</span></a></li>';
          }
          ;

          if ('success' == data.status && data.item.items.catalogItems.length > 0) {
            data.item.items.catalogItems.forEach(buildElements);
            html += '</ul>';
            $('.fastResult').html(html);
            $('.fastResult').show();
          } else {
            $('.fastResult').hide();
          }
        }
      });
    } else {
      $('.fastResult').hide();
    }
  });


  // Заполнение корзины аяксом
  $('body').on('click', '.addToCart', function() {
 
    if ($(this).parents('.property-form').length) {
      var request = $(this).parents('.property-form').formSerialize();
    } else {
      var request = 'inCartProductId=' + $(this).data('item-id') + "&amount_input=1";
    }

    $.ajax({
      type: "POST",
      url: mgBaseDir + "/cart",
      data: "updateCart=1&" + request,
      dataType: "json",
      cache: false,
      success: function(response) {
        if ('success' == response.status) {
          dataSmalCart = '';
          response.data.dataCart.forEach(printSmalCartData);
          $('.small-cart-table').html(dataSmalCart);
          $('.total .total-sum span').text(response.data.cart_price_wc);
          $('.pricesht').text(response.data.cart_price);
          $('.countsht').text(response.data.cart_count);
        }
      }
    });

    return false;
  });

  $("body").on("click", ".layer", function() {
    $(".layer").fadeOut("fast");
    $(".fake-cart").fadeOut("fast");
  });

  // Удаление вещи из корзины аяксом
  $('body').on('click', '.deleteItemFromCart', function() {

    var $this = $(this);
    var itemId = $this.data('delete-item-id');
    var property = $this.data('property');
    var $vari = $this.data('variant');
    $.ajax({
      type: "POST",
      url: mgBaseDir+"/cart",
      data: {
        action: "cart", // название действия в пользовательском класса Ajaxuser
        delFromCart: 1,
        itemId: itemId,
        property: property,
        variantId: $vari
      },
      dataType: "json",
      cache: false,
      success: function(response) {

        if ('success' == response.status) {
          var table = $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"]').parents('table');
          $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"]').parents('tr').remove();
          var i = 1;
          table.find('.index').each(function() {
            $(this).text(i++);
          });

          $('.total .total-sum span').text(response.data.cart_price_wc);
          response.data.cart_price = response.data.cart_price ? response.data.cart_price : 0;
          response.data.cart_count = response.data.cart_count ? response.data.cart_count : 0;
          $('.pricesht').text(response.data.cart_price);
          $('.countsht').text(response.data.cart_count);     
          $('.cart-table .total-sum-cell strong').text(response.data.cart_price_wc);          

          if ($('.small-cart-table tbody tr').length == 0) {
            $('.small-cart').hide();
            $('.desktop-cart .cart').css({border: '1px solid transparent'});
            $('.desktop-cart .cart-inner').css({background: 'none'});
            $('.empty-cart-block').show();
            $('.product-cart').hide();
          }
          ;

        }
      }
    });

    return false;
  });

  // строит содержимое маленькой корзины в  выпадащем блоке
  function printSmalCartData(element, index, array) {

    dataSmalCart += '<tr>\
				<td class="small-cart-img">\
					<a href="' + mgBaseDir + '/' + (element.category_url ? element.category_url : 'catalog') + '/'
            + element.product_url + '"><img src="' + mgBaseDir + '/uploads/thumbs/30_'
            + (element.image_url ? element.image_url : 'no-img.jpg') + '" alt="'
            + element.title + '" alt="" /></a>\
				</td>\
				<td class="small-cart-name">\
					<ul class="small-cart-list">\
						<li><a href="' + mgBaseDir + '/' + (element.category_url ? element.category_url : 'catalog') + '/'
            + element.product_url + '">' + element.title + '</a><span class="property">'
            + element.property_html + '</span></li>\
						<li class="qty">x' + element.countInCart + ' <span>'
            + element.priceInCart + '</span></li>\
					</ul>\
				</td>\
				<td class="small-cart-remove"><a href="#" class="deleteItemFromCart" title="Удалить" data-delete-item-id="' + element.id
            + '" data-property="' + element.property
            + '">&#215;</a></td>\
			</tr>';
  }

  $('.header .cart-list').click(function() {
    window.location = mgBaseDir + '/cart';
  });

//  $('.products-wrapper').masonry({
//    itemSelector: '.product-wrapper',
//    singleMode: false,
//    isResizable: true,
//    isAnimated: true,
//    animationOptions: {
//      queue: true,
//      duration: 500
//    }
//
//  });
});