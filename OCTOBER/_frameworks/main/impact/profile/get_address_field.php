<form id="frm_profile_edit_address" name="frm_profile_edit_address" method="POST" action="<?php echo $action; ?>">                
    <input class="ajax" type="hidden" name="ajax" value="1" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv">
        <h3><i class="fa fa-map-marker"></i> Current Address:</h3>

        <div class="form-group-inline">
            <label>Country: </label>
            <select class="combo" style="width: 160px">
                <option class="">Select</option>
                <?php
                if(sizeof($countryList))
                    foreach($countryList AS $value) {
                        echo '<option class="'.$value['country_id'].'">'.$value['country_title'].'</option>';
                    }
                ?>
            </select>
        </div>
        <div class="form-group-inline">
            <label>City: </label>
            <select class="combo" style="width: 160px">
            </select>
        </div>
        <div class="form-group-inline">
            <label>State: </label>
            <select class="combo" style="width: 160px">
                <option class="l1">Option 1</option>
                <option class="l2">Suboption 1</option>
                <option class="l3">Suboption 2</option>
                <option class="l2">Suboption 3</option>
                <option class="l1">Option 2</option>
            </select>
        </div>

        <h3 style="margin-top: 20px"><i class="fa fa-home"></i> Parmanent Address:</h3>

        <div class="form-group-inline">
            <label>Country: </label>
            <select class="combo" style="width: 160px">
                <option class="l1">Option 1</option>
                <option class="l2">Suboption 1</option>
                <option class="l3">Suboption 2</option>
                <option class="l2">Suboption 3</option>
                <option class="l1">Option 2</option>
            </select>
        </div>
        <div class="form-group-inline">
            <label>City: </label>
            <select class="combo" style="width: 160px">
                <option class="l1">Option 1</option>
                <option class="l2">Suboption 1</option>
                <option class="l3">Suboption 2</option>
                <option class="l2">Suboption 3</option>
                <option class="l1">Option 2</option>
            </select>
        </div>
        <div class="form-group-inline">
            <label>State: </label>
            <select class="combo" style="width: 160px">
                <option class="l1">Option 1</option>
                <option class="l2">Suboption 1</option>
                <option class="l3">Suboption 2</option>
                <option class="l2">Suboption 3</option>
                <option class="l1">Option 2</option>
            </select>
        </div>

    </div>
</form>