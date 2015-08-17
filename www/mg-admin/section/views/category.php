<div class="section-category">
  <div class="widget-table-wrapper">
    <div class="widget-table-title">
      <h4 class="category-table-icon"><?php echo $lang['TITLE_CATEGORIES']; ?></h4>
    </div>

    <!-- Верстка модального окна -->

    <div class="b-modal hidden-form add-category-popup" id="add-category-wrapper">
      <div class="product-table-wrapper">
        <div class="widget-table-title">
          <h4 class="category-table-icon" id="modalTitle"><?php echo $lang['NEW_CATEGORY']; ?></h4>
          <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL']; ?>"></div>
        </div>
        <div class="widget-table-body">
          <div class="add-product-form-wrapper">
            <div class="add-category-form">
              <label><span class="custom-text"><?php echo $lang['CAT_NAME']; ?>:</span><input type="text" name="title" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_CAT_NAME']; ?>" ><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL']; ?></div></label>
              <label><span class="custom-text"><?php echo $lang['CAT_URL']; ?>:</span><input type="text" name="url" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_CAT_URL']; ?>"><div class="errorField"><?php echo $lang['ERROR_EMPTY']; ?></div></label>
              <div class="category-filter">
                <span class="custom-text"><?php echo $lang['CAT_PARENT']; ?>:</span>
                <select class="last-items-dropdown custom-dropdown tool-tip-right" title="<?php echo $lang['T_TIP_CAT_PARENT']; ?>" name="parent">
                  <option selected value='0'><?php echo $lang['ALL']; ?></option>
                  <?php echo $select_categories ?>
                </select>
              </div>
              <label><span class="custom-text"><?php echo $lang['CAT_INVISIBLE']; ?>:</span><input type="checkbox" name="invisible" class="tool-tip-bottom" title="<?php echo $lang['CAT_INVISIBLE']; ?>"></label>

              <div class="category-desc-wrapper">
                <span class="custom-text" style="margin-bottom: 10px;"><?php echo $lang['CATEGORY_CONTENT']; ?>:</span>
                <div style="background:#FFF">
                  <textarea class="product-desc-field" name="html_content"></textarea>
                </div>
              </div>
              <div class="clear"></div>
              <span class="seo-title"><?php echo $lang['SEO_BLOCK'] ?></span>
              <div class="seo-wrapper">
                <label><span class="custom-text"><?php echo $lang['META_TITLE']; ?>:</span><input type="text" name="meta_title" class="product-name-input meta-data-category tool-tip-bottom" title="<?php echo $lang['T_TIP_META_TITLE']; ?>"></label>
                <label><span class="custom-text"><?php echo $lang['META_KEYWORDS']; ?>:</span><input type="text" name="meta_keywords" class="product-name-input meta-data-category tool-tip-bottom" title="<?php echo $lang['T_TIP_META_KEYWORDS']; ?>"></label>
                <label>
                  <ul class="meta-list">
                    <li><span class="custom-text"><?php echo $lang['META_DESC']; ?>:</span></li>
                    <li><span class="symbol-left"><?php echo $lang['LENGTH_META_DESC']; ?>: <span class="symbol-count"></span></li>
                  </ul>
                  <textarea class="product-meta-field meta-data-category tool-tip-bottom" name="meta_desc" title="<?php echo $lang['T_TIP_META_DESC']; ?>"></textarea>
                </label>
              </div>
              <div class="clear"></div>
              <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE_CAT']; ?>"><span><?php echo $lang['SAVE']; ?></span></button>
              <div class="clear"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Верстка модального окна -->

    <div class="widget-table-body">
      <div class="widget-table-action">
        <a href="javascript:void(0);" class="add-new-button tool-tip-bottom" title="<?php echo $lang['T_TIP_ADD_CATEGORY']; ?>"><span><?php echo $lang['ADD_CATEGORY']; ?></span></a>
        <a href="javascript:void(0);" class="custom-btn refresh-visible-cat tool-tip-bottom" title="<?php echo $lang['ALL_CAT_VISIBLE']; ?>"><span><?php echo $lang['ALL_CAT_VISIBLE']; ?></span></a>
      </div>

      <div class="category-tree-field">
        <ul class="edit-category-list" style="display:none">
          <li class="cat-li"><span class="cat-title">Название категории</span> <span class="cat-id">[id=101010]</span></li>
          <li><a href="javascript:void(0);" class="edit-sub-cat"><?php echo $lang['EDIT']; ?></a></li>
          <li><a href="javascript:void(0);" class="add-sub-cat"><?php echo $lang['ADD_SUBCATEGORY']; ?></a></li>
          <li><a href="javascript:void(0);" class="prod-sub-cat"><?php echo $lang['SHOW_PRODUCT']; ?></a></li>
          <li><a href="javascript:void(0);" class="delete-sub-cat"><?php echo $lang['DELETE']; ?></a></li>         
          <li><a href="javascript:void(0);" class="cancel-sub-cat"><?php echo $lang['CANCEL']; ?></a></li>
        </ul>
        <?php if (!empty($categories)): ?>
          <ul class="category-tree">
            <?php echo $categories ?>
          </ul>
        <?php else: ?>	
          <?php echo '<div class="empty-cat">'.$lang["CAT_NONE"].'</div>' ?>
        <?php endif; ?>
        <div class="clear"></div>

      </div>
    </div>
  </div>
</div>