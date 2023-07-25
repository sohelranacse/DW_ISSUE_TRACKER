<?php

    // MODIFIED

    function parseLastStep(&$html)
    {
        $html->setvar('user_name', get_session('j_name'));
        $html->setvar('user_phone', get_session('j_phone'));
        $html->setvar('user_mail', get_session('j_mail'));
        $cityId = get_session('j_city');
        $html->setvar('user_city', l(Common::getLocationTitle('city', $cityId)));
        $month = get_session('j_month');
        $day = get_session('j_day');
        $year = get_session('j_year');
        $html->setvar('user_age', User::getAge($year, $month, $day));
        Common::parseCaptcha($html);
        $html->parse('photo', false);
    }