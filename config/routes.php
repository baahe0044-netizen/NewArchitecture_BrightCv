<?php

declare(strict_types=1);

return static function (Router $router): void {
    $router->get('/', 'LandingController@index');
    $router->get('/manifest.webmanifest', 'PwaController@manifest');

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
    // No 'auth' here on purpose: a first-time visitor reaches this with no
    // session at all, and ResumeController@builder is what signs them in as
    // a guest and creates their first CV. Every other resume route stays
    // auth-gated as before -- by the time a guest is redirected to one, they
    // already have a real (if not yet claimed) session.
    $router->get('/resume/builder', 'ResumeController@builder');
    $router->get('/resume/builder/{id}', 'ResumeController@builder', ['auth']);
    $router->get('/resume/{id}/print', 'PdfController@preview', ['auth']);
    $router->get('/rewards', 'RewardsController@index', ['auth']);

    $router->get('/account/profile', 'AccountController@profile', ['auth']);
    $router->post('/account/profile', 'AccountController@updateProfile', ['auth', 'csrf']);
    $router->get('/account/security', 'AccountController@security', ['auth']);
    $router->post('/account/password', 'AccountController@updatePassword', ['auth', 'csrf']);
    $router->get('/account/appearance', 'AccountController@appearance', ['auth']);
    $router->post('/account/gamification', 'AccountController@updateGamification', ['auth', 'csrf']);
    $router->post('/api/account/claim', 'AccountController@claimApi', ['auth', 'csrf']);
    $router->post('/api/account/login-claim', 'AccountController@loginClaimApi', ['auth', 'csrf']);

    $router->get('/api/dashboard', 'DashboardController@data', ['auth']);
    $router->get('/api/resumes', 'ResumeController@indexApi', ['auth']);
    $router->post('/api/resumes', 'ResumeController@createApi', ['auth', 'csrf']);
    $router->get('/api/resumes/{id}', 'ResumeController@showApi', ['auth']);
    $router->put('/api/resumes/{id}', 'ResumeController@updateApi', ['auth', 'csrf']);
    $router->delete('/api/resumes/{id}', 'ResumeController@deleteApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/duplicate', 'ResumeController@duplicateApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/import', 'ImportController@parseApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/ats', 'ResumeController@atsApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/assistant', 'ResumeController@assistantApi', ['auth', 'csrf']);
    $router->post('/api/resumes/{id}/export', 'PdfController@recordExport', ['auth', 'csrf']);
    $router->get('/api/templates', 'TemplateController@indexApi', ['auth']);
};
