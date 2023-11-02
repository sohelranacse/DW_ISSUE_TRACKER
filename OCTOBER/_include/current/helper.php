<?php

	if(!function_exists('dd')) {
		function dd($array='Debug!') {
		    echo '<pre>';print_r($array);echo '</pre>';exit();
		}
	}
	if(!function_exists('level_of_education')) {
		function level_of_education() {
		    return [
		    	'1' => 'PSC/5 pass',
		    	'2' => 'JSC/JDC/8 pass',
		    	'3' => 'Secondary',
		    	'4' => 'Higher Secondary',
		    	'5' => 'Diploma',
		    	'6' => 'Bachelor/Honors',
		    	'7' => 'Masters',
		    	'8' => 'PhD (Doctor of Philosophy)'
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