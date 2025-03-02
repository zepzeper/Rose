<?php

namespace Rose\Controllers;

class Index
{
    public function index(): string
    {
        // Start output buffering to capture view content
        ob_start();

        // Include the view file
        include __DIR__ . '/../../public/views/index.php';

        // Get the buffered content and clean the buffer
        return ob_get_clean();
    }

    public function test(): string
    {
        return "hello from test";
    }
}

// TODO: Create template engine instance
/*$template = new TemplateEngine(__DIR__ . '/../../resources/views');*/
/**/
/*// Render the view with data*/
/*return $template->render('test.php', [*/
/*    'title' => 'My Framework',*/
/*    // Add more data for the view here*/
/*]);*/
