<form id="frm_update_additional_information" name="frm_update_additional_information" method="POST" onsubmit="return Profile.submit_additional_information(event, this)">
    <input type="hidden" name="cmd" value="update_additional_information" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <script type="text/javascript">
    	function add_more_spouse() {
    		$("#spouse_div").append(`
	    		<div class="form-group">
	                <input type="text" name="spouse_name[]" placeholder="Type spouse name" class="w-350" />
	            </div>
    		`)
    	}
    	function add_more_siblings() {
    		$("#siblings_div").append(`
	    		<div class="form-group">
	                <input type="text" name="siblings_name[]" placeholder="Type sibling name" class="w-350" />
	            </div>
    		`)
    	}
    </script>
    <div class="formdiv">
        <h3><i class="fa fa-user"></i> <?php echo $spouse; ?>:</h3>

        <div id="spouse_div">
        	<?php
        		if(sizeof($spouseList)) {
        			foreach($spouseList as $value) { ?>
        				<div class="form-group">
			                <input type="text" name="spouse_name[]" placeholder="Type spouse name" value="<?php echo $value; ?>" class="w-350" />
			            </div>
        			<?php }
        		} else {
        	?>
	        	<div class="form-group">
	                <input type="text" name="spouse_name[]" placeholder="Type spouse name" class="w-350" />
	            </div>
	        <?php } ?>
        </div>
        <button class="btn small pink" style="padding: 0px 10px;height: 25px;line-height: 20px;margin-left: 5px;" type="button" onclick="return add_more_spouse()"> <i class="fa fa-plus"></i></button>


        <h3 style="margin-top: 20px"><i class="fa fa-child"></i> <?php echo $sibling; ?>:</h3>

        <div id="siblings_div">
        	<?php
        		if(sizeof($siblingsList)) {
        			foreach($siblingsList as $value) { ?>
			            <div class="form-group">
			                <input type="text" name="siblings_name[]" placeholder="Type sibling name" value="<?php echo $value; ?>" class="w-350" />
			            </div>
        			<?php }
        		} else {
        	?>
	        	<div class="form-group">
	                <input type="text" name="siblings_name[]" placeholder="Type sibling name" class="w-350" />
	            </div>
	        <?php } ?>
        </div>
        <button class="btn small pink" style="padding: 0px 10px;height: 25px;line-height: 20px;margin-left: 5px;" type="button" onclick="return add_more_siblings()"> <i class="fa fa-plus"></i></button>

    </div>
</form>