<div class="section-settings">
  <div class="widget-table-wrapper">
    <div class="widget-table-title">
      <h4 class="settings-table-icon"><?php echo $lang['TITLE_SETTINGS'];?></h4>
    </div>

    <!-- Тут начинается Верстка модального окна -->
      <div class="b-modal hidden-form" id="add-list-cat-wrapper">
        <div class="properties-table-wrapper">
          <div class="widget-table-title">
            <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['STNG_LIST_CAT'];?></h4>
                <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL'];?>"></div>
          </div>
          <div class="widget-table-body">
            <div class="add-product-form-wrapper">
              <div id="select-category-form-wrapper" class="user-fields-wrapper">
                <select  class ="tool-tip-right category-select" title="<?php echo $lang['T_TIP_SELECTED_U_CAT'];?>" name="listCat" multiple>';
                </select>
              </div>
              <div class="user-fields-desc-wrapper">
                <span><?php echo $lang['STNG_LISC_SELECT_CAT'];?></span> : "<span class="propertyName"></span>"
                <p class="clear-text"><?php echo $lang['STNG_LISC_TIP'];?></p>
                      <a href="javascript:void(0);" class="cancelSelect"><?php echo $lang['STNG_LISC_CANCEL_SELECT'];?></a>
              </div>
              <div class="clear"></div>
              <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE_U_CAT'];?>">
              <span><?php echo $lang['SAVE'];?></span>
              </button>
              <div class="clear"></div>
            </div>
          </div>
        </div>
      </div>
    <!-- Тут заканчивается Верстка модального окна -->


    <div class="widget-table-body">
      <div id="settings-tabs">
        <ul class="tabs-list">
          <li class="ui-state-active" >
            <a href="javascript:void(0);" class="tool-tip-top" id="tab-shop" title="<?php echo $lang['T_TIP_TAB_SHOP'];?>"><span><?php echo $lang['STNG_TAB_SHOP'];?></span></a>
          </li>
          <li>
            <a href="javascript:void(0);" class="tool-tip-top" id="tab-system" title="<?php echo $lang['T_TIP_TAB_SYSTEM'];?>"><span ><?php echo $lang['STNG_TAB_SYSTEM'];?></span></a>
          </li>
          <li>
            <a href="javascript:void(0);" class="tool-tip-top" id="tab-template" title="<?php echo $lang['T_TIP_TAB_TEMPLATE'];?>"><span ><?php echo $lang['STNG_TAB_TEMPLATE'];?></span></a>
          </li>
          <li>
            <a href="javascript:void(0);" class="tool-tip-top" id="interface" title="<?php echo $lang['T_TIP_TAB_INTERFACE'];?>"><span ><?php echo $lang['STNG_TAB_INTERFACE'];?></span></a>
          </li>
          <li>
            <a href="javascript:void(0);" class="tool-tip-top" id="tab-userField" title="<?php echo $lang['T_TIP_TAB_USERFIELDS'];?>"><span ><?php echo $lang['STNG_USER_FIELD'];?></span></a>
          </li>
          <li>
            <a href="javascript:void(0);" class="tool-tip-top" id="tab-deliveryMethod" title="<?php echo $lang['T_TIP_TAB_DELIVERY'];?>"><span ><?php echo $lang['STNG_TAB_DELIVERY'];?></span></a>
          </li>
          <li>
            <a href="javascript:void(0);" class="tool-tip-top" id="tab-paymentMethod" title="<?php echo $lang['T_TIP_TAB_PAYMENT'];?>"><span ><?php echo $lang['STNG_TAB_PAYMENT'];?></span></a>
          </li>
        </ul>
        <div class="clear"></div>
        <div class="tabs-content">
          <!--Раздел настроек магазина-->
          <div class="main-settings-container" id="tab-shop-settings">
              <h4><?php echo $lang['STNG_MAIN_SITE'];?></h4>
                <table class="main-settings-list">
                  <?php foreach($data['setting-shop']['options'] as $option):?>
                  <tr id="data">
                    <td>
                      <span><?php echo $lang[$option['name']]; if($option['option']=="waterMark"):?> 
                        <br/>
                         <div class="watermark-img" >
                           <img style="max-width:200px;"  src="<?php echo SITE?>/uploads/watermark/watermark.png">
                         </div>
                         <form class="watermarkform" method="post" noengine="true" enctype="multipart/form-data">
                           <a href="javascript:void(0);" class="add-watermark">
                           <span><?php echo $lang['SETTING_LOCALE_27']?></span>
                             <input type="file" name="photoimg" class="add-img tool-tip-top" title="<?php echo $lang['SETTING_LOCALE_27']?>">
                           </a>
                         </form>
                       <?php endif;?>
                      </span>
                    </td>
                    <td>
                      <?php if($option['option'] == 'templateName'): ?>
                      <select class="option last-items-dropdown" name="<?php echo $option['option'] ?>" >
                        <?php foreach($data['setting-shop']['templates'] as $template):?>
                          <option value="<?php echo $template ?>" <?php if($template == $option['value']){ echo "selected";} ?> >
                            <?php echo $template ?>
                          </option>
                        <?php endforeach;?>
                      </select>
                      <?php elseif($option['option'] == 'orderMessage' || $option['option'] == 'widgetCode'):?>
                      <textarea style="width:200px; height:100px;" name="<?php echo $option['option'] ?>" class="settings-input option"><?php echo $option['value'] ?></textarea>
                      <?php elseif(in_array($option['option'], array('mainPageIsCatalog','actionInCatalog','printProdNullRem','printRemInfo','waterMark'))):?>
                        
                      <input type="checkbox" class="option" name="<?php echo $option['option'] ?>" value="<?php echo $option['value'] ?>" <?php if('true' == $option['value']){ echo "checked='checked'";} ?> />   
                      <?php else:?>
                      
                      <?php $numericProtection=""; if (in_array($option['option'],$data['numericFields'])){$numericProtection = "numericProtection";}; ?>
                      <input type="text"  name="<?php echo $option['option'] ?>" class="settings-input option <?php echo $numericProtection ?>" value="<?php echo $option['value'] ?>">
                      <?php endif;?>
                      </td>
                      <td><?php echo $lang['DESC_'.$option['name']] ?></td>
                    </tr>
                   <?php endforeach;?>
                </table>
                <button class="save-button save-settings"><span><?php echo $lang['SAVE'] ?></span></button>
                <div class="clear"></div>
               </div>
          <!--Раздел настроек системы-->
          <div class="main-settings-container" id="tab-system-settings" style="display:none">
            <h4><?php echo $lang['STNG_SYSTEM']?></h4>
            <table class="main-settings-list">
              <tr>
                <td>
                  <?php $downtime = $data['setting-system']['options']['downtime']['value'];
                  $checked = '';
                  $value = 'value="false"';

                  if($downtime=="true"){
                    $checked = 'checked="checked"';
                    $value = 'value="'.$downtime.'"';
                  }?>
                  <p class="close-site">
                    <label>
                      <input class="option" type="checkbox" <?php echo $value ?> <?php echo $checked ?> name="downtime"><span><?php echo $lang['DOWNTIME_SITE']?></span>
                    </label> 
                  </p>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="key-text"><?php echo $lang['LICENSE_KEY'] ?>:</span> <input type="text"  name="licenceKey" class="settings-input option licenceKey" value="<?php echo $data['setting-system']['options']['licenceKey']['value']?>" placeholder="Введите ключ и нажмите кнопку 'Cохранить'" style="width:270px;">
                  <span class="error-key" style="color:red; display: <?php echo (($updataDisabled!="disabled")?'none':'block'); ?>"><?php echo $lang['SETTING_LOCALE_1']?></span>
                  <?php 
                  $dateActivate = MG::getOption('dateActivateKey');
                  $dateActivate='2014-01-01 15:00:00';
                      if($dateActivate!='0000-00-00 00:00:00'){
                        $now_date = strtotime($dateActivate);                       
                        $future_date = strtotime(date("Y-m-d")); 
                        $dayActivate = (365-(floor(($future_date - $now_date) / 86400 )));
                        if($dayActivate<=0){$dayActivate=0; $extend=" [<a href='http://moguta.ru/extendcenter'>Продлить</a>]";}
                        $activeDate = " Данный ключ будет активен еще <span style='color:green'>".$dayActivate." дн.</span>".$extend;
                        
                      } else{
                        $activeDate = " <span style='color:red'>".$lang['SETTING_LOCALE_2']."</span>";
                      }
              
                  ?>
                  <br><span class="key-info"><?php echo $activeDate ?></span>
                
                </td>
              </tr>
              <tr>
                <td>
                  <dl>
                    <dt><?php echo $lang['STNG_CUR_VER']?><span><?php echo VER?></span></dt>
                    <dd id="updataMsg">
                      <?php if(!$errorUpdata):
                              if($newVersionMsg):
                                echo $newVersionMsg;?>
                                <span class="custom-text" style="color:red"><?php echo $lang['SETTING_LOCALE_3']?></span>
                                <br/><button rel="preDownload" class="update-now tool-tip-bottom <?php echo $updataOpacity ?>" title="Начать процесс обновления обновления" <?php echo $updataDisabled ?> >
                                  <span id="go"><?php echo $lang['SETTING_LOCALE_5']?></span>
                                </button>
                              <?php else:?> <!--if($newVersionMsg)-->
                                <strong><span style="color:green;"><?php echo $lang['SETTING_LOCALE_6']?></span></strong>
                              (<a href="javascript:void(0);" class="clearLastUpdate"><?php echo $lang['SETTING_LOCALE_7']?></a> )
                              <?php endif?><!--if($newVersionMsg)-->
                      <?php  else:?>  <!--if(!$errorUpdata)-->
                              <span style="color:red">
                                <?php echo $errorUpdata; ?> <?php echo $lang['SETTING_LOCALE_8']?>
                              </span>
                      <?php endif?> <!--if(!$errorUpdata)-->
                    </dd>
                  </dl>
                </td>
              </tr>
            </table>
            <button class="save-button save-settings"><span><?php echo $lang['SAVE']?></span></button>
            <div class="clear"></div>
            </div>
          <!--Раздел настроек пользовательских полей-->
          <div class="main-settings-container" id="tab-userField-settings" style="display:none">
            <h4><?php echo $lang['STNG_USER_FIELD'];?></h4>
            <button class="save-button addProperty"><span><?php echo $lang['SETTING_LOCALE_9']?></span></button>
            <table class="userField-settings-list main-settings-list"></table>
            <div class="clear"></div>
          </div>
          <!--Содержимое, показываемое при удачном загрузке архива с обновлением-->
          <div id="hiddenMsg" style="display:none">
            <?php echo $lang['SETTING_LOCALE_10']?> <b><span id="lVer"></b></span> <?php echo $lang['SETTING_LOCALE_11']?><br>
             <a href="javascript:void(0);" rel="postDownload" class="button"><span><?php echo $lang['SETTING_LOCALE_12']?></span></a>
          </div>
          
          <!--Раздел настроек шаблона -->
          <div class="main-settings-container" id="tab-template-settings" style="display:none;">
            <h4><?php echo $lang['STNG_TEMPLATE'];?></h4>

              <?php foreach($data['setting-template']['files'] as $filename=>$path):?>
              <a href="javascript:void(0);" class="file-template" data-path="<?php echo $path?>"><?php echo $filename?></a><!--javascript:void(0)-->
              <?php endforeach;?>               

              <textarea id="codefile" style="width:100%; height:500px; display:none;"></textarea>        
              <div class="error-not-tpl" style="display:none"><?php echo $lang['NOT_FILE_TPL'] ?></div>

            <button class="save-button save-file-template"><span><?php echo $lang['SAVE'] ?></span></button>
            <div class="clear"></div>
          </div>
          
          
          
          <!--Интерфейс-->
          <div class="main-settings-container" id="interface-settings" style="display:none;">
            <h4><?php echo $lang['STNG_INTERFACE'];?></h4>
              <table class="main-settings-list">
                <tr>
                  <td>
                    <p><?php echo $lang['SETTING_LOCALE_13']?></p>
                  </td>
                  <td>
                    <div class="color-settings">
                      <ul class="color-list">
                        <li class="red-theme"></li>
                        <li class="blue-theme"></li>
                        <li class="yellow-theme"></li>
                        <li class="green-theme"></li>
                      </ul>
                    </div>
                    <input type="hidden" name="themeColor" class="option" value="<?php echo $data['interface-settings']['options']['themeColor']['value'] ?>">
                  </td>
                  <td>
                    <p><?php echo $lang['SETTING_LOCALE_14']?></p>
                  </td>
                </tr>
                <tr>
                  <td>
                    <span>
                      <?php echo $lang['SETTING_LOCALE_15']?>
                    </span>
                  </td>
                  <td>
                    <div class="background-settings">
                      <ul class="color-list">
                        <li class="bg_1"></li>
                        <li class="bg_2"></li>
                        <li class="bg_3"></li>
                        <li class="bg_4"></li>
                        <li class="bg_5"></li>
                        <li class="bg_6"></li>
                        <li class="bg_7"></li>
                        <li class="bg_8"></li>
                      </ul>
                      <div class="clear"></div>
                    </div>
                    <input type="hidden" name="themeBackground" class="option" value="<?php echo $data['interface-settings']['options']['themeBackground']['value'] ?>">
                  </td>
                  <td>
                    <p><?php echo $lang['SETTING_LOCALE_16']?></p>
                  </td>
                </tr>
                <tr>
                  <td>
                    <span>
                      <?php echo $lang['SETTING_LOCALE_17']?>
                    </span>
                  </td>
                  <td>
                    <?php $staticMenu = $data['interface-settings']['options']['staticMenu']['value'];
                          $checked = '';
                          $value = 'value="false"';
                          if($staticMenu=="true"){
                           $checked = 'checked="checked"';
                           $value = 'value="'.$staticMenu.'"';
                          }
                    ?>
                    <input type="checkbox" <?php echo $value ?> <?php echo $checked ?> name="staticMenu" class="option the-fixed-menu">
                  </td>
                  <td>
                    <p><?php echo $lang['SETTING_LOCALE_18']?></p>
                  </td>
                </tr>
              </table>
              <button id="temp" class="save-button save-settings tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE_U_CAT'];?>">
                <span><?php echo $lang['SAVE'];?>
                </span>
              </button>
              <div class="clear"></div>
      </div>
          <!--Методы доставки-->
          <div class="main-settings-container" id="tab-deliveryMethod-settings" style="display:none;">
            <h4><?php echo $lang['STNG_DELIVERY'];?></h4>
            <a href="#" class="add-new-button tool-tip-bottom" title="<?php echo $lang['T_TIP_KEY_ADD_DELIVERY'];?>" ><span><?php echo $lang['STNG_KEY_ADD_DELIVERY'];?></span></a>
            <table class="main-settings-list">
              <thead class="yellow-bg">
                <th>id</th>
                <th><?php echo $lang['SETTING_LOCALE_19']?></th>
                <th><?php echo $lang['SETTING_LOCALE_20']?></th>
                <th><?php echo $lang['SETTING_LOCALE_21']?></th>
                <th>Бесплатная доставка от</th>
                <th><?php echo $lang['SETTING_LOCALE_22']?></th>
                <th><?php echo $lang['SETTING_LOCALE_23']?></th>
              </thead>
              <tbody class="deliveryMethod-tbody">
                <?php if(0 < count($data['deliveryMethod-settings']['deliveryArray'])):
                foreach($data['deliveryMethod-settings']['deliveryArray'] as $delivery):?>
                <tr id="delivery_<?php echo $delivery['id'] ?>">
                  <td class="deliveryId"><?php echo $delivery['id'] ?></td>
                  <td id="deliveryName"><?php echo $delivery['name'] ?></td>
                  <td id="deliveryCost"><span class="costValue"><?php echo $delivery['cost']?></span> <span class="currency"><span class="currency"><?php echo MG::getSetting('currency')?></span></span> </td>
                  <td id="deliveryDescription"><?php echo $delivery['description'] ?></td>
                  <td class="free"><span class="costFree"><?php echo $delivery['free'] ?></span> <span class="currency"><?php echo MG::getSetting('currency')?></span></td>
                  <td id="activity" status="<?php echo $delivery['activity'] ?>">
                    <?php if($delivery['activity']):?>
                    <span class="activity-product-true"><?php echo $lang['ACTYVITY_TRUE'];?></span>
                    <?php else:?>
                    <span class="activity-product-false"><?php echo $lang['ACTYVITY_FALSE'];?></span>
                    <?php endif?>
                  </td>
                  <td class="actions">
                    <ul class="action-list">
                      <li class="edit-row" id="<?php echo $delivery['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);" title="<?php echo $lang['EDIT'];?>"></a></li>
                      <li class="delete-row " id="<?php echo $delivery['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);" title="<?php echo $lang['DELETE'];?>"></a></li>
                    </ul>
                  </td>
                  <td id="paymentHideMethod" style="display: none"></td>
                </tr>
                <?php endforeach;
                else:?>
                <tr id="none_delivery"><td class="no-delivery" colspan="6"><?php echo $lang['NONE_DELIVERY'];?></td></tr>
                <?php endif;?>
              </tbody>
            </table>
        <!-- Верстка модального окна способов доставки-->
            <div class="b-modal hidden-form add-category-popup" id="add-deliveryMethod-wrapper">
                <div class="product-table-wrapper deliveryMethod-table-wrapper">
                    <div class="widget-table-title">
                        <h4 class="delivery-table-icon"></h4>
                        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_WITHOUT_SAVE'];?>"></div>
                    </div>
                    <div class="widget-table-body">
                        <div class="add-user-form-wrapper">
                            <div class="add-user-form">
                                <label>
                                  <span class="custom-text"><?php echo $lang['SETTING_LOCALE_19']?>:</span>
                                  <input type="text" name="deliveryName" class="product-name-input" title="<?php echo $lang['T_TIP_USER_EMAIL'];?>">
                                  <div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div>
                                </label>
                                <label>
                                  <span class="custom-text"><?php echo $lang['SETTING_LOCALE_20']?>:</span>
                                  <input type="text" name="deliveryCost" class="product-name-input">
                                  <span class="currency"><?php echo MG::getSetting('currency')?></span>
                                  <div class="errorField"><?php echo $lang['ERROR_NUMERIC'];?></div>
                                </label>
                                <label>
                                  <span class="custom-text"><?php echo $lang['SETTING_LOCALE_21']?>:</span>
                                  <input type="text" name="deliveryDescription" class="product-name-input">
                                  <div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div>
                                </label>
                                <label>
                                  <span class="custom-text">Бесплатная доставка от:</span>
                                  <input type="text" name="free" class="product-name-input tool-tip-bottom" title="Если ненужно учитывать это условие оставьте 0">
                                  <span class="currency"><?php echo MG::getSetting('currency')?></span>
                                  <div class="errorField"><?php echo $lang['ERROR_NUMERIC'];?></div>
                                </label>                             
                                <label>
                                  <span class="custom-text"><?php echo $lang['SETTING_LOCALE_22']?>:</span>
                                  <input type="checkbox" name="deliveryActivity" class="delivery-active">
                                </label>
                                <div id="paymentCheckbox">
                                  <span class="custom-text bold-text"><?php echo $lang['SETTING_LOCALE_24']?>:</span>
                                  <div id="paymentArray">
                                  <?php foreach($data['paymentMethod-settings']['paymentArray'] as $payment):?>
                                  <label>
                                    <span class="custom-text"><?php echo $payment['name']?></span>
                                    <input type="checkbox" name="<?php echo $payment['id']?>" class="paymentMethod">
                                  </label>
                                  <?php endforeach;?>
                                  </div>
                                </div>
                                <div class="clear"></div>
                                <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_SAVE'];?>"><span><?php echo $lang['SAVE'];?></span></button>
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <!-- Верстка модального окна  способов доставки-->
          </div>
          <!--Методы оплаты-->
          <div class="main-settings-container" id="tab-paymentMethod-settings" style="display:none;">
            <h4><?php echo $lang['STNG_PAYMENT'];?></h4>
            <?php //viewData($data['paymentMethod-settings']['paymentArray'])?>
            <table class="main-settings-list">
              <thead class="yellow-bg">
                <th class="id-way" style="display:none">id способа</th>
                <th><?php echo $lang['SETTING_LOCALE_19']?></th>
                <th><?php echo $lang['SETTING_LOCALE_22']?></th>
                <th><?php echo $lang['SETTING_LOCALE_23']?></th>
              </thead>
              <tbody class="paymentMethod-tbody">
              <?php foreach($data['paymentMethod-settings']['paymentArray'] as $payment):?>
              <tr id="payment_<?php echo $payment['id'] ?>">
                <td class="paymentId" style="display:none"><?php echo $payment['id'] ?></td>
                <td id="paymentName"><?php echo $payment['name'] ?></td>
                <td id="activity" status="<?php echo $payment['activity'] ?>">
                    <?php if($payment['activity']):?>
                    <span class="activity-product-true"><?php echo $lang['ACTYVITY_TRUE'];?></span>
                    <?php else:?>
                    <span class="activity-product-false"><?php echo $lang['ACTYVITY_FALSE'];?></span>
                    <?php endif?>
                </td>
                <td class="actions">
                  <ul class="action-list">
                      <li class="edit-row" id="<?php echo $payment['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);" title="<?php echo $lang['EDIT'];?>"></a></li>
                  </ul>
                </td>
                <td id="paramHideArray" style="display: none"><?php echo $payment['paramArray'] ? $payment['paramArray']: '{"0":0}' ?></td>
                <td id="deliveryHideMethod" style="display: none"><?php echo $payment['deliveryMethod'] ? $payment['deliveryMethod']: '{"0":0}' ?></td>
                <td id="urlArray" style="display: none"><?php echo $payment['urlArray'] ?></td>
              </tr>
              <?php endforeach;?>
              </tbody>
            </table>
            <!-- Верстка модального окна способов оплаты-->
            <div class="b-modal hidden-form add-category-popup" id="add-paymentMethod-wrapper">
                <div class="product-table-wrapper paymentMethod-table-wrapper">
                    <div class="widget-table-title">
                        <h4 class="payment-table-icon"></h4>
                        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_WITHOUT_SAVE'];?>"></div>
                    </div>
                    <div class="widget-table-body">
                        <div class="add-user-form-wrapper">
                            <div class="add-user-form">
                              <span class="custom-text"><strong><?php echo $lang['SETTING_LOCALE_19']?>:</strong></span>
                              <span id="paymentName"><?php echo $lang['SETTING_LOCALE_28']?></span><br>
                              <span class="custom-text bold-text"><?php echo $lang['SETTING_LOCALE_25']?>:</span>
                              <div id="paymentParam"></div>
                              <label>
                                <span class="custom-text"><?php echo $lang['SETTING_LOCALE_22']?>:</span>
                                <input type="checkbox" name="paymentActivity" class="payment-active">
                              </label>
                              <div id="deliveryCheckbox">
                                <span class="custom-text bold-text"><?php echo $lang['SETTING_LOCALE_26']?>:</span>
                                <div id="deliveryArray">
                                <?php foreach($data['deliveryMethod-settings']['deliveryArray'] as $delivery):?>
                                <label>
                                  <span class="custom-text"><?php echo $delivery['name']?></span>
                                  <input type="checkbox" name="<?php echo $delivery['id']?>" class="deliveryMethod">
                                </label>
                                <?php endforeach;?>
                                </div>
                              </div>
                              
                              <div id="urlParam"></div>
                              <div class="clear"></div>
                              <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_SAVE'];?>"><span><?php echo $lang['SAVE'];?></span></button>
                              <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Верстка модального окна  способов оплаты-->
            <div class="clear"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
