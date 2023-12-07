<?php

	if(!function_exists('dd')) {
		function dd($array='Debug!') {
		    echo '<pre>';print_r($array);echo '</pre>';exit();
		}
	}
	if(!function_exists('level_of_education')) {
		function level_of_education() {
		    return [
		    	'3' => 'Secondary',
		    	'4' => 'Higher Secondary',
		    	'5' => 'Diploma',
		    	'6' => 'Bachelor/Honors',
		    	'7' => 'Masters',
		    	'8' => 'PhD (Doctor of Philosophy)',
		    	'9' => 'Other'
		    ];
		}
	}
	if(!function_exists('get_level_of_education')) {
		function get_level_of_education($key) {
		    $value = level_of_education();
		    if (array_key_exists($key, $value)) 
				return $value[$key];
		}
	}
	if(!function_exists('sendemail')) {
		function sendemail($email, $message) {
		    if(!empty($email) && !empty($message)) {
		    	
		    	$ver_headers = "";
				$ver_headers .= "From: " . Common::getOption('title', 'main') . "<" . Common::getOption('info_mail', 'main') . ">" . "\n";
				$ver_headers .= "Reply-To: " . "<" . Common::getOption('info_mail', 'main') . ">" . "\n";
				$ver_headers .= "Return-Path: " . "<" . Common::getOption('info_mail', 'main') . ">" . "\n";
				$ver_headers .= "Message-ID: <" . time() . "-" . Common::getOption('info_mail', 'main') . ">" . "\n";
				$ver_headers .= "X-Mailer: PHP v" . phpversion() . "\n";
				$ver_headers .= 'Date: ' . date("r") . "\n";
				$ver_headers .= 'MIME-Version: 1.0' . "\n";
				$ver_headers .= "Content-Type: text/html; charset=utf-8" . "\n";
				$ver_headers .= "Content-Transfer-Encoding: 8bit" . "\n";
				
				// mail($email, 'Please verify your email for'.Common::getOption('title', 'main'), $message, $ver_headers);
				return 'success';
		    }
		}
	}
	if(!function_exists('sendsms')) {
		function sendsms($phone_number, $message) {
			return;
		    if(!empty($phone_number) && !empty($message) && strlen($phone_number) == 13) {
		    	$url = "http://bulksmsbd.net/api/smsapi";

			    $data = [
			        "api_key"   => "7C1RCtQxgwuuwymQIfZv",
			        "senderid"  => "8809617613549",
			        "number"    => "$phone_number",
			        "message"   => $message
			    ];

			    $ch = curl_init();
			    curl_setopt($ch, CURLOPT_URL, $url);
			    curl_setopt($ch, CURLOPT_POST, 1);
			    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			    $response = curl_exec($ch);
			    curl_close($ch);
			    return $response;
		    }
		}
	}