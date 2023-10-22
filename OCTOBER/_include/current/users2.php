<?php

	// address
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

    if($unfavorite_address) {
        $html->setvar('favorite_address', $favorite_address);
        $html->parse('favorite_address', false);
    }
    if($unfavorite_address) {
        $html->setvar('unfavorite_address', $unfavorite_address);
        $html->parse('unfavorite_address', false);
    }

    // main div
    $html->parse('edit_me_or_my_candidate', false);

?>