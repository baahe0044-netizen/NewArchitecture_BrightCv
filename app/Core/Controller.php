<?php

require_once __DIR__ . '/View.php';

class Controller
{
    protected function view($view, $data = [])
    {
        View::render($view, $data);
    }
}
