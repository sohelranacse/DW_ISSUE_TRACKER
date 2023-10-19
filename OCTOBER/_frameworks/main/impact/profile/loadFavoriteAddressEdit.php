<form id="frm_profile_edit_favorite_unfavorite_address" name="frm_profile_edit_favorite_unfavorite_address" method="POST" onsubmit="return Profile.submit_fevorite_unfevorite_region(event, this)">
    <input type="hidden" name="cmd" value="update_fevorite_unfevorite_region" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv">
        <h3><i class="fa fa-thumbs-up"></i> Fevorite Address:</h3>

        <div class="form-group-inline">
            <label>Country: </label>
            <select class="combo w-165" id="country_id_favorite" name="favorite_country_id" onchange="Profile.get_state(this, 'favorite')" required>
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
        <div class="form-group-inline">
            <label>State: </label>
            <select class="combo w-165" id="state_id_favorite" name="favorite_state_id" onchange="Profile.get_city(this, 'favorite')" required>
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
        <div class="form-group-inline">
            <label>City: </label>
            <select class="combo w-165" id="city_id_favorite" name="favorite_city_id" required>
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



        <h3 style="margin-top: 20px"><i class="fa fa-thumbs-down"></i> Unfevorite Address:</h3>

        <div class="form-group-inline">
            <label>Country: </label>
            <select class="combo w-165" id="country_id_unfavorite" name="unfavorite_country_id" onchange="Profile.get_state(this, 'unfavorite')" required>
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
        <div class="form-group-inline">
            <label>State: </label>
            <select class="combo w-165" id="state_id_unfavorite" name="unfavorite_state_id" onchange="Profile.get_city(this, 'unfavorite')" required>
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
        <div class="form-group-inline">
            <label>City: </label>
            <select class="combo w-165" id="city_id_unfavorite" name="unfavorite_city_id" required>
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
</form>