<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_update_posted_by" name="frm_update_posted_by" method="POST" onsubmit="return clProfile.submit_posted_by(event, this)">
    <input type="hidden" name="cmd" value="update_posted_by" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm">
    	<div class="bl">
            <label><i class="fa fa-user"></i> <?php echo $name; ?>:</label>
            <div class="field">
            <input type="text" id="poster_name" name="poster_name" placeholder="Type Name" value="<?php echo $g_user['poster_name']; ?>" required />
            </div>
        </div>
        <div class="bl">
            <label><i class="fa fa-phone"></i> <?php echo $phone_number; ?>:</label>
            <div class="field">
            <input type="tel" id="join_phone_number" class="inp phone" value="<?php echo $g_user['poster_phone']; ?>" maxlength="10">
            <input type="hidden" name="poster_phone" id="full_phone_number" value="<?php echo $g_user['poster_phone']; ?>"/>
            </div>
        </div>
        <div class="bl">
            <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
            <div class="field">
            <input type="text" name="poster_address" placeholder="Dhaka, Bangladesh" value="<?php echo $g_user['poster_address']; ?>" />
            </div>
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
<script type="text/javascript">
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
        }
    })
</script>