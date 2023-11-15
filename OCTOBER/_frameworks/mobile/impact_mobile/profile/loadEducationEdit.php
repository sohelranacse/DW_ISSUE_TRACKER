<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<style type="text/css">
.hide { display:none; }
</style>
<form id="frm_update_education" name="frm_update_education" method="POST" onsubmit="return clProfile.submit_education(event, this)">
    <input type="hidden" name="cmd" value="update_education" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm">
        <div id="paginate">

            <?php
            $n = sizeof($educationList);

            if($n) {
                foreach($educationList as $key => $value) {
                    $degreeData = DB::all("SELECT degree_id, degree_name FROM user_education_degree WHERE education_level_id = {$value['education_level_id']}");
            ?>
                    <div class="add_more_div" id="more_education<?php echo $key+1; ?>">
                        <div class="close_div">
                            <button type="button" onclick="clProfile.close_multiple_div(<?php echo $key+1; ?>)"><i class="fa fa-times"></i></button>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-bell"></i> <?php echo $level_of_education; ?>: </label>
                            <div class="field">
                            <select class="combo" name="education_level_id[]" onchange="return get_degree(this, this.value)">
                                <option value="">Select</option>
                                <?php
                                    foreach($level_of_edu_list as $Ekey => $row) {
                                        $selected = "";
                                        if($Ekey == $value['education_level_id'])
                                            $selected = "selected";

                                        echo '<option value="' . $Ekey . '" '.$selected.'>' . $row . '</option>';
                                    }
                                ?>
                            </select>
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-graduation-cap"></i> <?php echo $degree_title; ?>: <span class="r">*</span></label>
                            <div class="field">
                            <span id="degree_div">
                                <select class="combo" name="degree_id[]" onchange="return get_other_degree('more_education<?php echo $key+1; ?>', this.value)" required>
                                    <?php
                                    $d = 0;
                                    if(sizeof($degreeData))
                                        foreach($degreeData as $dRow) {
                                            $selected = "";
                                            if($dRow['degree_id'] == $value['degree_id']){
                                                $selected = "selected";
                                                $d++;
                                            }

                                            echo '<option value="' . $dRow['degree_id'] . '" '.$selected.'>' . $dRow['degree_name'] . '</option>';
                                        }
                                    ?>
                                    <option value="0" <?php if(!$d) echo 'selected'; ?>><?php echo l('other'); ?></option>
                                </select>
                            </span>
                            <span id="other_degree_div" class="<?php if(!$value['degree_title']) echo 'hide'; ?>">
                                <input type="text" name="degree_title[]" value="<?php echo $value['degree_title']; ?>" />
                            </span>
                            </div>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-book"></i> <?php echo $subject_title; ?>: <span class="r">*</span></label>
                            <div class="field">
                            <input type="text" name="subject_title[]" value="<?php echo $value['subject_title']; ?>" required />
                            </div>
                        </div>

                        <div class="bl">
                            <label><i class="fa fa-university"></i> <?php echo $institute_name; ?>: <span class="r">*</span></label>
                            <div class="field">
                            <input type="text" id="school_name" name="school_name[]" placeholder="Dhaka University" value="<?php echo $value['school_name']; ?>" required />
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                            <div class="field">
                            <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" value="<?php echo $value['address']; ?>" />
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-calculator"></i> <?php echo $results; ?>: </label>
                            <div class="field">
                            <input type="text" name="results[]" value="<?php echo $value['results']; ?>" placeholder="3.59" />
                            </div>
                        </div>
                        <div class="bl">
                            <label><i class="fa fa-calendar"></i> <?php echo l('passing_year') ?>: <span class="r">*</span></label>
                            <div class="field">
                            <select class="combo" name="passing_year[]">
                                <option value="">Select</option>
                                <?php
                                    $currentYear = date("Y");
                                    for ($year = 1980; $year <= $currentYear; $year++) {
                                        $selected = '';
                                        if($year == $value['passing_year'])
                                            $selected = ' selected';

                                        echo '<option value="' . $year . '"'.$selected.'>' . $year . '</option>';
                                    }
                                ?>
                            </select>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="add_more_div" id="more_education<?php echo $n+1; ?>"></div>
            <?php } else { ?>
                <div class="add_more_div" id="more_education1">
                    <div class="close_div">
                        <button type="button" onclick="clProfile.close_multiple_div(1)"><i class="fa fa-times"></i></button>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-bell"></i> <?php echo $level_of_education; ?>: </label>
                        <div class="field">
                        <select class="combo" name="education_level_id[]" onchange="return get_degree(this, this.value)">
                            <option value="">Select</option>
                            <?php
                                foreach($level_of_edu_list as $key => $row) {
                                    echo '<option value="' . $key . '">' . $row . '</option>';
                                }
                            ?>
                        </select>
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-graduation-cap"></i> <?php echo $degree_title; ?>: <span class="r">*</span></label>
                        <div class="field">
                        <span id="degree_div">
                            <select class="combo" name="degree_id[]" required>
                                <option value="">Select</option>
                            </select>
                        </span>
                        <span id="other_degree_div" class="hide">
                            <input type="text" name="degree_title[]" />
                        </span>
                        </div>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-book"></i> <?php echo $subject_title; ?>: <span class="r">*</span></label>
                        <div class="field">
                        <input type="text" name="subject_title[]" placeholder="Electrical Engineering" required />
                        </div>
                    </div>

                    <div class="bl">
                        <label><i class="fa fa-university"></i> <?php echo $institute_name; ?>: <span class="r">*</span></label>
                        <div class="field">
                        <input type="text" id="school_name" name="school_name[]" placeholder="Dhaka University" required />
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                        <div class="field">
                        <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-calculator"></i> <?php echo $results; ?>: </label>
                        <div class="field">
                        <input type="text" name="results[]" placeholder="3.59" />
                        </div>
                    </div>
                    <div class="bl">
                        <label><i class="fa fa-calendar"></i> <?php echo l('passing_year') ?>: <span class="r">*</span></label>
                        <div class="field">
                        <select class="combo" name="passing_year[]">
                            <option value="">Select</option>
                            <?php
                                $currentYear = date("Y");
                                for ($year = 1980; $year <= $currentYear; $year++) {
                                    echo '<option value="' . $year . '">' . $year . '</option>';
                                }
                            ?>
                        </select>
                        </div>
                    </div>
                </div>

                <div class="add_more_div" id="more_education2"></div>
            <?php } ?>

        </div>

        <script type="text/javascript">
            const btnLoader = `
            <div class="css_loader btn_action_loader"><div class="spinner center spinnerw"><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div></div></div>`;

            function get_degree(div, education_level_id) {
                var parentDivId = div.closest(".add_more_div").id;
                console.log(parentDivId, education_level_id);

                if(education_level_id == 8 || education_level_id == 9) {
                    $(`#${parentDivId} #degree_div`).html(`<input type="text" name="degree_title[]" placeholder="Type degree" />`)
                    $(`#${parentDivId} #other_degree_div`).addClass('hide')
                }
                else if(education_level_id) {
                    $(`#${parentDivId} #degree_div`).html(btnLoader)
                    $.ajax({
                        url: '../profile_ajax.php',
                        type: 'POST',
                        data: {
                            "cmd": "get_degree_como",
                            "education_level_id": education_level_id,
                        },             
                        success:function(data){
                            var result = JSON.parse(data);
                            var data = result.data;
                            var result = `<select class="combo" name="degree_id[]" onchange="return get_other_degree('${parentDivId}', this.value)">`;
                            if(data.length)
                                for(var i=0; i < data.length; i++) {
                                    result += `
                                        <option value="${data[i].degree_id}">${data[i].degree_name}</option>
                                    `;
                                }
                            result += '<option value="0">'+l('other')+'</option></select>'
                            $(`#${parentDivId} #degree_div`).html(result)
                            $(".combo").select2()
                        },
                        error: function(xhr, status, error) {
                            console.log("Error: " + error);
                        }
             
                    });
                } else {
                    $(`#${parentDivId} #degree_div`).html(`<select class="combo" name="degree_id[]">
                                <option value="">Select</option>
                            </select>`)
                    $(".combo").select2()
                }
            }
            function get_other_degree(parentDivId, degree_id) {
                console.log(parentDivId, degree_id);
                if(degree_id == 0)
                    $(`#${parentDivId} #other_degree_div`).removeClass('hide')
                else
                    $(`#${parentDivId} #other_degree_div`).addClass('hide')
            }

            var myField = `
                <div class="bl">
                    <label><i class="fa fa-bell"></i> <?php echo $level_of_education; ?>: </label>
                    <div class="field">
                    <select class="combo" name="education_level_id[]" onchange="return get_degree(this, this.value)">
                        <option value="">Select</option>
                        <?php
                            foreach($level_of_edu_list as $key => $row) {
                                echo '<option value="' . $key . '">' . $row . '</option>';
                            }
                        ?>
                    </select>
                    </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-graduation-cap"></i> <?php echo $degree_title; ?>: <span class="r">*</span></label>
                    <div class="field">
                    <span id="degree_div">
                        <select class="combo" name="degree_id[]" required>
                            <option value="">Select</option>
                        </select>
                    </span>
                    <span id="other_degree_div" class="hide">
                        <input type="text" name="degree_title[]" />
                    </span>
                    </div>
                </div>

                <div class="bl">
                    <label><i class="fa fa-book"></i> <?php echo $subject_title; ?>: <span class="r">*</span></label>
                    <div class="field">
                    <input type="text" name="subject_title[]" placeholder="Electrical Engineering" required />
                    </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-university"></i> <?php echo $institute_name; ?>: <span class="r">*</span></label>
                    <div class="field">
                    <input type="text" id="school_name" name="school_name[]" placeholder="Dhaka University" required />
                    </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                    <div class="field">
                    <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" />
                    </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-calculator"></i> <?php echo $results; ?>: </label>
                    <div class="field">
                    <input type="text" name="results[]" placeholder="3.59" />
                    </div>
                </div>
                <div class="bl">
                    <label><i class="fa fa-calendar"></i> <?php echo l('passing_year') ?>: <span class="r">*</span></label>
                    <div class="field">
                    <select class="combo" name="passing_year[]">
                        <option value="">Select</option>
                        <?php
                            $currentYear = date("Y");
                            for ($year = 1980; $year <= $currentYear; $year++) {
                                echo '<option value="' . $year . '">' . $year . '</option>';
                            }
                        ?>
                    </select>
                    </div>
                </div>
            `;
        </script>


        <div style="margin-bottom: 30px;width: 50px;">
            <button class="btn small turquoise" style="padding: 0 20px;" value="<?php if($n) echo $n+1; else echo 2; ?>" id="ind" type="button" onclick="return clProfile.add_more_education_field(this.value, myField)"><i class="fa fa-plus"></i></button>
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