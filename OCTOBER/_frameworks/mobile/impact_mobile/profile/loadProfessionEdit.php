<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_update_profession" name="frm_update_profession" method="POST" onsubmit="return clProfile.submit_profession(event, this)">
    <input type="hidden" name="cmd" value="update_profession" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm">
        <div id="paginate">

            <?php
            $n = sizeof($professionList);

            if($n) {
                foreach($professionList as $key => $value) { ?>
                    <div class="add_more_div" id="more_profession<?php echo $key+1; ?>">
                        <div class="close_div">
                            <button type="button" onclick="clProfile.close_multiple_profession_div(<?php echo $key+1; ?>)"><i class="fa fa-times"></i></button>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>: <span class="r">*</span></label>
                            <div class="field">
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
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-level-up"></i> <?php echo $position; ?>: <span class="r">*</span></label>
                            <div class="field">
                            <input type="text" id="position" name="position[]" placeholder="General Manager" value="<?php echo $value['position']; ?>" required />
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-industry"></i> <?php echo $company; ?>: <span class="r">*</span></label>
                            <div class="field">
                            <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." value="<?php echo $value['company']; ?>" required />
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-map-marker"></i> Address: </label>
                            <div class="field">
                            <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" value="<?php echo $value['address']; ?>" />
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="add_more_div" id="more_profession<?php echo $n+1; ?>"></div>
            <?php } else { ?>
                <div class="add_more_div" id="more_profession1">
                    <div class="close_div">
                        <button type="button" onclick="clProfile.close_multiple_profession_div(1)"><i class="fa fa-times"></i></button>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>: <span class="r">*</span></label>
                        <div class="field">
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
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-level-up"></i> <?php echo $position; ?>: <span class="r">*</span></label>
                        <div class="field">
                        <input type="text" id="position" name="position[]" placeholder="General Manager" required />
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-industry"></i> <?php echo $company; ?>: <span class="r">*</span></label>
                        <div class="field">
                        <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." required />
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-map-marker"></i> Address: </label>
                        <div class="field">
                        <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                        </div>
                    </div>

                </div>

                <div class="add_more_div" id="more_profession2"></div>
            <?php } ?>

        </div>
        <script type="text/javascript">
            var myField = `
                <div class="bl">
                    <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>: <span class="r">*</span></label>
                    <div class="field">
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
                </div>
                <div class="bl">
                    <label><i class="fa fa-level-up"></i> <?php echo $position; ?>: <span class="r">*</span></label>
                    <div class="field">
                    <input type="text" id="position" name="position[]" placeholder="General Manager" required />
                        </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-industry"></i> <?php echo $company; ?>: <span class="r">*</span></label>
                    <div class="field">
                    <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." required />
                        </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-map-marker"></i> Address: </label>
                    <div class="field">
                    <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                        </div>
                </div>
            `;
        </script>


        <div style="margin-bottom: 30px;">
            <button class="btn small turquoise" style="padding: 0 20px;" value="<?php if($n) echo $n+1; else echo 2; ?>" id="ind" type="button" onclick="return clProfile.add_more_profession_field(this.value, myField)">Add more <i class="fa fa-plus"></i></button>
        </div>

    </div>
    <div class="frm_btn frm_edit">
        <div class="double">
            <span class="l">
                <button type="button" id="pp_profile_looking_cancel" class="btn small white_frame frm_editor_cancel"><?php echo $cancel; ?></button>
            </span>
            <span class="r">
                <button type="submit" id="profile_field_save" class="btn small pink frm_editor_save"><?php echo $save; ?></button>
            </span>
        </div>
    </div>
</form>
</div>