<?php
/*
	created by Sohel Rana
	Dated: 17 October, 2023
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


class ProfileAjax extends Controller {

	function action() {
		global $g, $g_user;

		if(isset($_POST)) {
			$cmd = $this->input->post('cmd');
			// $action = $g['path']['url_main'].'profile_ajax.php';

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
					break;

				case "get_address_field":

					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $stateList = $cityList = $permanent_stateList = $permanent_cityList = [];

					$countryList = DB::all('SELECT `country_id`, `country_title` FROM `geo_country` WHERE (hidden = 0 OR country_id = 0) ORDER BY `first` DESC, `country_title` ASC');

					// current
					if($g_user['current_country_id'])
						$stateList = DB::all('SELECT state_id, state_title FROM geo_state WHERE hidden = 0 AND country_id = '.to_sql($g_user['current_country_id'], 'Number').' ORDER BY state_title');

					if($g_user['current_state_id'])
						$cityList = DB::all('SELECT city_id, city_title FROM geo_city WHERE hidden = 0 AND state_id = '.to_sql($g_user['current_state_id'], 'Number').' ORDER BY city_title');

					// permanent
					if($g_user['permanent_country_id'])
						$permanent_stateList = DB::all('SELECT state_id, state_title FROM geo_state WHERE hidden = 0 AND country_id = '.to_sql($g_user['permanent_country_id'], 'Number').' ORDER BY state_title');

					if($g_user['permanent_state_id'])
						$permanent_cityList = DB::all('SELECT city_id, city_title FROM geo_city WHERE hidden = 0 AND state_id = '.to_sql($g_user['permanent_state_id'], 'Number').' ORDER BY city_title');


					ob_start();
					include $g['tmpl']['dir_tmpl_main'].'profile/get_address_field.php';
					$data = ob_get_clean();
					echo $data;
					break;

				case 'get_state':
					$country_id = $this->input->post('country_id');
					$stateList = DB::all('SELECT state_id, state_title FROM geo_state WHERE hidden = 0 AND country_id = '.to_sql($country_id, 'Number').' ORDER BY state_title');

					$data = '<option value="">Select</option>';
					if(sizeof($stateList))
						foreach($stateList as $value) {
							$data .= '<option value="'.$value['state_id'].'">'.$value['state_title'].'</option>';
						}
					echo $data;
					break;

				case 'get_city':
					$state_id = $this->input->post('state_id');
					$cityList = DB::all('SELECT city_id, city_title FROM geo_city WHERE hidden = 0 AND state_id = '.to_sql($state_id, 'Number').' ORDER BY city_title');

					$data = '<option value="">Select</option>';
					if(sizeof($cityList))
						foreach($cityList as $value) {
							$data .= '<option value="'.$value['city_id'].'">'.$value['city_title'].'</option>';
						}
					echo $data;
					break;

				case 'update_address':
					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $current_street = $this->input->post('current_street');
				    $current_country_id = $this->input->post('country_id_current');
				    $current_state_id = $this->input->post('state_id_current');
				    $current_city_id = $this->input->post('city_id_current');
				    $permanent_street = $this->input->post('permanent_street');
				    $permanent_country_id = $this->input->post('country_id_permanent');
				    $permanent_state_id = $this->input->post('state_id_permanent');
				    $permanent_city_id = $this->input->post('city_id_permanent');

				    $data = [
				    	'current_street'	=>	$current_street,
				    	'current_country_id'	=>	$current_country_id,
				    	'current_state_id'	=>	$current_state_id,
				    	'current_city_id'	=>	$current_city_id,
				    	'permanent_street'	=>	$permanent_street,
				    	'permanent_country_id'	=>	$permanent_country_id,
				    	'permanent_state_id'	=>	$permanent_state_id,
				    	'permanent_city_id'	=>	$permanent_city_id,
				    ];
				    DB::update('user', $data, '`user_id` = ' . to_sql($g_user['user_id']));

				    $user_info = DB::row('
				    	SELECT a.current_street, a.permanent_street,
				    	(SELECT country_title FROM geo_country WHERE country_id = a.current_country_id) AS current_country,
				    	(SELECT state_title FROM geo_state WHERE state_id = a.current_state_id) AS current_state,
				    	(SELECT city_title FROM geo_city WHERE city_id = a.current_city_id) AS current_city,
				    	(SELECT country_title FROM geo_country WHERE country_id = a.permanent_country_id) AS permanent_country,
				    	(SELECT state_title FROM geo_state WHERE state_id = a.permanent_state_id) AS permanent_state,
				    	(SELECT city_title FROM geo_city WHERE city_id = a.permanent_city_id) AS permanent_city
				    	FROM user a
				    	WHERE a.user_id = '.to_sql($g_user['user_id'])
				    );
				    $result['msg'] = "success";
				    $result['current_address'] = $user_info['current_street'].', '.$user_info['current_city'].', '.$user_info['current_state'].', '.$user_info['current_country'];
				    $result['permanent_address'] = $user_info['permanent_street'].', '.$user_info['permanent_city'].', '.$user_info['permanent_state'].', '.$user_info['permanent_country'];
	                echo json_encode($result);
					break;
				case "loadFavoriteAddressEdit":

					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $stateList = $cityList = $permanent_stateList = $permanent_cityList = [];

					$countryList = DB::all('SELECT `country_id`, `country_title` FROM `geo_country` WHERE (hidden = 0 OR country_id = 0) ORDER BY `first` DESC, `country_title` ASC');

					// favorite
					if($g_user['favorite_country_id'])
						$stateList = DB::all('SELECT state_id, state_title FROM geo_state WHERE hidden = 0 AND country_id = '.to_sql($g_user['favorite_country_id'], 'Number').' ORDER BY state_title');

					if($g_user['favorite_state_id'])
						$cityList = DB::all('SELECT city_id, city_title FROM geo_city WHERE hidden = 0 AND state_id = '.to_sql($g_user['favorite_state_id'], 'Number').' ORDER BY city_title');

					// unfavorite
					if($g_user['unfavorite_country_id'])
						$permanent_stateList = DB::all('SELECT state_id, state_title FROM geo_state WHERE hidden = 0 AND country_id = '.to_sql($g_user['unfavorite_country_id'], 'Number').' ORDER BY state_title');

					if($g_user['unfavorite_state_id'])
						$permanent_cityList = DB::all('SELECT city_id, city_title FROM geo_city WHERE hidden = 0 AND state_id = '.to_sql($g_user['unfavorite_state_id'], 'Number').' ORDER BY city_title');
					
					ob_start();
					include $g['tmpl']['dir_tmpl_main'].'profile/loadFavoriteAddressEdit.php';
					$data = ob_get_clean();
					echo $data;
					break;

				case 'update_fevorite_unfevorite_region':
					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $favorite_country_id = $this->input->post('favorite_country_id');
				    $favorite_state_id = $this->input->post('favorite_state_id');
				    $favorite_city_id = $this->input->post('favorite_city_id');
				    $unfavorite_country_id = $this->input->post('unfavorite_country_id');
				    $unfavorite_state_id = $this->input->post('unfavorite_state_id');
				    $unfavorite_city_id = $this->input->post('unfavorite_city_id');

				    $data = [
				    	'favorite_country_id'	=>	$favorite_country_id,
				    	'favorite_state_id'	=>	$favorite_state_id,
				    	'favorite_city_id'	=>	$favorite_city_id,
				    	'unfavorite_country_id'	=>	$unfavorite_country_id,
				    	'unfavorite_state_id'	=>	$unfavorite_state_id,
				    	'unfavorite_city_id'	=>	$unfavorite_city_id,
				    ];
				    DB::update('user', $data, '`user_id` = ' . to_sql($g_user['user_id']));

				    $user_info = DB::row('
				    	SELECT 
				    	(SELECT country_title FROM geo_country WHERE country_id = a.favorite_country_id) AS favorite_country,
				    	(SELECT state_title FROM geo_state WHERE state_id = a.favorite_state_id) AS favorite_state,
				    	(SELECT city_title FROM geo_city WHERE city_id = a.favorite_city_id) AS favorite_city,
				    	(SELECT country_title FROM geo_country WHERE country_id = a.unfavorite_country_id) AS unfavorite_country,
				    	(SELECT state_title FROM geo_state WHERE state_id = a.unfavorite_state_id) AS unfavorite_state,
				    	(SELECT city_title FROM geo_city WHERE city_id = a.unfavorite_city_id) AS unfavorite_city
				    	FROM user a
				    	WHERE a.user_id = '.to_sql($g_user['user_id'])
				    );
				    $result['msg'] = "success";
				    $result['favorite_address'] = $user_info['favorite_city'].', '.$user_info['favorite_state'].', '.$user_info['favorite_country'];
				    $result['unfavorite_address'] = $user_info['unfavorite_city'].', '.$user_info['unfavorite_state'].', '.$user_info['unfavorite_country'];
	                echo json_encode($result);
					break;

				default:
        			echo "Wrong!";
			}

		}		
	}

}

$page = new ProfileAjax();
$page->action();