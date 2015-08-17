<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->

<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->

  <!-- Тут начинается Верстка модального окна -->
  <div class="b-modal hidden-form">
    <div class="custom-table-wrapper"><!-- блок для контента модального окна -->

      <div class="widget-table-title"><!-- Заголовок модального окна -->
        <h4 class="pages-table-icon" id="modalTitle">
          <?php echo $lang['HEADER_MODAL_ADD']; ?>
        </h4><!-- Иконка + Заголовок модального окна -->
        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['CLOSE_MODAL']; ?>"></div><!-- Кнопка для закрытия окнаа -->
      </div>

      <div class="widget-table-body slide-editor"><!-- Содержимое окна, управляющие элементы -->
        <ul class="text-list">
          <li>
            <span class="custom-text">Название:</span>
            <input type="text" name="nameEntity" value=""/>
          </li>
          <li>
            <span class="custom-text">Тип:</span>
            <select name="type">
              <option value="img" selected='selected'>Изображение</option>        
            </select>
          </li>
        </ul>
        <div class="block-for-form" >
          <ul class="custom-form-wrapper type-img">
            <li>
              <span>src =</span> <input type="text" name="src" value=""/>
              <a href="javascript:void(0);" class='browseImage'>выбрать изображение</a> 
            </li><!-- вызов файлового менеджера-->
          </ul>        
        </div>
        <button class="save-button tool-tip-bottom" data-id="" title="<?php echo $lang['SAVE_MODAL'] ?>"><!-- Кнопка действия -->
          <span><?php echo $lang['SAVE_MODAL'] ?></span>
        </button>
        <div class="clear"></div>
      </div>
    </div>
  </div>
  <!-- Тут заканчивается Верстка модального окна -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-table-body">
    <div class="wrapper-entity-setting">

      <!-- Тут начинается  Верстка базовых настроек  плагина (опций из таблицы  setting)-->
      <div class="widget-table-action base-settings">
        <h3>Настройки плагина</h3>

        <ul class="list-option"><!-- список опций из таблицы setting-->
          <li>
            <span>Ширина:</span> 
            <input type="text" name="width" value="<?php echo $options['width']; ?>">
          </li>
          <li>
            <span>Высота:</span>
            <input type="text" name="height" value="<?php echo $options['height']; ?>">
          </li>              
          <li>
            <span>Заголовок:</span>
            <input type="text" name="title" value="<?php echo $options['title']; ?>">
          </li>
          <li>
            <span>Количество строк:</span>
            <input type="text" name="countRows" value="<?php echo $options['countRows']; ?>">
          </li>    
        </ul>

        <!-- чекбокс -->
        <?php if ($options['nameEntity']=="true") {
          $checkbox = " value='true' checked=checked ";
        } else {
          $checkbox = " value='false' ";
        } ?>
        <span class="custom-text">Выводить заголовок:</span> 
        <input type="checkbox" class="option-slide" name="nameEntity" <?php echo $checkbox ?>>

        <button class="tool-tip-bottom base-setting-save save-button custom-btn" 
                data-id="" 
                title="<?php echo $lang['SAVE_MODAL'] ?>">
          <span><?php echo $lang['SAVE_MODAL'] ?></span> <!-- кнопка применения настроек -->
        </button>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">        
        <div class="add-new-button tool-tip-bottom" title="<?php echo $lang['ADD_MODAL']; ?>">
          <span><?php echo $lang['ADD_MODAL']; ?></span>
        </div>
        <div class="clear"></div>
        <div class="entity-settings-table-wrapper">
          <table class="widget-table">
            <thead>
              <tr>
                <th style="width:40px">№</th>
                <th style="width:100px; text-align: center;">Тип</th>
                <th style="width:100px;">Действия</th>
              </tr>
            </thead>
            <tbody class="entity-table-tbody"> 
              <?php if (empty($entity)): ?>
                <tr class="no-results">
                  <td colspan="4" align="center"><?php echo $lang['ENTITY_NONE']; ?></td>
                </tr>
                  <?php else: ?>
                    <?php foreach ($entity as $row): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                      <td><?php echo $row['id']; ?></td>
                      <td class="type">                                  
                        <span class='activity-product-true'> <?php echo $row['nameEntity'] ?></span>   
                      </td>
                      <td class="actions">
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="edit-row" 
                              data-id="<?php echo $row['id'] ?>" 
                              data-type="<?php echo $row['type']; ?>">
                            <a class="tool-tip-bottom" href="javascript:void(0);" 
                               title="<?php echo $lang['EDIT']; ?>"></a>
                          </li>
                          <li class="visible tool-tip-bottom  <?php echo ($row['invisible']) ? 'active' : '' ?>" 
                              data-id="<?php echo $row['id'] ?>" 
                              title="<?php echo ($row['invisible']) ? $lang['ACT_V_ENTITY'] : $lang['ACT_UNV_ENTITY']; ?>">
                            <a href="javascript:void(0);"></a>
                          </li>
                          <li class="delete-row" 
                              data-id="<?php echo $row['id'] ?>">
                            <a class="tool-tip-bottom" href="javascript:void(0);"  
                               title="<?php echo $lang['DELETE']; ?>"></a>
                          </li>
                        </ul>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="clear"></div>
    
      <?php echo $pagination ?>  <!-- Вывод навигации -->
      <div class="clear"></div>
    </div>
  </div>