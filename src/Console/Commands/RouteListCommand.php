<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Rose\Routing\Router;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends BaseCommand
{
    protected static string $defaultName = 'route:list';

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('List all registered routes')
             ->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'Filter routes by method (GET, POST, etc.)')
             ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Filter routes by name')
             ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Filter routes by path pattern');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle()
    {
        $router = $this->app->make(Router::class);
        $routes = $router->getRoutes();
        
        if (empty($routes)) {
            $this->output->writeln('<comment>No routes registered.</comment>');
            return 0;
        }
        
        // Apply filters if provided
        $method = $this->input->getOption('method');
        $name = $this->input->getOption('name');
        $path = $this->input->getOption('path');
        
        $filteredRoutes = $this->filterRoutes($routes, $method, $name, $path);
        
        if (empty($filteredRoutes)) {
            $this->output->writeln('<comment>No routes match the given criteria.</comment>');
            return 0;
        }
        
        $this->displayRoutes($filteredRoutes);
        
        return 0;
    }
    
    /**
     * Filter the routes by the provided criteria.
     *
     * @param array $routes
     * @param string|null $method
     * @param string|null $name
     * @param string|null $path
     * @return array
     */
    protected function filterRoutes($routes, $method = null, $name = null, $path = null)
    {
        $filtered = [];
        
        foreach ($routes as $route) {
            // Filter by HTTP method
            if ($method && !in_array(strtoupper($method), $route->methods())) {
                continue;
            }
            
            // Filter by route name
            if ($name && !str_contains($route->getName() ?? '', $name)) {
                continue;
            }
            
            // Filter by path pattern
            if ($path && !str_contains($route->uri(), $path)) {
                continue;
            }
            
            $filtered[] = $route;
        }
        
        return $filtered;
    }
    
    /**
     * Display the routes in a table.
     *
     * @param array $routes
     * @return void
     */
    protected function displayRoutes($routes)
    {
        $table = new Table($this->output);
        $table->setHeaders(['Method', 'URI', 'Name', 'Action', 'Middleware']);
        
        $rows = [];
        
        foreach ($routes as $route) {
            $rows[] = [
                implode('|', $route->methods()),
                $route->uri(),
                $route->getName() ?: '',
                $this->formatAction($route->getAction()),
                $this->formatMiddleware($route->middleware()),
            ];
        }
        
        $table->setRows($rows);
        $table->render();
        
        $this->output->writeln('');
        $this->output->writeln('<info>' . count($rows) . ' routes displayed.</info>');
    }
    
    /**
     * Format the route action for display.
     *
     * @param mixed $action
     * @return string
     */
    protected function formatAction($action)
    {
        if (is_string($action['uses'])) {
            return $action['uses'];
        }
        
        if (is_callable($action['uses']) && isset($action['controller'])) {
            return $action['controller'];
        }
        
        return 'Closure';
    }
    
    /**
     * Format the route middleware for display.
     *
     * @param array $middleware
     * @return string
     */
    protected function formatMiddleware($middleware)
    {
        if (empty($middleware)) {
            return '';
        }
        
        return implode(', ', array_map(function ($m) {
            return is_string($m) ? $m : get_class($m);
        }, $middleware));
    }
}
