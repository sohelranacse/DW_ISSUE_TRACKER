<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_update_additional_information" name="frm_update_additional_information" method="POST" onsubmit="return clProfile.submit_additional_information(event, this)">
    <input type="hidden" name="cmd" value="update_additional_information" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <script type="text/javascript">
    	function add_more_spouse() {
    		$("#spouse_div").append(`
	    		<div class="bl">
	                <input type="text" name="spouse_name[]" placeholder="Type spouse name" class="w-350" />
	            </div>
    		`)
    	}
    	function add_more_siblings() {
    		$("#siblings_div").append(`
	    		<div class="bl">
	                <input type="text" name="siblings_name[]" placeholder="Type sibling name" class="w-350" />
	            </div>
    		`)
    	}
    </script>
    <div class="bl_frm">
        <h3><i class="fa fa-user"></i> <?php echo $spouse; ?>:</h3>

        <div id="spouse_div">
        	<?php
        		if(sizeof($spouseList)) {
        			foreach($spouseList as $value) { ?>
        				<div class="bl">
			                <input type="text" name="spouse_name[]" placeholder="Type spouse name" value="<?php echo $value; ?>" class="w-350" />
			            </div>
        			<?php }
        		} else {
        	?>
	        	<div class="bl">
	                <input type="text" name="spouse_name[]" placeholder="Type spouse name" class="w-350" />
	            </div>
	        <?php } ?>
        </div>
        <div style="margin: 10px 0 20px;">
        <button class="btn small turquoise" style="width: 30px;" type="button" onclick="return add_more_spouse()"> <i class="fa fa-plus"></i></button>
    	</div>


        <h3 style="margin-top: 20px"><i class="fa fa-child"></i> <?php echo $sibling; ?>:</h3>

        <div id="siblings_div">
        	<?php
        		if(sizeof($siblingsList)) {
        			foreach($siblingsList as $value) { ?>
			            <div class="bl">
			                <input type="text" name="siblings_name[]" placeholder="Type sibling name" value="<?php echo $value; ?>" class="w-350" />
			            </div>
        			<?php }
        		} else {
        	?>
	        	<div class="bl">
	                <input type="text" name="siblings_name[]" placeholder="Type sibling name" class="w-350" />
	            </div>
	        <?php } ?>
        </div>
        <div style="margin: 10px 0 20px;">
        <button class="btn small turquoise" style="width: 30px;" type="button" onclick="return add_more_siblings()"> <i class="fa fa-plus"></i></button>
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