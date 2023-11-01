<div id="<?php echo $cmd; ?>" class="pp_popup_editor visible">
<form id="frm_update_additional_information" name="frm_update_additional_information" method="POST" onsubmit="return clProfile.submit_additional_information(event, this)">
    <input type="hidden" name="cmd" value="update_additional_information" />
    <input type="hidden" name="e_user_id" value="<?php echo $e_user_id; ?>" />
    <div class="bl arrow">
        <div class="title"><?php echo $title; ?>
        <div class="cl"></div>
        </div>
    </div>

    <div class="bl_frm">
        <div class="bl">
            <textarea name="additional_info" id="additional_info" placeholder="Type additional information" rows="10" style=""><?php echo $additional_info; ?></textarea>
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