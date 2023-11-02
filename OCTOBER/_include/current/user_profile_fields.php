<?php
	
	// hide edit icon
	if($row_user['user_id'] == self::$guid || $this->c_user_id) {
		$html->parse('address_edit', false);
		$html->parse('location_edit', false);
		$html->parse('education_edit', false);
		$html->parse('profession_edit', false);
		$html->parse('relative_edit', false);
		$html->parse('posted_by_edit', false);
		$html->parse('additional_in_edit', false);

		$visitor = 0;
	} else 
		$visitor = 1;

	
	############ ADDRESS ##############
    $address_info = DB::row('
        SELECT a.current_street, a.permanent_street,
        (SELECT country_title FROM geo_country WHERE country_id = a.current_country_id) AS current_country,
        (SELECT state_title FROM geo_state WHERE state_id = a.current_state_id) AS current_state,
        (SELECT city_title FROM geo_city WHERE city_id = a.current_city_id) AS current_city,
        (SELECT country_title FROM geo_country WHERE country_id = a.permanent_country_id) AS permanent_country,
        (SELECT state_title FROM geo_state WHERE state_id = a.permanent_state_id) AS permanent_state,
        (SELECT city_title FROM geo_city WHERE city_id = a.permanent_city_id) AS permanent_city
        FROM user a
        WHERE a.user_id = '.to_sql($row['user_id'])
    );

    $current_address = implode(', ', array_filter([$address_info['current_street'], $address_info['current_city'], $address_info['current_state'], $address_info['current_country']]));
    $permanent_address = implode(', ', array_filter([$address_info['permanent_street'], $address_info['permanent_city'], $address_info['permanent_state'], $address_info['permanent_country']]));

    if($current_address) {
        $html->setvar('current_address', $current_address);
        $html->parse('current_address', false);
    }
    if($permanent_address) {
        $html->setvar('permanent_address', $permanent_address);
        $html->parse('permanent_address', false);
    }

    (empty($current_address) && empty($permanent_address) && $visitor) ? '' : $html->parse('address_block', false);


    ############ LOCATION PREFERENCE ##############
    $favorite_un_address_info = DB::row('
        SELECT 
        (SELECT country_title FROM geo_country WHERE country_id = a.favorite_country_id) AS favorite_country,
        (SELECT state_title FROM geo_state WHERE state_id = a.favorite_state_id) AS favorite_state,
        (SELECT city_title FROM geo_city WHERE city_id = a.favorite_city_id) AS favorite_city,
        (SELECT country_title FROM geo_country WHERE country_id = a.unfavorite_country_id) AS unfavorite_country,
        (SELECT state_title FROM geo_state WHERE state_id = a.unfavorite_state_id) AS unfavorite_state,
        (SELECT city_title FROM geo_city WHERE city_id = a.unfavorite_city_id) AS unfavorite_city
        FROM user a
        WHERE a.user_id = '.to_sql($row['user_id'])
    );
    $favored = $unfavored = [];
            
    // favored
    if($favorite_un_address_info['favorite_city'])
        $favored['favorite_city'] = $favorite_un_address_info['favorite_city'];
    if($favorite_un_address_info['favorite_state'])
        $favored['favorite_state'] = $favorite_un_address_info['favorite_state'];
    if($favorite_un_address_info['favorite_country'])
        $favored['favorite_country'] = $favorite_un_address_info['favorite_country'];

    // unfavored
    if($favorite_un_address_info['unfavorite_city'])
        $unfavored['unfavorite_city'] = $favorite_un_address_info['unfavorite_city'];
    if($favorite_un_address_info['unfavorite_state'])
        $unfavored['unfavorite_state'] = $favorite_un_address_info['unfavorite_state'];
    if($favorite_un_address_info['unfavorite_country'])
        $unfavored['unfavorite_country'] = $favorite_un_address_info['unfavorite_country'];

    $favorite_address = implode(", ", $favored);
    $unfavorite_address = implode(", ", $unfavored);

    if($favorite_address) {
        $html->setvar('favorite_address', $favorite_address);
        $html->parse('favorite_address', false);
    }
    if($unfavorite_address) {
        $html->setvar('unfavorite_address', $unfavorite_address);
        $html->parse('unfavorite_address', false);
    }

    (empty($favorite_address) && empty($unfavorite_address) && $visitor) ? '' : $html->parse('location_preference_block', false);


    ############ EDUCATION ##############
    $educationList = DB::all("
    	SELECT a.*, b.degree_name
    	FROM user_education a
    	LEFT JOIN user_education_degree b ON (a.degree_id = b.degree_id)
    	WHERE a.user_id = {$row['user_id']}
    	ORDER BY a.added_on
    ");
    $educationData = '';
    if(sizeof($educationList)) {
    	foreach($educationList as $educationRow) {

    		if($educationRow['degree_id'] > 0)
    			$educationData .= '<li><i class="fa fa-graduation-cap"></i> ' . $educationRow['degree_name'] . '</li>';
    		else
    			$educationData .= '<li><i class="fa fa-graduation-cap"></i> ' . $educationRow['degree_title'] . '</li>';

		    $educationData .= '<ul>';

		    $educationData .= '<li><i class="fa fa-book"></i> ' . $educationRow['subject_title'] . '</li>';
		    $educationData .= '<li><i class="fa fa-university"></i> ' . $educationRow['school_name'] . '</li>';

		    if (!empty($educationRow['address']))
		        $educationData .= '<li><i class="fa fa-map-marker"></i> ' . $educationRow['address'] . '</li>';
		    
		    if (!empty($educationRow['results']))
		        $educationData .= '<li><i class="fa fa-calculator"></i> ' . $educationRow['results'] . '</li>';
		    

		    if (!empty($educationRow['passing_year']))
		        $educationData .= '<li><i class="fa fa-calendar"></i> ' . $educationRow['passing_year'] . '</li>';
		    

		    $educationData .= '</ul>';
    	}
    	$html->setvar('educationData', $educationData);
    	$html->parse('educationData', false);
    }

    (empty($educationData) && $visitor) ? '' : $html->parse('education_block', false);


    ############ PROFESSION ##############
    $professionList = DB::all("
    	SELECT a.*, b.title
    	FROM user_profession a
    	INNER JOIN var_preferred_profession b ON (a.profession_type = b.id)
    	WHERE a.user_id = {$row['user_id']}
    	ORDER BY a.added_on
    ");
    $professionData = '';
    if(sizeof($professionList)) {
    	foreach ($professionList as $professionRow) {
		    $professionData .= '<li><i class="fa fa-level-up"></i> ' . $professionRow['position'] . '</li>';
		    $professionData .= '<ul>';
		    $professionData .= '<li><i class="fa fa-industry"></i> ' . $professionRow['company'] . '</li>';
		    		    
		    $professionData .= '<li><i class="fa fa-bullhorn"></i> ' . $professionRow['title'] . '</li>';
		    
		    if (!empty($professionRow['address']))
		        $professionData .= '<li><i class="fa fa-map-marker"></i> ' . $professionRow['address'] . '</li>';

		    if (!empty($professionRow['from_date'])) {
		    	$and_end_date = $professionRow['to_date'] ? $professionRow['to_date'] : l('continuing');

		        $professionData .= '<li><i class="fa fa-calendar"></i> ' . $professionRow['from_date'] . ' to '.$and_end_date.'</li>';
		    }
		    
		    $professionData .= '</ul>';
		}
    	$html->setvar('professionData', $professionData);
    	$html->parse('professionData', false);
    }

    (empty($professionData) && $visitor) ? '' : $html->parse('profession_block', false);


    ############ RELATIVES ##############
    $relativeList = DB::all("
    	SELECT a.*, b.title, c.title AS marital_title
    	FROM user_relatives a
    	LEFT JOIN var_preferred_profession b ON (a.profession_type = b.id)
    	LEFT JOIN var_marital_status c ON (a.marital_status = c.id)
    	WHERE a.user_id = {$row['user_id']}
    	ORDER BY a.added_on
    ");
    $relativeData = '';
    if(sizeof($relativeList)) {
    	foreach ($relativeList as $relativeRow) {
    		$relativeData .= '<li><i class="fa fa-user"></i> ' . $relativeRow['relative_name'] . '</li>';
		    $relativeData .= '<ul>';
		    $relativeData .= '<li><i class="fa fa-link"></i> ' . $relativeRow['relation'] . '</li>';
		    
		    if (!empty($relativeRow['marital_status']))
		        $relativeData .= '<li><i class="fa fa-circle"></i> ' . $relativeRow['marital_title'] . '</li>';
		    
		    if (!empty($relativeRow['address']))
		        $relativeData .= '<li><i class="fa fa-map-marker"></i> ' . $relativeRow['address'] . '</li>';
		    
		    if (!empty($relativeRow['profession_type']))
		        $relativeData .= '<li><i class="fa fa-bullhorn"></i> ' . $relativeRow['title'] . '</li>';
		    
		    if (!empty($relativeRow['position']))
		        $relativeData .= '<li><i class="fa fa-level-up"></i> ' . $relativeRow['position'] . '</li>';
		    
		    if (!empty($relativeRow['company']))
		        $relativeData .= '<li><i class="fa fa-industry"></i> ' . $relativeRow['company'] . '</li>';
		    
		    if (!empty($relativeRow['degree_title']))
		        $relativeData .= '<li><i class="fa fa-graduation-cap"></i> ' . $relativeRow['degree_title'] . '</li>';
		    
		    $relativeData .= '</ul>';
		}
    	$html->setvar('relativeData', $relativeData);
    	$html->parse('relativeData', false);
    }

    (empty($relativeData) && $visitor) ? '' : $html->parse('relatives_block', false);


    ############ POSTED BY ##############
    if($row_user['signup_as'] !== 'self') {

    	$postedByData = '';

		if (!empty($row_user['poster_name']))
		    $postedByData .= '<li><i class="fa fa-user"></i> ' . $row_user['poster_name'] . '</li>';
		
		if (!empty($row_user['poster_phone']))
		    $postedByData .= '<li><i class="fa fa-phone"></i> ' . $row_user['poster_phone'] . '</li>';
		
		if (!empty($row_user['poster_address']))
		    $postedByData .= '<li><i class="fa fa-map-marker"></i> ' . $row_user['poster_address'] . '</li>';

    	$html->setvar('postedByData', $postedByData);
    	// $html->parse('postedByData', false);

    	(empty($postedByData) && $visitor) ? '' : $html->parse('postedByData', false);
    }


    ############ ADDITIONAL INFORMATION ##############
    if($row_user['additional_info']) {

    	$additional_info = '<li>'.$row_user['additional_info'].'</li>';

		$html->setvar('additionInfoData', $additional_info);
	    $html->parse('additionInfoData', false);
	}

    // if empty, visitor will not view Additional Information section.
    (empty($row_user['additional_info']) && $visitor) ? '' : $html->parse('additionalInfo_block', false);



    // main div
    $html->parse('edit_me_or_my_candidate', false);

?>