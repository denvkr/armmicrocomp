<?php
 /**
 *  Файл представления Catalog - выводит сгенерированную движком информацию на странице сайта с каталогом товаров.
 *  В этом  файле доступны следующие данные:
 *   <code>     
 *    $data['items'] => Массив товаров 
 *    $data['titeCategory'] => Название открытой категории
 *    $data['cat_desc'] => Описание открытой категории
 *    $data['pager'] => html верстка  для навигации страниц
 *    $data['searchData'] =>  результат поисковой выдачи
 *    $data['meta_title'] => Значение meta тега для страницы 
 *    $data['meta_keywords'] => Значение meta_keywords тега для страницы 
 *    $data['meta_desc'] => Значение meta_desc тега для страницы 
 *    $data['currency'] => Текущая валюта магазина
 *    $data['actionButton'] => тип кнопки в миникарточке товара
 *   </code>
 *   
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>     
 *    <php viewData($data['items']); ?>  
 *   </code>
 * 
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>     
 *    <php echo $data['items']; ?>  
 *   </code>
 * 
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложую программную логику логику.
 *   @author Авдеев Марк <mark-avdeev@mail.ru>
 *   @package moguta.cms
 *   @subpackage Views
 */

  // Установка значений в метатеги title, keywords, description.
  mgSEO($data);

?>

<!-- Верстка каталога -->
	<?php if (empty($data['searchData'])): ?>
  [brcr]
	<h1 class="new-products-title"><?php echo $data['titeCategory'] ?></h1>
  <?php if(!empty($data['cat_desc'])):?>
  <div class="cat-desc">
		<?php echo $data['cat_desc']?>
	</div>
  <?php endif;?>
	<div class="products-wrapper">   
		<?php foreach ($data['items'] as $item): ?>

		<div class="product-wrapper">
			<div class="product-image">
        <?php   
         echo $item['recommend']?'<span class="sticker-recommend"></span>':'';
         echo $item['new']?'<span class="sticker-new"></span>':'';      
        ?> 
				<a href="<?php echo SITE ?>/<?php echo isset($item["category_url"]) ? $item["category_url"] : 'catalog' ?>/<?php echo $item["product_url"] ?>">
					<img src="<?php echo $item["image_url"] ? SITE.'/uploads/thumbs/70_'.$item["image_url"] : SITE."/uploads/no-img.jpg" ?>" alt="">
				</a>
			</div>
			<div class="product-name">
				<a href="<?php echo SITE ?>/<?php echo isset($item["category_url"]) ? $item["category_url"] : 'catalog' ?>/<?php echo $item["product_url"] ?>"><?php echo $item["title"] ?></a>
			</div>
			<span class="product-price"><?php echo $item["price"] ?> <?php echo $data['currency']; ?></span>
			<!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
		  <?php 
		  if (!$item['liteFormData']){
        if($item['count']==0){
          echo $item['actionView'];          
        }else{
          echo $item[$data['actionButton']]; 
        }
		  } else{
			  echo $item['liteFormData'];
		  }
		  ?>
		</div>

		<?php endforeach; ?>

		<div class="clear"></div>
		<?php
		  // выводим постраничную навигацию
		  echo $data['pager'];
		?>
		<!-- / Верстка каталога -->
	</div>
  <!-- Верстка поиска -->
<?php else: ?>

  <h1 class="new-products-title">При поиске по фразе: <strong>"<?php echo $data['searchData']['keyword'] ?>"</strong> найдено
    <strong><?php echo mgDeclensionNum($data['searchData']['count'], array('товар', 'товара', 'товаров')); ?></strong>
  </h1>

  <div class="search-results">
    <?php 
    foreach ($data['items'] as $item): ?>  
    <div class="product-wrapper">
      <div class="product-image">
        <?php        
          echo $item['recommend']?'<span class="sticker-recommend"></span>':'';
          echo $item['new']?'<span class="sticker-new"></span>':'';
        ?>
        <a href="<?php echo SITE ?>/<?php echo isset($item["category_url"]) ? $item["category_url"] : 'catalog' ?>/<?php echo $item["product_url"] ?>">
          <img src="<?php echo SITE ?>/uploads/<?php echo $item["image_url"] ? $item["image_url"] : "none.png" ?>" alt="">
        </a>
      </div>
      <div class="product-desc">
        <div class="product-name">
          <a href="<?php echo SITE ?>/<?php echo isset($item["category_url"]) ? $item["category_url"] : 'catalog' ?>/<?php echo $item["product_url"] ?>"><?php echo $item["title"] ?></a>
        </div>
        <div class="product-desc"><?php echo MG::textMore($item["description"], 240) ?></div>
        <span class="product-price"><?php echo $item["price"] ?>  <?php echo $data['currency']; ?></span>
         <?php 
          if (!$item['liteFormData']){
            echo $item[$data['actionButton']];      
          } else{
            echo $item['liteFormData'];
          }
        ?>
        <div class="clear"></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="clear"></div>
  </div>

  <?php
  echo $data['pager'];
endif;
?>
<!-- / Верстка поиска -->