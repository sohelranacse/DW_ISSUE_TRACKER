<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_update_relatives" name="frm_update_relatives" method="POST" onsubmit="return clProfile.submit_relatives(event, this)">
    <input type="hidden" name="cmd" value="update_relatives" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm">
        <div id="paginate">

            <?php
            $n = sizeof($relativeList);

            if($n) {
                foreach($relativeList as $key => $value) { ?>
                    <div class="add_more_div" id="more_relatives<?php echo $key+1; ?>">
                        <div class="close_div">
                            <button type="button" onclick="clProfile.close_multiple_relatives_div(<?php echo $key+1; ?>)"><i class="fa fa-times"></i></button>
                        </div>


                        <div class="bl">
                            <label><i class="fa fa-user"></i> <?php echo $name; ?>: <span class="r">*</span></label>
                            <div class="field">
                            <input type="text" id="relative_name" name="relative_name[]" placeholder="Type name" value="<?php echo $value['relative_name']; ?>" required />
                            </div>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-link"></i> <?php echo $relation; ?>: <span class="r">*</span></label>
                            <div class="field">
                            <input type="text" id="relation" name="relation[]" placeholder="Father" value="<?php echo $value['relation']; ?>" required />
                            </div>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-circle"></i> Marital Status:</label>
                            <div class="field">
                            <select class="combo" name="marital_status[]">
                                <option value="">Select</option>
                                <?php
                                if(sizeof($maritalStatus))
                                    foreach($maritalStatus AS $m_row) {
                                        $selected = '';
                                        if($m_row['id'] == $value['marital_status'])
                                            $selected = ' selected';
                                        echo '<option value="'.$m_row['id'].'"'.$selected.'>'.$m_row['title'].'</option>';
                                    }
                                ?>
                            </select>
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                            <div class="field">
                            <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" value="<?php echo $value['address']; ?>" />
                            </div>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>:</label>
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
                            <label><i class="fa fa-level-up"></i> <?php echo $position; ?>:</label>
                            <div class="field">
                            <input type="text" id="position" name="position[]" placeholder="General Manager" value="<?php echo $value['position']; ?>" />
                            </div>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-industry"></i> <?php echo $company; ?>:</label>
                            <div class="field">
                            <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." value="<?php echo $value['company']; ?>" />
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-graduation-cap"></i> <?php echo $highest_degree; ?>:</label>
                            <div class="field">
                            <input type="text" id="degree_title" name="degree_title[]" placeholder="BSc in Computer Science" value="<?php echo $value['degree_title']; ?>" />
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="add_more_div" id="more_relatives<?php echo $n+1; ?>"></div>
            <?php } else { ?>
                <div class="add_more_div" id="more_relatives1">
                    <div class="close_div">
                        <button type="button" onclick="clProfile.close_multiple_relatives_div(1)"><i class="fa fa-times"></i></button>
                    </div>


                    <div class="bl">
                        <label><i class="fa fa-user"></i> <?php echo $name; ?>: <span class="r">*</span></label>
                        <div class="field">
                        <input type="text" id="relative_name" name="relative_name[]" placeholder="Type Name" required />
                        </div>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-link"></i> <?php echo $relation; ?>: <span class="r">*</span></label>
                        <div class="field">
                        <input type="text" id="relation" name="relation[]" placeholder="Father" required />
                        </div>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-circle"></i> Marital Status:</label>
                        <div class="field">
                        <select class="combo" name="marital_status[]">
                            <option value="">Select</option>
                            <?php
                            if(sizeof($maritalStatus))
                                foreach($maritalStatus AS $m_row) {
                                    echo '<option value="'.$m_row['id'].'">'.$m_row['title'].'</option>';
                                }
                            ?>
                        </select>
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                        <div class="field">
                        <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                        </div>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>:</label>
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
                        <label><i class="fa fa-level-up"></i> <?php echo $position; ?>:</label>
                        <div class="field">
                        <input type="text" id="position" name="position[]" placeholder="General Manager" />
                        </div>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-industry"></i> <?php echo $company; ?>:</label>
                        <div class="field">
                        <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." />
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-graduation-cap"></i> <?php echo $highest_degree; ?>:</label>
                        <div class="field">
                        <input type="text" id="degree_title" name="degree_title[]" placeholder="BSc in Computer Science" />
                        </div>
                    </div>
                </div>

                <div class="add_more_div" id="more_relatives2"></div>
            <?php } ?>

        </div>
        <script type="text/javascript">
            var myField = `
                <div class="bl">
                    <label><i class="fa fa-user"></i> <?php echo $name; ?>: <span class="r">*</span></label>
                    <div class="field">
                    <input type="text" id="relative_name" name="relative_name[]" placeholder="Type Name" required />
                    </div>
                </div>

                <div class="bl">
                    <label><i class="fa fa-link"></i> <?php echo $relation; ?>: <span class="r">*</span></label>
                    <div class="field">
                    <input type="text" id="relation" name="relation[]" placeholder="Father" required />
                    </div>
                </div>

                <div class="bl">
                    <label><i class="fa fa-circle"></i> Marital Status:</label>
                    <div class="field">
                    <select class="combo" name="marital_status[]">
                        <option value="">Select</option>
                        <?php
                        if(sizeof($maritalStatus))
                            foreach($maritalStatus AS $m_row) {
                                echo '<option value="'.$m_row['id'].'">'.$m_row['title'].'</option>';
                            }
                        ?>
                    </select>
                    </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                    <div class="field">
                    <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                    </div>
                </div>

                <div class="bl">
                    <label><i class="fa fa-bullhorn"></i> <?php echo $profession_type; ?>:</label>
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
                    <label><i class="fa fa-level-up"></i> <?php echo $position; ?>:</label>
                    <div class="field">
                    <input type="text" id="position" name="position[]" placeholder="General Manager" />
                    </div>
                </div>

                <div class="bl">
                    <label><i class="fa fa-industry"></i> <?php echo $company; ?>:</label>
                    <div class="field">
                    <input type="text" id="company" name="company[]" placeholder="Microsoft Inc." />
                    </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-graduation-cap"></i> <?php echo $highest_degree; ?>:</label>
                    <div class="field">
                    <input type="text" id="degree_title" name="degree_title[]" placeholder="BSc in Computer Science" />
                    </div>
                </div>
            `;
        </script>


        <div style="margin-bottom: 30px;">
            <button class="btn small pink" style="padding: 0 20px;" value="<?php if($n) echo $n+1; else echo 2; ?>" id="ind" type="button" onclick="return clProfile.add_more_relatives_field(this.value, myField)">Add more <i class="fa fa-plus"></i></button>
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