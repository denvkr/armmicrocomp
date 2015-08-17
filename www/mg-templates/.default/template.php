<?php
/**
 * Файл template.php является каркасом шаблона, содержит основную верстку шаблона.
 * 
 * В этом файле доступны следующие данные:
 * <code>
*   $data['cartCount'] => Количество товаров в корзине.
*   $data['cartPrice'] => Общая стоимость товаров в корзине.
*   $data['currency'] => Валюта магазина.
*   $data['cartData'] => Содержание корзины.
*   $data['categoryList'] => Список категорий.
*   $data['content']  => Содержание страницы.
*   $data['menu'] => Ггоризонтальное меню.
*   $data['thisUser'] => Информация об авторизованном пользователе.
 * </code>
 *   
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>     
 *    <php viewData($data['cartData']); ?>  
 *   </code>
 * 
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>     
 *    <php echo $data['cartData']; ?>  
 *   </code>
 * 
 *   @author Авдеев Марк <mark-avdeev@mail.ru>
 *   @package moguta.cms
 *   @subpackage Views
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width">
<?php mgMeta(); ?>
<link href="<?php echo PATH_SITE_TEMPLATE ?>/css/jquery.fancybox.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="<?php echo PATH_SITE_TEMPLATE ?>/js/jquery.fancybox.pack.js"></script>
<script type="text/javascript" src="<?php echo PATH_SITE_TEMPLATE ?>/js/jquery-ui-1.10.1.custom.min.js"></script>
<script type="text/javascript" src="<?php echo PATH_SITE_TEMPLATE ?>/js/jquery.bxslider.min.js"></script>
<script type="text/javascript" src="<?php echo SITE ?>/mg-core/script/jquery.maskedinput.min.js"></script>
<script src="<?php echo PATH_SITE_TEMPLATE ?>/js/jquery.cookie.js"></script>
<script type="text/javascript" src="<?php echo PATH_SITE_TEMPLATE ?>/js/script.js"></script>
<!--[if IE 9]>
  <link href="<?php echo PATH_SITE_TEMPLATE ?>/css/ie9.css" rel="stylesheet" type="text/css" />
<![endif]-->
<!--[if IE 8]>
  <link href="<?php echo PATH_SITE_TEMPLATE ?>/css/ie8.css" rel="stylesheet" type="text/css" />
<![endif]-->
<!--[if IE 7]>
  <link href="<?php echo PATH_SITE_TEMPLATE ?>/css/ie7.css" rel="stylesheet" type="text/css" />
<![endif]-->

</head>
<body> 

  <div class="wrapper">
	<div class="container">
		<div class="header">
			<div class="desktop-cart">
				<div class="cart">
					<div class="cart-inner">
						<ul class="cart-list">
							<li><h3 class="cart-title">Корзина товаров:</h3></li>
							<li class="cart-qty"><span class="countsht"><?php echo $data['cartCount']?$data['cartCount']:0 ?></span> шт. - <span class="pricesht"><?php echo $data['cartPrice']?$data['cartPrice']:0  ?></span>  <?php echo $data['currency']; ?></li>
						</ul>
						<a href="<?php echo SITE ?>/cart" class="small-cart-icon"></a>
					</div>
					<div class="small-cart">
						<h2>Товары в корзине</h2>
						<table class="small-cart-table">
							<?php if(!empty($data['cartData']['dataCart'])){            
							foreach($data['cartData']['dataCart'] as $item):?>
							<tr>
							  <td class="small-cart-img">
								<a href="<?php echo SITE."/".($item['category_url']?$item['category_url']:'catalog')."/".$item['product_url'] ?>">
								   <img src="<?php echo SITE."/uploads/thumbs/30_".($item['image_url']?$item['image_url']:'no-img.jpg') ?>" alt="<?php echo $item['title'] ?>" />
								</a>
							  </td>
							  <td class="small-cart-name">
								 <ul class="small-cart-list">
								   <li><a href="<?php echo SITE."/".($item['category_url']?$item['category_url']:'catalog')."/".$item['product_url'] ?>"><?php echo $item['title'] ?></a>
									 <span class="property"><?php echo $item['property_html'] ?> </span>  
									 </li>
								   <li class="qty">x<?php echo $item['countInCart'] ?> <span><?php echo $item['priceInCart'] ?></li>
								 </ul>
							   </td>
							  <td class="small-cart-remove"><a href="#" class="deleteItemFromCart" title="Удалить" data-delete-item-id='<?php echo $item['id'] ?>' data-property="<?php echo $item['property'] ?>"  data-variant="<?php echo $item['variantId'] ?>">&#215;</a></td>
							</tr>
							<?php endforeach;
							} else { ?>
							 
							<?php }?>
						</table>
						<ul class="total">
							<li class="total-sum">Общая сумма: <span><?php echo $data['cartData']['cart_price_wc'] ?></span></li>
							<li class="checkount-buttons"><a href="<?php echo SITE ?>/cart">Корзина</a>&nbsp;&nbsp;|<a href="<?php echo SITE ?>/order">Оформить</a></li>
						</ul>
					</div>
				</div>
			</div>
			
			<div class="top-contacts">				
				  <?php if($thisUser = $data['thisUser']): ?>
				  <p class="auth">Добро пожаловать <a href="<?php echo SITE?>/personal"><?php echo empty($thisUser->name)?$thisUser->email:$thisUser->name ?></a>, <a href="<?php echo SITE?>/enter?logout=1">выход</a></p>
				  <?php else: ?>
				  <p class="auth"><a href="<?php echo SITE?>/enter">Войти</a> или <a href="<?php echo SITE?>/registration">зарегистрироваться</a></p>
				  <?php endif; ?>

			</div>
			<div class="logo-block">
			<img style="display: inline;float: none;position: static;margin: 0,0,0,0;" src="<?php echo SITE.'/uploads/armmicrochip_logo.png' ?>" alt="<?php echo $item['title'] ?>" />
			<!--<img src="/public_html/uploads/armmicrochip_logo.png" style="display: block;float: none;position: static;" height="50" width="100">-->	
			</div>
				<div class="search-block">
					<form method="get" action="<?php echo SITE?>/catalog" class="search-form">
						<input type="text" autocomplete="off" name="search" class="search-field" value="Ключевое слово" onfocus="if (this.value == 'Ключевое слово') {this.value = '';}" onblur="if (this.value == '') {this.value = 'Ключевое слово';}">
						<input type="submit" class="search-button" value="">
					</form>
					<div class="fastResult">

					</div>
				</div>
			<div class="clear">&nbsp;</div>
			<div class="top-menu">     
				<?php mgMenuFull();?>
			</div><!--end top-menu-->
		</div><!--end header-->
		<!--Mobile top panel-->
		<div class="mobile-top-panel">
			<a href="javascript:void(0);" class="show-menu-toggle"></a>
			<div class="mobile-top-menu">
				<?php mgMenuFull();?>
			</div>
			<div class="mobile-cart">
				<a href="<?php echo SITE ?>/cart">
					<div class="cart small-cart-icon">
						<div class="cart-inner">
							<ul class="cart-list">
								<li class="cart-qty"><span class="countsht"><?php echo $data['cartCount']?$data['cartCount']:0 ?></span> шт.</li>
							</ul>
						</div>
					</div>
				</a>
			</div>
		</div>
		<!--Mobile top panel-->	
		
		<!--Mobile category menu-->
		<div class="mobile-categories">
			<h2>Категории товаров <span class="mobile-white-arrow"></span></h2>
			<ul class="mobile-cat-list">
				<?php echo $data['categoryList'] ?>
			</ul>
		</div>
		<!--Mobile category menu-->	
		<?php if(!URL::isSection(null)) :?>
			<div class="left-block">
				<span class="mobile-menu-toggler"></span>
				<div class="menu-block">
					<h2 class="cat-title">Категории</h2>
					<ul class="cat-list">
						<?php echo $data['categoryList'] ?>
					</ul>
				</div>
               <!--[catalog-filter]-->        
         
			</div>
    
		<?php  endif; ?>
		<?php if(URL::isSection(null)) :?>	
		<!--[slider-action]-->	
		<?php  endif;?>
		
				
		<div class="center">
    	<?php echo $data['content'] ?>     
		</div><!--end center-->
		<div class="clear">&nbsp;</div>
	</div><!--end container-->
	<div class="h-footer"></div>
</div><!--end wrapper-->
<div class="footer">
	<div class="copyright"> 2014 год. Все права защищены.</div>
	<div class="powered"> </div>
</div>
<?php echo MG::getSetting('widgetCode'); ?> 
</body>
</html>
