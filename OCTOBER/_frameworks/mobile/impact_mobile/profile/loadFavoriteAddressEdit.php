<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_profile_edit_favorite_unfavorite_address" name="frm_profile_edit_favorite_unfavorite_address" method="POST" onsubmit="return clProfile.submit_fevorite_unfevorite_region(event, this)">
    <input type="hidden" name="cmd" value="update_fevorite_unfevorite_region" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm">
        <h3><i class="fa fa-thumbs-up"></i> <?php echo $favored_location; ?>:</h3>

        <div class="bl">
            <label><?php echo $country; ?>: </label>
            <div class="field">
            <select class="combo" id="country_id_favorite" name="favorite_country_id" onchange="clProfile.get_state(this, 'favorite')">
                <option value="">Select</option>
                <?php
                if(sizeof($countryList))
                    foreach($countryList AS $value) {
                        $selected = '';
                        if($value['country_id'] == $g_user['favorite_country_id'])
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
            <select class="combo" id="state_id_favorite" name="favorite_state_id" onchange="clProfile.get_city(this, 'favorite')">
                <option value="">Select</option>
                <?php
                if(sizeof($stateList))
                    foreach($stateList AS $value) {
                        $selected = '';
                        if($value['state_id'] == $g_user['favorite_state_id'])
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
            <select class="combo" id="city_id_favorite" name="favorite_city_id">
                <option value="">Select</option>
                <?php
                if(sizeof($cityList))
                    foreach($cityList AS $value) {
                        $selected = '';
                        if($value['city_id'] == $g_user['favorite_city_id'])
                            $selected = ' selected';
                        echo '<option value="'.$value['city_id'].'"'.$selected.'>'.$value['city_title'].'</option>';
                    }
                ?>
            </select>
            </div>
        </div>



        <h3 style="margin-top: 20px"><i class="fa fa-thumbs-down"></i> <?php echo $unfavored_location; ?>:</h3>

        <div class="bl">
            <label><?php echo $country; ?>: </label>
            <div class="field">
            <select class="combo" id="country_id_unfavorite" name="unfavorite_country_id" onchange="clProfile.get_state(this, 'unfavorite')">
                <option value="">Select</option>
                <?php
                if(sizeof($countryList))
                    foreach($countryList AS $value) {
                        $selected = '';
                        if($value['country_id'] == $g_user['unfavorite_country_id'])
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
            <select class="combo" id="state_id_unfavorite" name="unfavorite_state_id" onchange="clProfile.get_city(this, 'unfavorite')">
                <option value="">Select</option>
                <?php
                if(sizeof($permanent_stateList))
                    foreach($permanent_stateList AS $value) {
                        $selected = '';
                        if($value['state_id'] == $g_user['unfavorite_state_id'])
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
            <select class="combo" id="city_id_unfavorite" name="unfavorite_city_id">
                <option value="">Select</option>
                <?php
                if(sizeof($permanent_cityList))
                    foreach($permanent_cityList AS $value) {
                        $selected = '';
                        if($value['city_id'] == $g_user['unfavorite_city_id'])
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
                <button id="pp_profile_looking_cancel" class="btn small white_frame frm_editor_cancel"><?php echo $cancel; ?></button>
            </span>
            <span class="r">
                <button type="submit" id="profile_field_save" class="btn small pink frm_editor_save"><?php echo $save; ?></button>
            </span>
        </div>
    </div>
</form>
</div>