<form id="frm_update_posted_by" name="frm_update_posted_by" method="POST" onsubmit="return Profile.submit_posted_by(event, this)">
    <input type="hidden" name="cmd" value="update_posted_by" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />

    <div class="formdiv">
    	<div class="form-group-half">
            <label><i class="fa fa-user"></i> <?php echo $name; ?>:</label>
            <input type="text" id="poster_name" name="poster_name" placeholder="Type Name" value="<?php echo $g_user['poster_name']; ?>" required />
        </div>
        <div class="form-group-half">
            <label><i class="fa fa-phone"></i> <?php echo $phone_number; ?>:</label>
            <input type="tel" id="join_phone_number" class="inp phone" value="<?php echo $g_user['poster_phone']; ?>" placeholder="1712345678" maxlength="11">
            <input type="hidden" name="poster_phone" id="full_phone_number" value="<?php echo $g_user['poster_phone']; ?>"/>
        </div>
        <div class="form-group">
            <label><i class="fa fa-map-marker"></i> <?php echo $address; ?>: </label>
            <input type="text" name="poster_address" placeholder="Dhaka, Bangladesh" value="<?php echo $g_user['poster_address']; ?>" style="width: 100%" />
        </div>
    </div>
</form>
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