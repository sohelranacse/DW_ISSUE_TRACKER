<form id="frm_verify_phone_number" name="frm_verify_phone_number" method="POST" onsubmit="return Profile.submit_mobile_verification(event, this)">
    <input type="hidden" name="cmd" value="verify_phone_number" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv verify_phone_number_form">
        <div style="padding: 10px 0 0;">
            <label><?php echo l('phone_number'); ?>: <span style="font-weight: bold;"><?php echo $g_user['phone']; ?></span></label>
        </div>
    	<div style="padding: 20px 0;">
            <label style="font-weight: bold;"><?php echo l('verification_code'); ?>:</label>
            <input type="number" id="verification_code" name="verification_code" placeholder="123456" required pattern="\d{1,6}" onkeyup="return check_vnumber(this.value)" />
            <button id="vcode_submit" type="submit" class="btn small turquoise" disabled><?php echo l('submit'); ?></button>
        </div>
        <div class="form-group-full">
            <label id="resendCodeSpan"><?php echo l('resend_text'); ?> <button id="resendCode" name="resend_code" class="resend_code" type="button" onclick="return Profile.resendVCode()"><?php echo l('resend_code'); ?></button>
            <?php echo strtolower(l('again')); ?>.</label>
            <div id="timerContainer" style="display: none;">
              <?php echo l('resend_code'); ?>: <span id="timer"></span>
            </div>
        </div>
    </div>
</form>

<script>
function check_vnumber(code) {
    var submitButton = $('#vcode_submit');

    if (code.length === 6) {
        submitButton.prop('disabled', false);
    } else {
        if (code.length > 6) {
            // Truncate the code to 6 characters
            code = code.substring(0, 6);
            // Update the input value
            $('#verification_code').val(code);
        }

        submitButton.prop('disabled', code.length !== 6);
    }
}
</script>