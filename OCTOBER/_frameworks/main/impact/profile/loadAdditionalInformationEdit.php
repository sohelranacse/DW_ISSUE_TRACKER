<form id="frm_update_additional_information" name="frm_update_additional_information" method="POST" onsubmit="return Profile.submit_additional_information(event, this)">
    <input type="hidden" name="cmd" value="update_additional_information" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv">
        <div class="form-group">
            <textarea name="additional_info" id="additional_info" placeholder="Type additional information" rows="10" style="width: 100%;line-height: 26px"><?php echo $additional_info; ?></textarea>
        </div>

    </div>
</form>