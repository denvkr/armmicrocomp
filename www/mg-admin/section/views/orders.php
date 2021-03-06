<div class="section-order">
  <div class="widget-table-wrapper">
    <div class="widget-table-title">
      <h4 class="product-order-icon"><?php echo $lang['TITLE_ORDERS']; ?></h4>
      <p class="produc-count"><?php echo $lang['ALL_COUNT_ORDER']; ?>: <strong><?php echo $orderCount ?></strong> <?php echo $lang['UNIT']; ?></p>
      <div class="clear"></div>
    </div>

    <!--Модальное окно заказов-->
    <div class="b-modal hidden-form" id="add-order-wrapper">
      <div class="orders-table-wrapper">
        <div class="widget-table-title">
          <h4 class="add-order-table-icon"></h4>
          <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL']; ?>"></div>
        </div>
        <div class="widget-table-body">
          <div class="order-preview">                  

            <div class="category-filter">
              <button class="editor-order tool-tip-bottom custom-btn order-edit-visible" title="Редактировать" data-id=""><span>Редактировать</span></button>
              <button class="print-button tool-tip-bottom custom-btn order-edit-visible" title="<?php echo $lang['T_TIP_PRINT_ORDER']; ?>" data-id=""><span><?php echo $lang['PRINT_ORDER']; ?></span></button>
              <button class="csv-button tool-tip-bottom custom-btn order-edit-visible" title="<?php echo $lang['T_TIP_PRINT_ORDER']; ?>" data-id=""><span>Сохранить в CSV</span></button>
              <button class="get-pdf-button tool-tip-bottom custom-btn order-edit-visible" title="<?php echo $lang['T_TIP_PRINT_ORDER_PDF']; ?>"  data-id=""><span><?php echo $lang['PRINT_ORDER_PDF']; ?></span></button>
              <span class="custom-text">Статус заказа:</span>
              <select id="orderStatus" class="last-items-dropdown custom-dropdown tool-tip-right" title="<?php echo $lang['SELECT_ORDER_STATUS']; ?>"  name="status_id">
                <?php foreach ($assocStatus as $k => $v): ?>
                  <option value="<?php echo $k ?>"> <?php echo $lang[$assocStatus[$k]] ?> </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="loading-block"></div>
            <div class="clear"></div>
            
            <div id="order-data">
              <div class="search-block order-edit-display">
                <span>Добавить товар: </span>
                <input type="text" autocomplete="off" name="searchcat" class="search-field" placeholder="Наименование или артикул товара" >
                <p class="example-line">Ведите, например: <a href="javascript:void(0)" class="example-find" ><?php echo $exampleName?></a></p>
                <div class="fastResult"></div>
                <div class="errorField" style="display: none;">Необходимо добавить товар к заказу!</div>
              </div>

              <div class="product-block">
                <!-- Здесь будет отображена карточка товара -->
              </div>

              <form name="orderContent">   
                <div class="order-history">   
                </div>
              </form>

            </div>
            <button class="save-button tool-tip-bottom" title="<?php echo $lang['APPLY']; ?>"><span><?php echo $lang['APPLY']; ?></span></button>
            <div class="clear"></div>
          </div>
        </div>
      </div>
    </div>



    <!-- Тут начинается  Верстка таблицы заказов -->
    <div class="widget-table-body">

      <div class="widget-table-action">
        <a href="javascript:void(0);" class="add-new-button tool-tip-top" title="<?php echo $lang['T_TIP_ADD_NEW_ORDER']; ?>"><span><?php echo $lang['ADD_NEW_ORDER']; ?></span></a>
        <a href="javascript:void(0);" class="show-filters tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_FILTER']; ?>"><span><?php echo $lang['FILTER']; ?></span></a>
        <a href="javascript:void(0);" class="show-property-order tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_PROPERTY_ORDER']; ?>"><span><?php echo $lang['SHOW_PROPERTY_ORDER']; ?></span></a>

        <div class="filter">
          <span class="last-items"><?php echo $lang['SHOW_COUNT_ORDER']; ?></span>
          <select class="last-items-dropdown countPrintRowsOrder">
            <?php
            foreach (array(5, 10, 15, 20, 25, 30) as $value) {
              $selected = '';
              if ($value==$countPrintRowsOrder) {
                $selected = 'selected="selected"';
              }
              echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
            ?>
          </select>
        </div>
        <div class="clear"></div>
      </div>

      <div class="filter-container" <?php if ($displayFilter) {
              echo "style='display:block'";
            } ?>>
          <?php echo $filter ?>
        <a href="javascript:void(0);" class="refreshFilter"><span><?php echo $lang['CLEAR']; ?></span></a>
        <div class="clear"></div>
      </div>

      <div class="property-order-container">    
        <h2><?php echo $lang['OREDER_LOCALE_24'] ?>:</h2>
        <form name="requisites" method="POST">
          <ul class="requisites-list">
            <li><span><?php echo $lang['OREDER_LOCALE_9'] ?>:</span><input type="text" name="nameyur" value="<?php echo $propertyOrder["nameyur"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_15'] ?>:</span><input type="text" name="adress" value="<?php echo $propertyOrder["adress"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_16'] ?>:</span><input type="text" name="inn" value="<?php echo $propertyOrder["inn"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_17'] ?>:</span><input type="text" name="kpp" value="<?php echo $propertyOrder["kpp"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_32'] ?>:</span><input type="text" name="ogrn" value="<?php echo $propertyOrder["ogrn"] ?>"></li>               
            <li><span><?php echo $lang['OREDER_LOCALE_18'] ?>:</span><input type="text" name="bank" value="<?php echo $propertyOrder["bank"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_19'] ?>:</span><input type="text" name="bik" value="<?php echo $propertyOrder["bik"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_20'] ?>:</span><input type="text" name="ks" value="<?php echo $propertyOrder["ks"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_21'] ?>:</span><input type="text" name="rs" value="<?php echo $propertyOrder["rs"] ?>"></li>
            <li><span><?php echo $lang['OREDER_LOCALE_25'] ?>:</span><input type="text" name="general" value="<?php echo $propertyOrder["general"] ?>"></li>

          </ul>

          <ul class="order-form-img-list">
            <li><span><?php echo $lang['OREDER_LOCALE_26'] ?>: </span><input type="hidden" name="sing" value="<?php echo $propertyOrder["sing"] ?>">
              <img class="singPreview" src="<?php echo file_exists(SITE.'/'.$propertyOrder["sing"]) ? SITE.'/'.$propertyOrder["sing"] : SITE.'/uploads/sing.jpg'; ?>"><br/>             
              <a href="javascript:void(0);" class="upload-sign custom-btn"><span><?php echo $lang["UPLOAD"] ?></span></a>
            </li>
            <li><span><?php echo $lang['OREDER_LOCALE_27'] ?>:</span><input type="hidden" name="stamp" value="<?php echo $propertyOrder["stamp"] ?>">                  
              <img class="stampPreview" src="<?php echo file_exists(SITE.'/'.$propertyOrder["stamp"]) ? SITE.'/'.$propertyOrder["stamp"] : SITE.'/uploads/stamp.jpg'; ?>"><br/>
              <a href="javascript:void(0);" class="upload-stamp custom-btn"><span><?php echo $lang["UPLOAD"] ?></span></a>
            </li>
          </ul>
          <ul class="nds-list">
            <li><?php echo $lang['OREDER_LOCALE_28'] ?>: <input  type="text" name="nds" size="2" value="<?php echo $propertyOrder["nds"] ?>"> %</li>
            <li><?php echo $lang['OREDER_LOCALE_29'] ?>: <input type="checkbox" name="usedsing" value="<?php echo $propertyOrder["usedsing"] ?>" <?php echo $propertyOrder["usedsing"] ? 'checked=cheked' : '' ?>></li>
            <li><?php echo $lang['OREDER_LOCALE_30'] ?>: <input  type="text" name="prefix" value="<?php echo $propertyOrder["prefix"] ?>"></li>      
            <li><?php echo $lang['OREDER_LOCALE_31'] ?>: <input  type="text" name="currency" placeholder="рубль,рубля,рублей" value="<?php echo $propertyOrder["currency"] ?>"></li>   
          </ul>
          <div class="clear"></div>
        </form>
        <div class="clear"></div>
        <a href="javascript:void(0);" class="save-property-order custom-btn"><span><?php echo $lang['SAVE']; ?></span></a>
        <div class="clear"></div>
      </div>

      <div class="main-settings-container">
        <table class="widget-table product-table">
          <thead>
            <tr>
              <th class="id-width">№</th>
              <th>

                <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="add_date") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="add_date") ? $sorterData[1]*(-1) : 1 ?>" data-field="add_date"><?php echo $lang['ORDER_ADD_DATE']; ?></a>
              </th>

              <th>
                <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="user_email") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="user_email") ? $sorterData[1]*(-1) : 1 ?>" data-field="user_email"><?php echo $lang['ORDER_EMAIL']; ?></a>
              </th>
              <th>
                <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="delivery_id") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="delivery_id") ? $sorterData[1]*(-1) : 1 ?>" data-field="delivery_id"><?php echo $lang['ORDER_DELIVERY']; ?></a>
              </th>
              <th>
                <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="payment_id") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="payment_id") ? $sorterData[1]*(-1) : 1 ?>" data-field="payment_id"><?php echo $lang['ORDER_PAYMENT']; ?></a>
              </th>
              <th>
                <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="summ") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="summ") ? $sorterData[1]*(-1) : 1 ?>" data-field="summ"><?php echo $lang['ORDER_SUMM']; ?></a>
              </th>              
              <th>
                <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="status_id") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="status_id") ? $sorterData[1]*(-1) : 1 ?>" data-field="status_id"><?php echo $lang['ORDER_STATUS']; ?></a>
              </th>
              <th class="actions"><?php echo $lang['ACTIONS']; ?>
              </th>
            </tr>
          </thead>
          <tbody class="order-tbody">
          <?php if ($orders) { ?>
            <?php foreach ($orders as $order) { ?>

                <tr class="" order_id="<?php echo $order['id'] ?>" >
                  <td > <?php echo $order['id'] ?></td>
                  <td class="add_date"> <?php echo date('d.m.Y H:i', strtotime($order['add_date'])); ?></td>
                  <td > <?php echo $order['user_email'] ?></td>
                  <td > <?php echo $assocDelivery[$order['delivery_id']] ?></td>
                  <td ><span class="icon-payment-<?php echo $order['payment_id'] ?>"></span> <?php echo $assocPay[$order['payment_id']] ?></td>
                  <td > <?php echo ($order['summ']+$order['delivery_cost']) ?> <?php echo MG::getSetting('currency'); ?></td>
                  <td class="statusId id_<?php echo $order['status_id'] ?>">
                    <span class="<?php echo $assocStatusClass[$order['status_id']] ?>">
                        <?php echo $lang[$assocStatus[$order['status_id']]] ?>
                    </span>
                  </td>

                  <td class="actions">
                    <ul class="action-list">
                      <li class="see-order" id="<?php echo $order['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);" title="<?php echo $lang['SEE']; ?>"></a></li>
                      <li class="order-to-csv"><a  data-id="<?php echo $order['id'] ?>" class="tool-tip-bottom" href="javascript:void(0);" title="Сохранить в CSV"></a></li>
                          <?php if (empty($order['yur_info'])) {
                            $textBtnFdf = "квитанцию";
                          }
                          else $textBtnFdf = "счет"; ?>
                      <li class="order-to-pdf"><a data-id="<?php echo $order['id'] ?>" class="tool-tip-bottom" href="javascript:void(0);" title="Сохранить <?php echo $textBtnFdf; ?> в PDF"></a></li>

                      <li class="order-to-print"><a  data-id="<?php echo $order['id'] ?>" class="tool-tip-bottom" href="javascript:void(0);" title="Печать <?php echo $textBtnFdf; ?>"></a></li>
                      <li class="delete-order " id="<?php echo $order['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);"  title="<?php echo $lang['DELETE']; ?>"></a></li>
                    </ul>
                  </td>
                </tr>

              <?php
              }
            }else {
              ?>

              <tr><td colspan="8" class="noneOrders"><?php echo $lang['ORDER_NONE'] ?></td></tr>

            <?php } ?>

          </tbody>
        </table>
      </div>
      <?php echo $pager ?>
      <div class="clear"></div>
    </div>
  </div>
</div>