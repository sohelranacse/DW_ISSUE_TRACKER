<?php
/*
	created by Sohel Rana
	Dated: 30 August, 2023
*/
include("./_include/core/main_start.php");

checkByAuth();
if($g_user['role'] == "user")
	redirect('search_results');

class Input {
    private $data = [];

    public function __construct() {
        $this->data = $_POST;
    }

    public function post($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
}
class Controller {
    protected $input;

    public function __construct() {
        $this->input = new Input();
    }
}


class ProfileDocument extends Controller {

	function action() {
		global $g_user;

		if(isset($_POST)) {
			$cmd = $this->input->post('cmd');

			switch ($cmd) {

				case "upload_nid":

					// edit user
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);


				    $fileType = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
				    $allowedFT = array("pdf","jpg","jpeg","png");
				    if(in_array($fileType, $allowedFT)) {

					    $fileName =  base64_encode($g_user['name_seo'].$g_user['user_id']).'.'.$fileType;
					    $targetFilePath = '_files/nid/'.$fileName;
					    
					    if (!empty($g_user['nid_data']) && file_exists('_files/nid/' . $g_user['nid_data']))
    						unlink('_files/nid/' . $g_user['nid_data']);

					    if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {

					    	if($g_user['nid_verify_status'] == 4) // rejected
					    		$nid_verify_status = 3; // reuploaded
					    	else
					    		$nid_verify_status = 2;

					        $data = array(
					        	'nid_data' => $fileName,
					        	'nid_verify_status' => $nid_verify_status,
					        	'nid_verify_requested_on' => date("Y-m-d H:i:s")
					       	);
					        DB::update('user', $data, 'user_id = ' . to_sql($g_user['user_id'], 'Number'));
					        echo 'success';
					    }
					}

				default:
        			echo "Wrong!";
			}

		}		
	}

}

$page = new ProfileDocument();
$page->action();