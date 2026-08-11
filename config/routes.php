<?php

declare(strict_types=1);

return static function (Router $router): void {
    $router->get('/', 'LandingController@index');

    $router->get('/login', 'AuthController@loginPage', ['guest']);
    $router->post('/login', 'AuthController@login', ['guest', 'csrf']);
    $router->get('/register', 'AuthController@registerPage', ['guest']);
    $router->post('/register', 'AuthController@register', ['guest', 'csrf']);
    $router->get('/forgot-password', 'AuthController@forgotPasswordPage', ['guest']);
    $router->post('/forgot-password', 'AuthController@forgotPassword', ['guest', 'csrf']);
    $router->get('/reset-password/{token}', 'AuthController@resetPasswordPage', ['guest']);
    $router->post('/reset-password', 'AuthController@resetPassword', ['guest', 'csrf']);
    $router->post('/logout', 'AuthController@logout', ['auth', 'csrf']);

    $router->get('/dashboard', 'DashboardController@index', ['auth']);
    $router->get('/templates', 'TemplateController@index', ['auth']);
    $router->get('/resume/builder', 'ResumeController@builder', ['auth']);
    $router->get('/resume/builder/{id}', 'ResumeController@builder', ['auth']);
    $router->get('/resume/{id}/print', 'PdfController@preview', ['auth']);

    $router->get('/account/profile', 'AccountController@profile', ['auth']);
    $router->post('/account/profile', 'AccountController@updateProfile', ['auth', 'csrf']);
    $router->get('/account/security', 'AccountController@security', ['auth']);
    $router->post('/account/password', 'AccountController@updatePassword', ['auth', 'csrf']);
    $router->get('/account/appearance', 'AccountController@appearance', ['auth']);

    $router->get('/api/dashboard', 'DashboardController@data', ['auth']);
    $router->get('/api/resumes', 'ResumeController@indexApi', ['auth']);
    $router->post('/api/resumes', 'ResumeController@createApi', ['auth', 'csrf']);
    $router->get('/api/resumes/{id}', 'ResumeController@showApi', ['auth']);
    $router->put('/api/resumes/{id}', 'ResumeController@updateApi', ['auth', 'csrf']);
    $router->delete('/api/resumes/{id}', 'ResumeController@deleteApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/duplicate', 'ResumeController@duplicateApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/ats', 'ResumeController@atsApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/assistant', 'ResumeController@assistantApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/export', 'PdfController@recordExport', ['auth', 'csrf']);
    $router->get('/api/templates', 'TemplateController@indexApi', ['auth']);
};
