<?php

return [

    '/' => 'LandingController@index',

    '/login' => 'AuthController@loginPage',

    '/logout' => 'AuthController@logout',

    '/dashboard' => 'DashboardController@index',

    '/resume/builder' => 'ResumeController@builder',

    '/templates' => 'TemplateController@index',

];
