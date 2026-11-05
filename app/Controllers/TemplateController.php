<?php

declare(strict_types=1);

final class TemplateController extends Controller
{
    public function __construct(private readonly TemplateService $templates = new TemplateService())
    {
    }

    public function index(Request $request): Response
    {
        return $this->view('templates/list', [
            'title' => 'CV templates',
            'templates' => $this->templates->all(),
            'user' => Auth::user(),
        ]);
    }

    public function indexApi(Request $request): Response
    {
        return $this->success(['templates' => $this->templates->all()]);
    }
}
