<?php

require_once __DIR__ . '/../Core/Controller.php';

class DashboardController extends Controller
{
    public function index()
    {
        $this->view('dashboard/index');
    }
}
