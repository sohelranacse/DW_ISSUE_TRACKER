<?php
/*
	created by Sohel Rana
	Dated: 17 October, 2023
*/
$g['mobile_redirect_off'] = true;
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
			$mobile = $this->input->post('mobile') ? $this->input->post('mobile') : '';
			$htmlPath = $mobile ? $g['tmpl']['dir_tmpl_mobile'] : $g['tmpl']['dir_tmpl_main'];
			$cancel = l('cancel');
			$save = l('save');

			switch ($cmd) {

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

					$country = l('country');
					$state = l('state');
					$city = l('city');
					$street = l('street');
					$current_address = l('current_address');
					$permanent_address = l('permanent_address');
					$title = l('address');

					ob_start();
					include $htmlPath.'profile/get_address_field.php';
					$data = ob_get_clean();

					echo json_encode(['status' => true,'data' => $data]);
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
				    	'current_street'	=>	$current_street ? $current_street : '',
				    	'current_country_id'	=>	$current_country_id ? $current_country_id : '',
				    	'current_state_id'	=>	$current_state_id ? $current_state_id : '',
				    	'current_city_id'	=>	$current_city_id ? $current_city_id : '',
				    	'permanent_street'	=>	$permanent_street ? $permanent_street : '',
				    	'permanent_country_id'	=>	$permanent_country_id ? $permanent_country_id : '',
				    	'permanent_state_id'	=>	$permanent_state_id ? $permanent_state_id : '',
				    	'permanent_city_id'	=>	$permanent_city_id ? $permanent_city_id : '',
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
				    $result['current_address'] = $result['permanent_address'] = '';

				    $current_address = implode(', ', array_filter([$user_info['current_street'], $user_info['current_city'], $user_info['current_state'], $user_info['current_country']]));
				    $permanent_address = implode(', ', array_filter([$user_info['permanent_street'], $user_info['permanent_city'], $user_info['permanent_state'], $user_info['permanent_country']]));

				    if($current_address)
				    	$result['current_address'] = $current_address;
				    if($permanent_address)
				    	$result['permanent_address'] = $permanent_address;
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

					$country = l('country');
					$state = l('state');
					$city = l('city');
					$favored_location = l('favored_location');
					$unfavored_location = l('unfavored_location');
					$title = l('location_preference');

					ob_start();
					include $htmlPath.'profile/loadFavoriteAddressEdit.php';
					$data = ob_get_clean();
					echo json_encode(['status' => true,'data' => $data]);
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
				    $favored = $unfavored = [];

				    // favored
				    if($user_info['favorite_city'])
				    	$favored['favorite_city'] = $user_info['favorite_city'];
				    if($user_info['favorite_state'])
				    	$favored['favorite_state'] = $user_info['favorite_state'];
				    if($user_info['favorite_country'])
				    	$favored['favorite_country'] = $user_info['favorite_country'];

				    // unfavored
				    if($user_info['unfavorite_city'])
				    	$unfavored['unfavorite_city'] = $user_info['unfavorite_city'];
				    if($user_info['unfavorite_state'])
				    	$unfavored['unfavorite_state'] = $user_info['unfavorite_state'];
				    if($user_info['unfavorite_country'])
				    	$unfavored['unfavorite_country'] = $user_info['unfavorite_country'];

				    $result['favorite_address'] = implode(", ", $favored);
				    $result['unfavorite_address'] = implode(", ", $unfavored);
	                echo json_encode($result);
					break;

				case 'loadEducationEdit':

					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $educationList = DB::all("SELECT * FROM `user_education` WHERE user_id = {$g_user['user_id']} ORDER BY added_on");

					$degree_title = l('degree_title');
					$institute_name = l('institute_name');
					$results = l('results');
					$passing_year = l('passing_year');
					$address = l('address');
					$title = l('education');

					ob_start();
					include $htmlPath.'profile/loadEducationEdit.php';
					$data = ob_get_clean();
					echo json_encode(['status' => true,'data' => $data]);
					break;

				case 'update_education':
					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $degree = $this->input->post('degree_title');
				    $school_name = $this->input->post('school_name');
				    $address = $this->input->post('address');
				    $results = $this->input->post('results');
				    $passing_year = $this->input->post('passing_year');

				    $i = 0;
				    DB::delete('user_education', '`user_id` =' . to_sql($g_user['user_id']));
				    if($this->input->post('degree_title') && sizeof($degree)) {
					    foreach($degree as $degree_title) {

					    	$data = [
				    			'degree_title'	=>	$degree_title,
				    			'school_name'	=>	$school_name[$i],
				    			'address'		=>	$address[$i] ? $address[$i] : '',
				    			'results'		=>	$results[$i] ? $results[$i] : '',
				    			'passing_year'	=>	$passing_year[$i] ? $passing_year[$i] : '',
				    			'added_on'		=>	date("Y-m-d H:i:s"),
				    			'user_id'		=>	$g_user['user_id'],
				    		];
				    		DB::insert('user_education', $data);

						    $i++;
					    }	
					}					    


				    $educationList = DB::all("SELECT * FROM `user_education` WHERE user_id = {$g_user['user_id']} ORDER BY added_on");
				    echo json_encode($educationList);
					break;

				case 'loadProfessionEdit':

					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $professionType = DB::all("SELECT * FROM `var_preferred_profession` ORDER BY title");
				    $professionList = DB::all("SELECT * FROM `user_profession` WHERE user_id = {$g_user['user_id']} ORDER BY added_on");

					$profession_type = l('profession_type');
					$position = l('position');
					$address = l('address');
					$company = l('company');
					$title = l('profession');

					ob_start();
					include $htmlPath.'profile/loadProfessionEdit.php';
					$data = ob_get_clean();
					echo json_encode(['status' => true,'data' => $data]);
					break;

				case 'update_profession':
					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $profession = $this->input->post('profession_type');
				    $position = $this->input->post('position');
				    $address = $this->input->post('address');
				    $company = $this->input->post('company');
				    
				    $i = 0;
				    DB::delete('user_profession', '`user_id` =' . to_sql($g_user['user_id']));
				    if($this->input->post('profession_type') && sizeof($profession)) {
					    foreach($profession as $profession_type) {

					    	$data = [
				    			'profession_type'	=>	$profession_type,
				    			'position'		=>	$position[$i],
				    			'company'		=>	$company[$i],
				    			'address'		=>	$address[$i] ? $address[$i] : '',
				    			'added_on'		=>	date("Y-m-d H:i:s"),
				    			'user_id'		=>	$g_user['user_id'],
				    		];
				    		DB::insert('user_profession', $data);

						    $i++;
					    }	
					}					    

					$professionList = DB::all("
				    	SELECT a.*, b.title
				    	FROM user_profession a
				    	INNER JOIN var_preferred_profession b ON (a.profession_type = b.id)
				    	WHERE a.user_id = {$g_user['user_id']}
				    	ORDER BY a.added_on
				    ");
				    echo json_encode($professionList);
					break;

				case 'loadRelativesEdit':

					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $professionType = DB::all("SELECT * FROM `var_preferred_profession` ORDER BY title");
				    $maritalStatus = DB::all("SELECT * FROM `var_marital_status` ORDER BY title");
				    $relativeList = DB::all("SELECT * FROM `user_relatives` WHERE user_id = {$g_user['user_id']} ORDER BY added_on");

					$highest_degree = l('highest_degree');
					$marital_status = l('marital_status');
					$name = l('name');
					$relation = l('relation');
					$address = l('address');
					$profession_type = l('profession_type');
					$position = l('position');
					$company = l('company');
					$title = l('relatives');

					ob_start();
					include $htmlPath.'profile/loadRelativesEdit.php';
					$data = ob_get_clean();
					echo json_encode(['status' => true,'data' => $data]);
					break;

				case 'update_relatives':
					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $relatives = $this->input->post('relative_name');
				    $relation = $this->input->post('relation');
				    $marital_status = $this->input->post('marital_status');
				    $address = $this->input->post('address');
				    $profession_type = $this->input->post('profession_type');
				    $position = $this->input->post('position');
				    $company = $this->input->post('company');
				    $degree_title = $this->input->post('degree_title');

				    $i = 0;
				    DB::delete('user_relatives', '`user_id` =' . to_sql($g_user['user_id']));
				    if($this->input->post('relative_name') && sizeof($relatives)) {
					    foreach($relatives as $relative_name) {

					    	$data = [
				    			'relative_name'	=>	$relative_name,
				    			'relation'		=>	$relation[$i],
				    			'added_on'		=>	date("Y-m-d H:i:s"),
				    			'user_id'		=>	$g_user['user_id'],
				    		];

				    		// optional
				    		if($marital_status[$i]) $data['marital_status'] = $marital_status[$i];
				    		if($address[$i]) $data['address'] = $address[$i];
				    		if($profession_type[$i]) $data['profession_type'] = $profession_type[$i];
				    		if($position[$i]) $data['position'] = $position[$i];
				    		if($company[$i]) $data['company'] = $company[$i];
				    		if($degree_title[$i]) $data['degree_title'] = $degree_title[$i];

				    		DB::insert('user_relatives', $data);

						    $i++;
					    }	
					}					    

					$relativeList = DB::all("
				    	SELECT a.*, b.title, c.title AS marital_title
				    	FROM user_relatives a
				    	LEFT JOIN var_preferred_profession b ON (a.profession_type = b.id)
				    	LEFT JOIN var_marital_status c ON (a.marital_status = c.id)
				    	WHERE a.user_id = {$g_user['user_id']}
				    	ORDER BY a.added_on
				    ");
				    echo json_encode($relativeList);
					break;

				case 'loadAdditionalInformationEdit':

					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);


				    $spouseList = $siblingsList = [];
				    if($g_user['spouse_name'])
				    	$spouseList = explode(', ', $g_user['spouse_name']);

				    if($g_user['siblings_name'])
				    	$siblingsList = explode(', ', $g_user['siblings_name']);

					$spouse = l('spouse');
					$sibling = l('sibling');
				    $title = $this->input->post('additional_information');

					ob_start();
					include $htmlPath.'profile/loadAdditionalInformationEdit.php';
					$data = ob_get_clean();
					echo json_encode(['status' => true,'data' => $data]);
					break;

				case 'update_additional_information':
					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $spouseList = $this->input->post('spouse_name');
				    $siblingsList = $this->input->post('siblings_name');

				    $spouse_name = $siblings_name = '';

				    $spouseListFilter = array_filter($spouseList);
				    if($spouseListFilter)
				    	$spouse_name = implode(", ", $spouseListFilter);

				    $siblingsListFilter = array_filter($siblingsList);
				    if($siblingsListFilter)			    
				    	$siblings_name = implode(", ", $siblingsListFilter);

				    $data = [
				    	'spouse_name' => $spouse_name,
				    	'siblings_name' => $siblings_name
				    ];
				    DB::update('user', $data, '`user_id` = ' . to_sql($g_user['user_id']));

				    $result = [
				    	'success'		=>	'success',
				    	'no_of_spouse'	=>	sizeof($spouseListFilter),
				    	'spouse_name'	=>	$spouse_name,
				    	'no_of_siblings' =>	sizeof($siblingsListFilter),
				    	'siblings_name'	=>	$siblings_name
				    ];		
				    echo json_encode($result);
					break;

				case 'loadPostedByEdit':

					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

					$name = l('name');
					$phone_number = l('phone_number');
					$address = l('address');
					$title = l('relatives');

					ob_start();
					include $htmlPath.'profile/loadPostedByEdit.php';
					$data = ob_get_clean();
					echo json_encode(['status' => true,'data' => $data]);
					break;

				case 'update_posted_by':
					// user information
					$e_user_id = $this->input->post('e_user_id');
				    if($e_user_id)
				        $g_user = User::getInfoFull($e_user_id);

				    $poster_name = $this->input->post('poster_name');
				    $poster_phone = $this->input->post('poster_phone');
				    $poster_address = $this->input->post('poster_address');
				    $title = $this->input->post('posted_by');

				    $data = [
				    	'poster_name' => $poster_name ? $poster_name : '',
				    	'poster_phone' => $poster_phone ? $poster_phone : '',
				    	'poster_address' => $poster_address ? $poster_address : ''
				    ];
				    DB::update('user', $data, '`user_id` = ' . to_sql($g_user['user_id']));

				    echo json_encode($data);
					break;				

				default:
        			echo "Wrong!";
			}

		}		
	}

}

$page = new ProfileAjax();
$page->action();