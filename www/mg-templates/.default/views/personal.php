<?php
 /**
 *  Файл представления Personal - выводит сгенерированную движком информацию на странице личного кабинета.
 *  В этом файле доступны следующие данные:
 *   <code>
 *     $data['error'] => Сообщение об ошибке.
 *     $data['message'] =>  Информационное сообщение.
 *     $data['status'] => Статус пользователя.
 *     $data['userInfo'] => Информация о пользователе.
 *     $data['orderInfo'] => Информация о заказе.
 *     $data['currency'] => $settings['currency'],
 *     $data['paymentList'] => $paymentList,
 *     $data['meta_title'] => Значение meta тега для страницы,
 *     $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *     $data['meta_desc'] => Значение meta_desc тега для страницы
 *   </code>
 *   
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>     
 *    <php viewData($data['userInfo']); ?>  
 *   </code>
 * 
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>     
 *    <php echo $data['message']; ?>  
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
<?php switch($data['status']){ case 1:?>
	<span style="color:red">Доступ пользователя к личному кабинету блокирован. Обратитесь к администратору</span>
<?php break; case 2:?>
	<span style="color:red">Пользователь не активирован! Для активации пользователя перейдите по ссылке указанной в письме, полученом Вами при регистрации</span><br>
	<span style="color:black">Запрос повторной активации</span>
	<form action = "<?php echo SITE ?>/registration" method = "POST">
		<input type="text" name="activateEmail" value="Email"></td>
		<input type = "submit" class="enter-btn default-btn" name="reActivate" value = "Отправить запрос">
	</form>
	
<?php break; case 3: $userInfo = $data['userInfo']?>
	<h1 class="new-products-title">Личный кабинет пользователя "<?php echo $userInfo->name?>"</h1>
<?php if($data['message']):?>
	<span  style="color:green" class="personalInformer"><?php echo $data['message']?></span>
<?php endif; ?>

<?php if($data['error']):?>
	<span style="color:red" class="personalInformer"><?php echo $data['error']?></span>
<?php endif;?>

<div class="create-user-account-form">
	<p class="custom-text">В своем кабинете Вы сможете следить за статусами Ваших заказов, так же изменять свои личные данные.</p>

	<div class="personal-tabs">
		<ul class="personal-tabs-list">
			<li><a href="#personal">Личные данные</a></li>
			<li><a href="#change-pass">Сменить пароль</a></li>
			<li><a href="#orders-history">История заказов</a></li>
		</ul>

		<div id="personal">
			<p class="change-pass-title">Личные данные</p>
			<form action = "<?php echo SITE?>/personal" method = "POST">
				<ul class="form-list">
					<li>Email: <span class="normal-text"><?php echo $userInfo->email?></span></li>
					<li>Дата регистрации: <span class="normal-text"><?php echo date('d.m.Y', strtotime($userInfo->date_add))?></span></li>
				</ul>
				<ul class="form-list">
					<li>Имя:</li>
					<li><input type="text" name="name" value="<?php echo $userInfo->name?>"></li>
					<li>Фамилия:</li>
					<li><input type="text" name="sname" value="<?php echo $userInfo->sname?>"></li>
					<li>Телефон:</li>
					<li><input type="text" name="phone" value="<?php echo $userInfo->phone?>"></li>
					<li>Адрес доставки:</li>          
					<li><textarea class="address-area" name="address"><?php echo $userInfo->address?></textarea></li>
          <li>
          <select name="customer">
            <?php $selected = $userInfo->inn?'selected':'';?>
            <option value="fiz">Физическое лицо</option>
            <option value="yur" <?php echo $selected?>>Юридическое лицо</option>
          </select>
          </li> 
				</ul>
        
        <?php if(!$userInfo->inn) {$style = 'style="display:none"';}?>
            <ul class="form-list yur-field" <?php echo $style?>>
                <li>Юр. лицо:</li>
                <li><input type="text" name="nameyur" value="<?php echo $userInfo->nameyur ?>"></li>
                <li>Юр. адрес:</li>
                <li><input type="text" name="adress" value="<?php echo $userInfo->adress ?>"></li>
                <li>ИНН:</li>
                <li><input type="text" name="inn" value="<?php echo $userInfo->inn ?>"></li>
                <li>КПП:</li>
                <li><input type="text" name="kpp" value="<?php echo $userInfo->kpp ?>"></li>
                <li>Банк:</li>
                <li><input type="text" name="bank" value="<?php echo $userInfo->bank ?>"></li>
                <li>БИК:</li>
                <li><input type="text" name="bik" value="<?php echo $userInfo->bik ?>"></li>
                <li>К/Сч:</li>
                <li><input type="text" name="ks" value="<?php echo $userInfo->ks ?>"></li>
                <li>Р/Сч:</li>
                <li><input type="text" name="rs" value="<?php echo $userInfo->rs ?>"></li>
            </ul>
				<button type="submit" class="save-btn default-btn" name="userData" value ="save">Сохранить</button>
			</form>
		</div>
		<div id="change-pass">
			<p class="change-pass-title">Сменить пароль</p>
			<form action = "<?php echo SITE?>/personal" method = "POST">
				<p class="custom-text"><span class="red-star">*</span>Поля отмеченные красной звездочкой, обязательны к заполнению.</p>
				<ul class="form-list">
					<li>Старый пароль:<span class="red-star">*</span></li>
					<li><input type="password" name="pass"></li>
					<li>Новый пароль(не менее 5 символов):<span class="red-star">*</span></li>
					<li><input type="password" name="newPass"></li>
					<li>Повторите новый пароль:<span class="red-star">*</span></li>
					<li><input type="password" name="pass2"></li>
				</ul>
				<button type="submit" class="save-btn default-btn" name="chengePass" value = "save">Сохранить</button>
				<div class="clear"></div>
			</form>
		</div>

		<div id="orders-history">
			<?php    
       if($data['orderInfo']):?>
			<div class="order-history-list">
				<p class="change-pass-title">История заказов</p>
				<?php foreach ($data['orderInfo'] as $order):?>
				<div class="order-history" id="<?php echo $order['id'] ?>">
					<div class="order-number">
						Заказ <strong>№<?php echo $order['id'] ?></strong>
						от <?php echo date('d.m.Y', strtotime($order['add_date']))?>
						<span class="order-status"> Cтатус заказа: <strong><?php echo $lang[$order['string_status_id']]?></strong></span>
					</div>
					<table class="status-table">
						<tr>
							<th>Товар</th>
							<th>Артикул</th>
							<th>Цена</th>
							<th>Количество</th>
							<th>Сумма</th>
						</tr>
						<?php $perOrders = unserialize(stripslashes($order['order_content']));?>
						<?php if(!empty($perOrders)) foreach ($perOrders as $perOrder):?>
            <?php  $coupon = $perOrder['coupon'];?>
						<tr>
							<td>
								<a href="<?php echo $perOrder['url']?>" target="_blank"><?php echo $perOrder['name'] ?></a>
                <br/>
								<?php echo htmlspecialchars_decode(str_replace('&amp;','&',$perOrder['property'])) ?>
							</td>
							<td><?php echo $perOrder['code'] ?></td>
							<td><?php echo $perOrder['price'].'  '.$data['currency']; ?></td>
							<td><?php echo $perOrder['count'] ?> шт.</td>
							<td><?php echo $perOrder['price'] * $perOrder['count'].'  '.$data['currency']; ?></td>
						</tr>
						<?php endforeach; ?>
					</table>
					<?php if(2 > $order['status_id']):?>
					<div class="order-settings">
						<form  method="POST" action="<?php echo SITE?>/order">
							<input type="hidden" name="orderID" value="<?php echo $order['id']?>">
							<input type="hidden" name="orderSumm" value="<?php echo $order['summ']?>">
							<input type="hidden" name="paymentId" value="<?php echo $order['payment_id']?>">
							<button type="submit" name="pay" value="go" class="default-btn">Оплатить заказ</button>
						</form>
					</div>
					<?php endif;?>
					<?php if($order['status_id'] < 2):?>
						<div class="order-settings">
							<button class="close-order" id="<?php echo $order['id'] ?>" date="<?php echo date('d.m.Y', strtotime($order['add_date']))?>" href="#openModal">
								Отменить заказ
							</button>
              <br/>
              <button class="change-payment" id="<?php echo $order['id'] ?>" date="<?php echo date('d.m.Y', strtotime($order['add_date']))?>" href="#changePayment">
								Изменить способ оплаты
							</button>
						</div>
					<?php endif;?>
					<div class="order-total">
						<ul class="total-list">
              <?php if($coupon):?>
              <li>Купон: <span title="<?php echo $coupon?>"><?php echo MG::textMore($coupon, 20)?></span></li>
              <?php endif;?>
							<li>Итого: <span><?php echo $order['summ'].'  '.$data['currency']?></span></li>
							<?php if($order['description']): ?>
								<li>Доставка: <span><?php echo $order['description']?></span></li>
							<?php endif;?>
							<li>Оплата: <span class="paymen-name-to-history"><?php echo $order['name']?></span></li>
							<?php $totSumm = $order['summ'] + $order['delivery_cost'];?>
							<?php if($order['delivery_cost']): ?><li>Стоимость доставки : <span><?php echo $order['delivery_cost'].'  '.$data['currency']; ?></span></li>
							<?php endif;?>
							<li>Всего к оплате: <span><?php echo $totSumm.'  '.$data['currency']; ?></span></li>
						</ul>
					</div>
					<div class="clear">&nbsp;</div>
				</div>
				<?php endforeach;?>
				<div class="close-reason">
					<!--Эта часть пропадает после закрытия заказа-->
					<div class="close-reason-wrapper" id="openModal">
						<p class="order-number">Закрытие заказа №<strong name="orderId" class="orderId"></strong> от <span class="orderDate"></span></p>
						<p class="custom-text">Укажите причину закрытия заказа:</p>
						<textarea class="reason-text" type="text" name="comment_textarea"></textarea>
						<button type="submit" class="close-order-btn default-btn" >Закрыть</button>
						<a class="close-order" href="#successModal" name="next"></a>
						<a class="close-order" href="#errorModal" name="error"></a>
						<div class="clear"></div>
					</div>
					<!--Эта часть пропадает после закрытия заказа-->

					<!--Эта часть появляется после закрытия заказа без перезагрузки страницы-->
					<div class="successful-closure" id="successModal">
						<div class="succes-img"></div>
						<p class="order-close-text">Заказ №<strong class="orderId"></strong> от <span class="orderDate"></span></p>
						<p class="order-close-text green-color">Был успешно отменен!</p>
						<p id="order-comm"></p>
						<a href="#" id="close-order-successbtn" onClick="$.fancybox.close();" class="default-btn">Выход</a>
						<div class="clear"></div>
					</div>
					<!--Эта часть появляется после закрытия заказа без перезагрузки страницы-->
					<div class="successful-closure" id="errorModal">Ошибка</div>
				</div>
        
        <!--Смена способа оплаты заказа-->
        <div class="change-payment">				
					<div class="close-reason-wrapper" id="changePayment">
						<p class="order-number">Выберите способ оплаты для заказа №<strong name="orderId" class="orderId"></strong> от <span class="orderDate"></span></p>	
            <select class="order-changer-pay">
            <?php 
            foreach ($data['paymentList'] as $item) {             
               $delivery = json_decode($item['deliveryMethod']);            
              if($delivery->{$order['delivery_id']}){
               echo "<option value='".$item['id']."'>".$item['name'].'</option>';             
              }
            }
            ?>
            </select>
						<button type="submit" class="change-payment-btn default-btn" >Применить</button>				
						<div class="clear"></div>
					</div>		
				</div>
        
			</div>
			<div class="clear">&nbsp;</div>
			<?php else:?> <!-- if($data['orderInfo']) -->
			<br><span>У вас нет заказов</span>
			<?php endif?> <!-- if($data['orderInfo']) -->
		</div>
	</div>
</div>
<?php break;
default :?>
<span style="color:red">Личный кабинет доступен только авторизованым пользователям!</span>
<?php }?> <!-- endswitch -->