<form id="frm_update_profession" name="frm_update_profession" method="POST" onsubmit="return Profile.submit_profession(event, this)">
    <input type="hidden" name="cmd" value="update_profession" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv">
        <div id="paginate">

            <?php
            $n = sizeof($professionList);

            if($n) {
                foreach($professionList as $key => $value) { ?>
                    <div class="add_more_div" id="more_profession<?php echo $key+1; ?>">
                        <div class="close_div">
                            <button type="button" onclick="Profile.close_multiple_profession_div(<?php echo $key+1; ?>)"><i class="fa fa-times"></i></button>
                        </div>

                        <div class="form-group-half">
                            <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>: <span class="r">*</span></label>
                            <select class="combo" name="profession_type[]">
                                <option value="">Select</option>
                                <?php
                                if(sizeof($professionType))
                                    foreach($professionType AS $row) {
                                        $selected = '';
                                        if($row['id'] == $value['profession_type'])
                                            $selected = ' selected';
                                        echo '<option value="'.$row['id'].'"'.$selected.'>'.$row['title'].'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-level-up"></i> <?php echo $position; ?>: <span class="r">*</span></label>
                            <input type="text" id="position" name="position[]" placeholder="General Manager" value="<?php echo $value['position']; ?>" required />
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-industry"></i> <?php echo $company; ?>: <span class="r">*</span></label>
                            <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." value="<?php echo $value['company']; ?>" required />
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                            <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" value="<?php echo $value['address']; ?>" />
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-calendar"></i> <?php echo $from_date; ?>: </label>
                            <input type="date" name="from_date[]" value="<?php echo $value['from_date']; ?>" />
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-calendar"></i> <?php echo $to_date; ?>: </label>
                            <input type="date" name="to_date[]" value="<?php echo $value['to_date']; ?>" />
                        </div>
                    </div>
                <?php } ?>
                <div class="add_more_div" id="more_profession<?php echo $n+1; ?>"></div>
            <?php } else { ?>
                <div class="add_more_div" id="more_profession1">
                    <div class="close_div">
                        <button type="button" onclick="Profile.close_multiple_profession_div(1)"><i class="fa fa-times"></i></button>
                    </div>

                    <div class="form-group-half">
                        <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>: <span class="r">*</span></label>
                        <select class="combo" name="profession_type[]">
                            <option value="">Select</option>
                            <?php
                            if(sizeof($professionType))
                                foreach($professionType AS $row) {
                                    echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-level-up"></i> <?php echo $position; ?>: <span class="r">*</span></label>
                        <input type="text" id="position" name="position[]" placeholder="General Manager" required />
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-industry"></i> <?php echo $company; ?>: <span class="r">*</span></label>
                        <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." required />
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                        <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-calendar"></i> <?php echo $from_date; ?>: </label>
                        <input type="date" name="from_date[]" />
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-calendar"></i> <?php echo $to_date; ?>: </label>
                        <input type="date" name="to_date[]" />
                    </div>

                </div>

                <div class="add_more_div" id="more_profession2"></div>
            <?php } ?>

        </div>
        <script type="text/javascript">
            var myField = `
                <div class="form-group-half">
                    <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>: <span class="r">*</span></label>
                    <select class="combo" name="profession_type[]">
                        <option value="">Select</option>
                        <?php
                        if(sizeof($professionType))
                            foreach($professionType AS $row) {
                                echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-level-up"></i> <?php echo $position; ?>: <span class="r">*</span></label>
                    <input type="text" id="position" name="position[]" placeholder="General Manager" required />
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-industry"></i> <?php echo $company; ?>: <span class="r">*</span></label>
                    <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." required />
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                    <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-calendar"></i> <?php echo $from_date; ?>: </label>
                    <input type="date" name="from_date[]" />
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-calendar"></i> <?php echo $to_date; ?>: </label>
                    <input type="date" name="to_date[]" />
                </div>
            `;
        </script>


        <div style="padding-left: 6px;padding-top: 10px;">
            <button class="btn small pink" style="padding: 0 20px;" value="<?php if($n) echo $n+1; else echo 2; ?>" id="ind" type="button" onclick="return Profile.add_more_profession_field(this.value, myField)"> <i class="fa fa-plus"></i></button>
        </div>

    </div>
</form>