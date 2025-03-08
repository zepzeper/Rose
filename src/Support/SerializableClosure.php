<?php

namespace Rose\Support;

use Closure;
use Opis\Closure\Serializer;
use ReflectionFunction;

class SerializableClosure
{
    /**
     * @var Closure
     */
    protected $closure;
    
    /**
     * @var string|null
     */
    protected ?string $serialized = null;
    
    /**
     * Create a new serializable closure instance.
     *
     * @param Closure $closure
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }
    
    /**
     * Get the closure.
     *
     * @return Closure
     */
    public function getClosure(): Closure
    {
        return $this->closure;
    }
    
    /**
     * Serialize the closure.
     *
     * @return string[]
     */
    public function __sleep(): array
    {
        if (class_exists(Serializer::class)) {
            // Use SuperClosure if available
            $serializer = new Serializer();
            $this->serialized = $serializer->serialize($this->closure);
        } else {
            // Simple serialization method (limited, no support for use/bound variables)
            $reflection = new ReflectionFunction($this->closure);
            $this->serialized = serialize([
                'code' => $this->getClosureCode($reflection),
                'variables' => $this->getClosureVariables($reflection),
            ]);
        }
        
        return ['serialized'];
    }
    
    /**
     * Unserialize the closure.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        if (class_exists(Serializer::class)) {
            // Use SuperClosure if available
            $serializer = new Serializer();
            $this->closure = $serializer->unserialize($this->serialized);
        } else {
            // Simple unserialization method
            $data = unserialize($this->serialized);
            
            // Extract variables into the current scope
            foreach ($data['variables'] as $name => $value) {
                $$name = $value;
            }
            
            // Recreate the closure
            $this->closure = eval("return {$data['code']};");
        }
        
        $this->serialized = null;
    }
    
    /**
     * Get the closure code.
     *
     * @param ReflectionFunction $reflection
     * @return string
     */
    protected function getClosureCode(ReflectionFunction $reflection): string
    {
        $file = file($reflection->getFileName());
        $start = $reflection->getStartLine() - 1;
        $end = $reflection->getEndLine() - 1;
        $code = '';
        
        for ($i = $start; $i <= $end; $i++) {
            $code .= $file[$i];
        }
        
        return $code;
    }
    
    /**
     * Get the closure variables.
     *
     * @param ReflectionFunction $reflection
     * @return array
     */
    protected function getClosureVariables(ReflectionFunction $reflection): array
    {
        $variables = [];
        
        $staticVariables = $reflection->getStaticVariables();
        foreach ($staticVariables as $name => $value) {
            $variables[$name] = $value;
        }
        
        return $variables;
    }
    
    /**
     * Invoke the closure.
     *
     * @param mixed ...$args
     * @return mixed
     */
    public function __invoke(...$args)
    {
        return call_user_func_array($this->closure, $args);
    }
}
