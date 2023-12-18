<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_update_verify_phone_number" name="frm_update_verify_phone_number" method="POST" onsubmit="return clProfile.submit_mobile_verification(event, this)">
    <input type="hidden" name="cmd" value="verify_phone_number" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title" id="verify_phone_number"><?php echo l('verify_phone_number'); ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm verify_phone_number_form" id="verifyPhone">
        <div class="bl">
            <label><?php echo l('phone_number'); ?>: <span style="font-weight: bold;"><?php echo $g_user['phone']; ?></span> (<button type="button" id="changeNumber" onclick="return load_phone_number()"><?php echo l('change'); ?></button>)</label>
        </div>
        <div class="bl">
            <label style="font-weight: bold;"><?php echo l('verification_code'); ?>:</label>
            <div class="field">
                <input type="number" id="verification_code" name="verification_code" placeholder="123456" required pattern="\d{1,6}" onkeyup="return check_vnumber(this.value)"  style="width: 70%" />

                <button id="vcode_submit" type="submit" class="btn small turquoise" style="width: 27%" disabled><?php echo l('submit'); ?></button>
            </div>
        </div>
        <div class="bl">
            <label id="resendCodeSpan"><?php echo l('resend_text'); ?> <button id="resendCode" name="resend_code" class="resend_code" type="button" onclick="return clProfile.resendVCode()"><?php echo l('resend_code'); ?></button>
            <?php echo strtolower(l('again')); ?>.</label>
            <div id="timerContainer" style="display: none;">
              <?php echo l('resend_code'); ?>: <span id="timer" style="font-weight: bold"></span> later
            </div>
        </div>

    </div>
    <div class="frm_btn frm_edit">
        <div class="double">
            <span class="l">
                <!-- <button type="submit" id="profile_field_save" class="btn small pink frm_editor_save"><?php // echo $save; ?></button> -->
            </span>
            <span class="r" id="cancelButton">
                <button type="button" onclick="return clProfile.loadTabs('#tabs-1');" id="pp_profile_looking_cancel" class="btn small white_frame frm_editor_cancel"><?php echo l('back'); ?></button>
            </span>
        </div>
    </div>
</form>
</div>

<script>
function backToverify() {
    $("#verify_phone_number").html(`Please confirm your phone number verification`);
    $("#verifyPhone").html(`
        <div class="bl">
            <label><?php echo l('phone_number'); ?>: <span style="font-weight: bold;"><?php echo $g_user['phone']; ?></span> (<button type="button" id="changeNumber" onclick="return load_phone_number()"><?php echo l('change'); ?></button>)</label>
        </div>
        <div class="bl">
            <label style="font-weight: bold;"><?php echo l('verification_code'); ?>:</label>
            <div class="field">
                <input type="number" id="verification_code" name="verification_code" placeholder="123456" required pattern="\d{1,6}" onkeyup="return check_vnumber(this.value)"  style="width: 70%" />

                <button id="vcode_submit" type="submit" class="btn small turquoise" style="width: 27%" disabled><?php echo l('submit'); ?></button>
            </div>
        </div>
        <div class="bl">
            <label id="resendCodeSpan"><?php echo l('resend_text'); ?> <button id="resendCode" name="resend_code" class="resend_code" type="button" onclick="return Profile.resendVCode()"><?php echo l('resend_code'); ?></button>
            <?php echo strtolower(l('again')); ?>.</label>
            <div id="timerContainer" style="display: none;">
              <?php echo l('resend_code'); ?>: <span id="timer" style="font-weight: bold"></span> later
            </div>
        </div>
    `);
    $("#cancelButton").html(`<button type="button" onclick="return clProfile.loadTabs('#tabs-1');" id="pp_profile_looking_cancel" class="btn small white_frame frm_editor_cancel"><?php echo l('back'); ?></button>`)
}
function load_phone_number() {
    $("#verify_phone_number").html(`Verification code will be sent to your changed phone number`);
    $("#verifyPhone").html(`
        <div class="bl">
            <label><?php echo l('phone_number'); ?>: <span style="font-weight: bold;"><?php echo $g_user['phone']; ?></span></label>
        </div>
        <div class="bl" style="display: flex;justify-content: space-between;">
            <div class="field" style="width: 60%">
                <input type="tel" id="join_phone_number" class="inp phone" maxlength="11" placeholder="1712345678">
                <input type="hidden" name="phone" id="full_phone_number"/>
            </div>
            <button type="button" id="cNumber_submit" onclick="return clProfile.changePhoneNumber()" style="padding: 0 15px;text-transform: capitalize;width: 37%" class="btn small turquoise" disabled><?php echo l('change'); ?></button>
        </div>
    `);
    $("#cancelButton").html(`<button type="button" onclick="return backToverify();" class="btn small white_frame frm_editor_cancel"><?php echo l('cancel'); ?></button>`)

    $(function(){

        // phone number
        var phone_number = window.intlTelInput(document.querySelector("#join_phone_number"), {
            separateDialCode: true,
            preferredCountries:["bd","us","gb","sa","ae"],
            hiddenInput: "full",
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.3/js/utils.js"
        })
        document.getElementById('join_phone_number').onkeyup = function(){
            var full_number = phone_number.getNumber(intlTelInputUtils.numberFormat.E164);
            $("#full_phone_number").val(full_number);

            var submitButton = $('#cNumber_submit');

            if (full_number.length === 14) {
                submitButton.prop('disabled', false);
            } else {
                if (full_number.length > 14) {
                    // Truncate the full_number to 6 characters
                    full_number = full_number.substring(0, 14);
                    // Update the input value
                    $('#full_phone_number').val(full_number);
                }

                submitButton.prop('disabled', full_number.length !== 14);
            }
        }
    })
}
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