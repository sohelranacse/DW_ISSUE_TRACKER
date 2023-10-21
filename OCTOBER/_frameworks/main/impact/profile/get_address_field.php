<form id="frm_profile_edit_address" name="frm_profile_edit_address" method="POST" onsubmit="return Profile.submit_frm_profile_edit_address(event, this)">
    <input type="hidden" name="cmd" value="update_address" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv">
        <h3><i class="fa fa-map-marker"></i> <?php echo $current_address; ?>:</h3>

        <div class="form-group">
            <label><?php echo $street; ?>: </label>
            <input type="text" name="current_street" id="current_street" placeholder="1064/1, East Shewrapara" class="w-full" value="<?php echo $g_user['current_street'] ?>">
        </div>
        <div class="form-group-inline">
            <label><?php echo $country; ?>: </label>
            <select class="combo w-165" id="country_id_current" name="country_id_current" onchange="Profile.get_state(this, 'current')" required>
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
        <div class="form-group-inline">
            <label><?php echo $state; ?>: </label>
            <select class="combo w-165" id="state_id_current" name="state_id_current" onchange="Profile.get_city(this, 'current')" required>
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
        <div class="form-group-inline">
            <label><?php echo $city; ?>: </label>
            <select class="combo w-165" id="city_id_current" name="city_id_current" required>
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



        <h3 style="margin-top: 20px"><i class="fa fa-home"></i> <?php echo $permanent_address; ?>:</h3>

        <div class="form-group">
            <label><?php echo $street; ?>: </label>
            <input type="text" name="permanent_street" id="permanent_street" placeholder="1064/1, East Shewrapara" class="w-full" value="<?php echo $g_user['permanent_street'] ?>">
        </div>

        <div class="form-group-inline">
            <label><?php echo $country; ?>: </label>
            <select class="combo w-165" id="country_id_permanent" name="country_id_permanent" onchange="Profile.get_state(this, 'permanent')" required>
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
        <div class="form-group-inline">
            <label><?php echo $state; ?>: </label>
            <select class="combo w-165" id="state_id_permanent" name="state_id_permanent" onchange="Profile.get_city(this, 'permanent')" required>
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
        <div class="form-group-inline">
            <label><?php echo $city; ?>: </label>
            <select class="combo w-165" id="city_id_permanent" name="city_id_permanent" required>
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
</form>