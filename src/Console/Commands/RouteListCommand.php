<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Rose\Routing\RouteCollection;
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
    protected function configure(): void
    {
        $this->setName(self::$defaultName)
             ->setDescription('List all registered routes')
             ->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'Filter routes by method (GET, POST, etc.)')
             ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Filter routes by name')
             ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Filter routes by path pattern')
             ->addOption('controller', 'c', InputOption::VALUE_OPTIONAL, 'Filter routes by controller')
             ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Display routes in a detailed format');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle(): int
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
        $controller = $this->input->getOption('controller');
        $detailed = $this->input->getOption('detailed');
        
        $filteredRoutes = $this->filterRoutes($routes, $method, $name, $path, $controller);

        if (empty($filteredRoutes)) {
            $this->output->writeln('<comment>No routes match the given criteria.</comment>');
            return 0;
        }
        
        $this->displayDetailedRoutes($filteredRoutes);
        
        return 0;
    }
    
    /**
     * Filter the routes by the provided criteria.
     *
     * @param RouteCollection $routes
     * @param string|null $method
     * @param string|null $name
     * @param string|null $path
     * @param string|null $controller
     * @return array
     */
    protected function filterRoutes($routes, $method = null, $name = null, $path = null, $controller = null): array
    {
        $filtered = [];
        
        $routes = $routes->getRoutes();
        foreach ($routes as $route) {
            // Filter by HTTP method
            if ($method && !in_array(strtoupper($method), $route->getMethods())) {
                continue;
            }
            
            // Filter by route name
            if ($name && !str_contains($route->getName() ?? '', $name)) {
                continue;
            }
            
            // Filter by path pattern
            if ($path && !str_contains($route->getUri(), $path)) {
                continue;
            }
            
            // Filter by controller
            if ($controller && !str_contains($route->getController(), $controller)) {
                continue;
            }
            
            $filtered[] = $route;
        }
        
        return $filtered;
    }
    
    /**
     * Display the routes in a table format.
     *
     * @param array $routes
     * @return void
     */
    protected function displayDetailedRoutes($routes): void
    {
        $table = new Table($this->output);
        $table->setStyle('box');
        $table->setHeaders([
            '<fg=white;options=bold>METHOD</>',
            '<fg=white;options=bold>URI</>',
            '<fg=white;options=bold>NAME</>',
            '<fg=white;options=bold>CONTROLLER</>',
            '<fg=white;options=bold>ACTION</>',
            '<fg=white;options=bold>PARAMETERS</>'
        ]);
        
        $rows = [];
        
        foreach ($routes as $route) {
            $methods = implode('|', $route->getMethods());
            $methodsFormatted = $this->colorizeMethod($methods);
            
            $rows[] = [
                $methodsFormatted,
                '<fg=cyan>' . $route->getUri() . '</>',
                $route->getName() ? '<fg=yellow>' . $route->getName() . '</>' : '',
                '<fg=green>' . $this->shortenClassName($route->getController()) . '</>',
                $route->getAction(),
                implode('|', $route->getParameters())
            ];
        }
        
        $table->setRows($rows);
        $table->render();
        
        $this->output->writeln('');
        $this->output->writeln('<info>' . count($rows) . ' routes displayed.</info>');
    }
    
    /**
     * Colorize HTTP methods for better visibility.
     *
     * @param string $method
     * @return string
     */
    protected function colorizeMethod($method): string
    {
        $colors = [
            'GET' => 'green',
            'POST' => 'yellow',
            'PUT' => 'blue',
            'PATCH' => 'cyan',
            'DELETE' => 'red',
            'OPTIONS' => 'magenta',
            'HEAD' => 'white',
        ];
        
        if (strpos($method, '|') !== false) {
            // Handle multiple methods
            $methods = explode('|', $method);
            $colorized = [];
            
            foreach ($methods as $m) {
                $color = $colors[$m] ?? 'default';
                $colorized[] = "<fg=$color>$m</>";
            }
            
            return implode('|', $colorized);
        }
        
        $color = $colors[$method] ?? 'default';
        return "<fg=$color>$method</>";
    }
    
    /**
     * Format the route parameters for display.
     *
     * @param array $parameters
     * @return string
     */
    protected function formatParameters($parameters): string
    {
        if (empty($parameters)) {
            return '';
        }
        
        $formatted = [];
        foreach ($parameters as $name => $value) {
            if (is_numeric($name)) {
                $formatted[] = $value;
            } else {
                $formatted[] = "$name: $value";
            }
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Format the route middleware for display.
     *
     * @param array $middleware
     * @return string
     */
    protected function formatMiddleware($middleware): string
    {
        if (empty($middleware)) {
            return '';
        }
        
        return implode(', ', array_map(function ($m) {
            return is_string($m) ? $m : get_class($m);
        }, $middleware));
    }
    
    /**
     * Shorten a class name by removing the namespace.
     *
     * @param string $className
     * @return string
     */
    protected function shortenClassName($className): string
    {
        if (str_contains($className, '\\')) {
            $parts = explode('\\', $className);
            return end($parts);
        }
        
        return $className;
    }
}
