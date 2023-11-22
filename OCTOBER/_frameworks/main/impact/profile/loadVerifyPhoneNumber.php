<form id="frm_verify_phone_number" name="frm_verify_phone_number" method="POST" onsubmit="return Profile.submit_mobile_verification(event, this)">
    <input type="hidden" name="cmd" value="update_posted_by" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv verify_phone_number_form">
    	<div class="form-group-half">
            <label><?php echo l('verification_code'); ?>:</label>
            <input type="number" id="verification_code" name="verification_code" placeholder="322323" required pattern="\d{1,6}" />
        </div>
        <div class="form-group-half">
            <button id="vcode_submit" type="submit" class="btn small turquoise"><?php echo l('submit'); ?></button>
        </div>
        <div class="form-group-full">
            <label><?php echo l('resend_text'); ?> <button id="resendCode" name="resend_code" class="resend_code" type="button"><?php echo l('resend_code'); ?></button> <?php echo strtolower(l('again')); ?>.</label>
        </div>
    </div>
</form>