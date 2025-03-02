<?php
namespace Rose\Controllers;

use Framework\View\TemplateEngine;
use Rose\View\TemplateEngine as RoseTemplateEngine;

class HomeController
{
    protected $templateEngine;
    
    public function __construct()
    {
        $this->templateEngine = new RoseTemplateEngine(__DIR__ . '/../../public/views');
    }
    
    public function index(): string
    {
        return $this->templateEngine->render('home.twig', [
            'title' => 'Home Page',
            'message' => 'Welcome to our site'
        ]);
    }

    /**
     * Demo endpoint for the landing page
     */
    public function demo(): string
    {
        // Simulate a short delay to show the loading indicator
        usleep(500000); // 500ms delay

        // Get some demo data
        $data = [
            [
                'id' => 1,
                'name' => 'Server-side rendering',
                'description' => 'HTML generated on the server'
            ],
            [
                'id' => 2,
                'name' => 'Hypermedia-driven',
                'description' => 'Uses HTML as the application state'
            ],
            [
                'id' => 3,
                'name' => 'Progressive enhancement',
                'description' => 'Works with or without JavaScript'
            ]
        ];

        // Return HTML fragment for HTMX to inject
        $html = '<div class="space-y-4">';
        $html .= '<h3 class="text-lg font-medium text-gray-900">HTMX Features</h3>';
        $html .= '<ul class="space-y-2">';

        foreach ($data as $item) {
            $html .= '<li class="bg-white p-4 rounded-lg shadow border border-gray-100">';
            $html .= '<div class="font-medium">' . htmlspecialchars($item['name']) . '</div>';
            $html .= '<div class="text-gray-600 text-sm">' . htmlspecialchars($item['description']) . '</div>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

}
