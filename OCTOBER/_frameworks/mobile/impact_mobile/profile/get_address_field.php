<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_profile_edit_address" name="frm_profile_edit_address" method="POST" onsubmit="return clProfile.submit_frm_profile_edit_address(event, this)">
    <input type="hidden" name="cmd" value="update_address" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm">
        <h3><i class="fa fa-map-marker"></i> <?php echo $current_address; ?>:</h3>

        <div class="bl">
            <label><?php echo $street; ?>: </label>
            <div class="field">
            <input type="text" name="current_street" id="current_street" placeholder="1064/1, Bashundhara" class="w-full" value="<?php echo $g_user['current_street'] ?>">
            </div>
        </div>
        <div class="bl">
            <label><?php echo $country; ?>: </label>
            <div class="field">
                <div class="field">
                <select class="combo" id="country_id_current" name="country_id_current" onchange="clProfile.get_state(this, 'current')">
                    <option value="">Select</option>
                    <?php
                    if(sizeof($countryList))
                        foreach($countryList AS $value) {
                            $selected = '';
                            if($value['country_id'] == $g_user['current_country_id'])
                                $selected = ' selected';
                            echo '<option value="'.$value['country_id'].'"'.$selected.'>'.$value['country_title'].'</option>';
                        }
                    ?>
                </select>
                </div>
            </div>
        </div>
        <div class="bl">
            <label><?php echo $state; ?>: </label>
            <div class="field">
            <select class="combo" id="state_id_current" name="state_id_current" onchange="clProfile.get_city(this, 'current')">
                <option value="">Select</option>
                <?php
                if(sizeof($stateList))
                    foreach($stateList AS $value) {
                        $selected = '';
                        if($value['state_id'] == $g_user['current_state_id'])
                            $selected = ' selected';
                        echo '<option value="'.$value['state_id'].'"'.$selected.'>'.$value['state_title'].'</option>';
                    }
                ?>
            </select>
            </div>
        </div>
        <div class="bl">
            <label><?php echo $city; ?>: </label>
            <div class="field">
            <select class="combo" id="city_id_current" name="city_id_current">
                <option value="">Select</option>
                <?php
                if(sizeof($cityList))
                    foreach($cityList AS $value) {
                        $selected = '';
                        if($value['city_id'] == $g_user['current_city_id'])
                            $selected = ' selected';
                        echo '<option value="'.$value['city_id'].'"'.$selected.'>'.$value['city_title'].'</option>';
                    }
                ?>
            </select>
            </div>
        </div>



        <h3 style="margin-top: 20px"><i class="fa fa-home"></i> <?php echo $permanent_address; ?>:</h3>

        <div class="bl">
            <label><?php echo $street; ?>: </label>
            <div class="field">
                <input type="text" name="permanent_street" id="permanent_street" placeholder="1064/1, Bashundhara" class="w-full" value="<?php echo $g_user['permanent_street'] ?>">
            </div>
        </div>

        <div class="bl">
            <label><?php echo $country; ?>: </label>
            <div class="field">
            <select class="combo" id="country_id_permanent" name="country_id_permanent" onchange="clProfile.get_state(this, 'permanent')">
                <option value="">Select</option>
                <?php
                if(sizeof($countryList))
                    foreach($countryList AS $value) {
                        $selected = '';
                        if($value['country_id'] == $g_user['permanent_country_id'])
                            $selected = ' selected';
                        echo '<option value="'.$value['country_id'].'"'.$selected.'>'.$value['country_title'].'</option>';
                    }
                ?>
            </select>
            </div>
        </div>
        <div class="bl">
            <label><?php echo $state; ?>: </label>
            <div class="field">
            <select class="combo" id="state_id_permanent" name="state_id_permanent" onchange="clProfile.get_city(this, 'permanent')">
                <option value="">Select</option>
                <?php
                if(sizeof($permanent_stateList))
                    foreach($permanent_stateList AS $value) {
                        $selected = '';
                        if($value['state_id'] == $g_user['permanent_state_id'])
                            $selected = ' selected';
                        echo '<option value="'.$value['state_id'].'"'.$selected.'>'.$value['state_title'].'</option>';
                    }
                ?>
            </select>
            </div>
        </div>
        <div class="bl">
            <label><?php echo $city; ?>: </label>
            <div class="field">
            <select class="combo" id="city_id_permanent" name="city_id_permanent">
                <option value="">Select</option>
                <?php
                if(sizeof($permanent_cityList))
                    foreach($permanent_cityList AS $value) {
                        $selected = '';
                        if($value['city_id'] == $g_user['permanent_city_id'])
                            $selected = ' selected';
                        echo '<option value="'.$value['city_id'].'"'.$selected.'>'.$value['city_title'].'</option>';
                    }
                ?>
            </select>
            </div>
        </div>

    </div>
    <div class="frm_btn frm_edit">
        <div class="double">
            <span class="l">
                <button type="button" onclick="return clProfile.loadTabs('#tabs-1');" id="pp_profile_looking_cancel" class="btn small white_frame frm_editor_cancel"><?php echo $cancel; ?></button>
            </span>
            <span class="r">
                <button type="submit" id="profile_field_save" class="btn small pink frm_editor_save"><?php echo $save; ?></button>
            </span>
        </div>
    </div>
</form>
</div>