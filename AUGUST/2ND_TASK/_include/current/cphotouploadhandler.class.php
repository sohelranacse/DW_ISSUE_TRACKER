<?php
class CPhotoUploadHandler extends UploadHandler {

    protected function handle_form_data($file, $index) {
        $file->file_name = @$_REQUEST['file_name'][$index];
    }

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error,
                                           $index = null, $content_range = null) {
        global $g;
        global $g_user;
        
        $e_user_id = get_param('e_user_id', 0);
        if($e_user_id) // added by sohel
            $g_user = DB::row("SELECT * FROM user WHERE user_id = ".to_sql($e_user_id, 'Number'));

        $isError = false;
		$name = get_param('file_name');// . '.jpg';

        if(Common::isOptionActive('free_site') || User::isSuperPowers() ||
            Common::getOption('upload_limit_photo_count')>DB::result("SELECT COUNT(photo_id) FROM photo WHERE user_id=" . $g_user['user_id'] . " AND visible<>'P'")){
            $file = parent::handle_file_upload(
                $uploaded_file, $name, $size, $type, $error, $index, $content_range
            );
        } else {
            $file->error = 'upload_limit ';
        }

        if (empty($file->error)) {
            $info = CProfilePhoto::photoUpload($this->get_upload_path($file->name));
            $file->id = $info['id'];
            $file->src_r = $info['src_r'];
            $file->name = get_param('file_name');
        }

        if (!guid()) {
            $file->error = 'please_login';
        }
        return $file;
    }
}
