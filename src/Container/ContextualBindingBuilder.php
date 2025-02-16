<?php

namespace Rose\Container;

class ContextualBindingBuilder
{
    protected array $needs = [];

    public function __construct(
        protected Container $container,
        protected array $concretes
    ) {}

    public function needs(string $abstract): self
    {
        $this->needs[] = $abstract;
        return $this;
    }

    public function give(mixed $implementation): void
    {
        foreach ($this->concretes as $concrete) {
            foreach ($this->needs as $need) {
                $this->container->contextual[$concrete][$need] = $implementation;
            }
        }
    }
}
