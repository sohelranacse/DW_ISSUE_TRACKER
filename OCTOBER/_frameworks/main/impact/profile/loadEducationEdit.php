<form id="frm_update_education" name="frm_update_education" method="POST" onsubmit="return Profile.submit_education(event, this)">
    <input type="hidden" name="cmd" value="update_education" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv">
        <div id="paginate">

            <?php
            $n = sizeof($educationList);

            if($n) {
                foreach($educationList as $key => $value) { ?>
                    <div class="add_more_div" id="more_education<?php echo $key+1; ?>">
                        <div class="close_div">
                            <button type="button" onclick="Profile.close_multiple_div(<?php echo $key+1; ?>)"><i class="fa fa-times"></i></button>
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-graduation-cap"></i> <?php echo $degree_title; ?>: <span class="r">*</span></label>
                            <input type="text" id="degree_title" name="degree_title[]" placeholder="BSc in Computer Science" value="<?php echo $value['degree_title']; ?>" required />
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-university"></i> <?php echo $institute_name; ?>: <span class="r">*</span></label>
                            <input type="text" id="school_name" name="school_name[]" placeholder="Dhaka University" value="<?php echo $value['school_name']; ?>" required />
                        </div>
                        <div class="form-group">
                            <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                            <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" value="<?php echo $value['address']; ?>" style="width: 100%" />
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-calculator"></i> <?php echo $results; ?>: </label>
                            <input type="text" name="results[]" value="<?php echo $value['results']; ?>" placeholder="3.59" />
                        </div>
                        <div class="form-group-half">
                            <label><i class="fa fa-calendar"></i> Passing Year: </label>
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
                <?php } ?>
                <div class="add_more_div" id="more_education<?php echo $n+1; ?>"></div>
            <?php } else { ?>
                <div class="add_more_div" id="more_education1">
                    <div class="close_div">
                        <button type="button" onclick="Profile.close_multiple_div(1)"><i class="fa fa-times"></i></button>
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-graduation-cap"></i> <?php echo $degree_title; ?>: <span class="r">*</span></label>
                        <input type="text" id="degree_title" name="degree_title[]" placeholder="BSc in Computer Science" required />
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-university"></i> <?php echo $institute_name; ?>: <span class="r">*</span></label>
                        <input type="text" id="school_name" name="school_name[]" placeholder="Dhaka University" required />
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                        <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" style="width: 100%" />
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-calculator"></i> <?php echo $results; ?>: </label>
                        <input type="text" name="results[]" placeholder="3.59" />
                    </div>
                    <div class="form-group-half">
                        <label><i class="fa fa-calendar"></i> Passing Year: </label>
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

                <div class="add_more_div" id="more_education2"></div>
            <?php } ?>

        </div>

        <script type="text/javascript">
            var myField = `
                <div class="form-group-half">
                    <label><i class="fa fa-graduation-cap"></i> <?php echo $degree_title; ?>: <span class="r">*</span></label>
                    <input type="text" id="degree_title" name="degree_title[]" placeholder="BSc in Computer Science" required />
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-university"></i> <?php echo $institute_name; ?>: <span class="r">*</span></label>
                    <input type="text" id="school_name" name="school_name[]" placeholder="Dhaka University" required />
                </div>
                <div class="form-group">
                    <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
                    <input type="text" name="address[]" placeholder="Dhaka, Bangladesh" style="width: 100%" />
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-calculator"></i> <?php echo $results; ?>: </label>
                    <input type="text" name="results[]" placeholder="3.59" />
                </div>
                <div class="form-group-half">
                    <label><i class="fa fa-calendar"></i> Passing Year: </label>
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
            `;
        </script>


        <div style="text-align: right;padding-right: 6px;padding-top: 10px;">
            <button class="btn small pink" style="padding: 0 20px;" value="<?php if($n) echo $n+1; else echo 2; ?>" id="ind" type="button" onclick="return Profile.add_more_education_field(this.value, myField)">Add more <i class="fa fa-plus"></i></button>
        </div>

    </div>
</form>