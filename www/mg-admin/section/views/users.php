<div class="section-user">
    <div class="widget-table-wrapper">
        <div class="widget-table-title">
            <h4 class="user-table-icon"><?php echo $lang['TITLE_USERS'];?></h4>
            <p class="produc-count"><?php echo $lang['ALL_COUNT_USERS'];?>: <strong><?php echo $usersCount ?></strong> <?php echo $lang['UNIT'];?></p>
        </div>
        <!-- Верстка модального окна -->
            <div class="b-modal hidden-form add-category-popup" id="add-user-wrapper">
                <div class="product-table-wrapper users-table-wrapper">
                    <div class="widget-table-title">
                        <h4 class="user-table-icon"><?php echo $lang['TITLE_USER_NEW'];?></h4>
                        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_WITHOUT_SAVE'];?>"></div>
                    </div>
                    <div class="widget-table-body">
                        <div class="add-user-form-wrapper">
                            <div class="add-user-form">
                                <label><span class="custom-text"><?php echo $lang['USER_EMAIL'];?>:</span><input type="text" name="email" class="product-name-input meta-data-category tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_EMAIL'];?>"><div class="errorField"><?php echo $lang['ERROR_EMAIL'];?></div></label>
                                <div class="controlEditorPas">
                                    <span class="custom-text"><?php echo $lang['USER_PASS'];?>:</span> <a href="#" class="editPass tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_PAS_EDIT'];?>"><span><?php echo $lang['USER_PASS_EDIT'];?></span></a>
                                </div>
                                <div class="editorPas change-pass-form" style="display:none">
                                    <label><span class="custom-text"><?php echo $lang['USER_PASS'];?>:</span><input type="password" name="pass" class="product-name-input meta-data-category"><div class="errorField"><?php echo $lang['ERROR_PASS'];?></div></label>
                                    <label><span class="custom-text"><?php echo $lang['USER_PASS_CONFIRM'];?>:</span><input type="password" name="passconfirm" class="product-name-input meta-data-category"><div class="errorField"><?php echo $lang['ERROR_CONFIRM_PASS'];?></div></label>
                                </div>
                                <label><span class="custom-text"><?php echo $lang['USER_NAME'];?>:</span><input type="text" name="name" class="product-name-input meta-data-category"></label>
                                <label><span class="custom-text"><?php echo $lang['USER_SNAME'];?>:</span><input type="text" name="sname" class="product-name-input meta-data-category"></label>
                                <label><span class="custom-text"><?php echo $lang['USER_PHONE'];?>:</span><input type="text" name="phone" class="product-name-input meta-data-category"></label>
                                <label><span class="custom-text"><?php echo $lang['USER_ADDRESS'];?>:</span><br/>
                                    <textarea name="address" class="product-meta-field meta-data-category" ></textarea></label>
                                <span class="custom-text"><?php echo $lang['USER_GROUP'];?>:</span>
                                <select name="role" class="last-items-dropdown role custom-dropdown tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_ROLE'];?>">                                   
                                    <option value="2"><?php echo $lang['USER_GROUP_NAME2'];?></option>
                                    <option value="1"><?php echo $lang['USER_GROUP_NAME1'];?></option>
                                </select>
                                <div class="user-status-filter tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_ACTYVITY'];?>" >
                                    <span class="custom-text"><?php echo $lang['USER_STATUS'];?>:</span>
                                    <select class="last-items-dropdown custom-dropdown activity" 	name="activity">
                                        <option value="0"><?php echo $lang['USER_ACTYVITY_FALSE'];?></option>
                                        <option value="1"><?php echo $lang['USER_ACTYVITY_TRUE'];?></option>
                                    </select>
                                </div>
                                <div class="user-ban" >
                                    <span class="custom-text"><?php echo $lang['ACCESS_PERSONAL'];?>:</span>
                                    <select class="last-items-dropdown custom-dropdown blocked tool-tip-bottom user-acces" title="<?php echo $lang['T_TIP_USER_BLOCKED'];?>" name="blocked">
                                        <option value="1"><?php echo $lang['ACCESS_PERSONAL_TRUE'];?></option>
                                        <option value="0"><?php echo $lang['ACCESS_PERSONAL_FALSE'];?></option>
                                    </select>
                                </div>
                                <div class="clear"></div>
                                <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_SAVE'];?>"><span><?php echo $lang['SAVE'];?></span></button>
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <!-- Верстка модального окна -->

        <div class="widget-table-body">
            <div class="widget-table-action">
                <a href="#" class="add-new-button tool-tip-bottom" title="<?php echo $lang['T_TIP_USER_ADD'];?>" ><span><?php echo $lang['USER_ADD'];?></span></a>
                <div class="filter">
                    <span class="last-items"><?php echo $lang['SHOW_USER_COUNT'];?></span>
                    <select class="last-items-dropdown countPrintRowsUser">
                        <?php
                        foreach(array(5, 10, 15, 20, 25, 30) as $value) {
                            $selected = '';
                            if($value == $countPrintRowsUser) {
                                $selected = 'selected="selected"';
                            }
                            echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="clear"></div>
            </div>
            <div class="main-settings-container">
                <table class="widget-table user-table">
                    <thead>
                        <tr>
                            <th><?php echo $lang['USER_EMAIL'];?></th>
                            <th><?php echo $lang['USER_STATUS'];?></th>
                            <th><?php echo $lang['USER_GROUP'];?></th>
                            <th><?php echo $lang['USER_DATE_ADD'];?></th>
                            <th><?php echo $lang['ACCESS_PERSONAL'];?></th>
                            <th class="actions"><?php echo $lang['ACTIONS'];?></th>
                        </tr>
                    </thead>
                    <tbody class="user-tbody">
                        <?php foreach($users as $data) { ?>
                        <tr id="<?php echo $data['id'] ?>">

                            <td class="email"><?php echo $data['email']?></td>
                            <td class="activity">
                                    <?php if($data['activity']) { ?>
                                <span class="activity-product-true"><?php echo $lang['USER_ACTYVITY_TRUE'];?></span>
                                        <?php } else { ?>
                                <span class="activity-product-false"><?php echo $lang['USER_ACTYVITY_FALSE'];?></span>
                                        <?php }?>
                            </td>
                            <td class="role"><?php
                                    echo $groupName[$data['role']];
                                    ?></td>
                            <td class="date_add"><?php echo date('d.m.Y H:i', strtotime($data['date_add'])) ?></td>
                            <td class="blocked">
                                    <?php if($data['blocked']) { ?>
                                <span class="activity-product-false"><?php echo $accessStatus[1] ?></span>
                                        <?php } else { ?>
                                <span class="activity-product-true"><?php echo $accessStatus[0] ?></span>
                                        <?php }?>
                            </td>
                            <td class="actions">
                                <ul class="action-list">
                                    <li class="edit-row" id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);" title="<?php echo $lang['EDIT'];?>"></a></li>
                                    <li class="delete-order " id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);" title="<?php echo $lang['DELETE'];?>"></a></li>
                                </ul>
                            </td>
                        </tr>
                            <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php echo $pagination ?>
            <div class="clear"></div>
        </div>
    </div>
</div>