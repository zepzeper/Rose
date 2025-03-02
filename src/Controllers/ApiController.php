<?php

namespace Rose\Controllers;

use Rose\View\TemplateEngine;

class ApiController
{
    protected TemplateEngine $templateEngine;

    public function __construct() {
        $this->templateEngine = new TemplateEngine(__DIR__ . '/../public/views');
    }

    public function index(): string
    {
        return $this->templateEngine->render('home.twig', [
            'title' => 'Home Page',
            'message' => 'Welcome to our site'
        ]);
    }
}
