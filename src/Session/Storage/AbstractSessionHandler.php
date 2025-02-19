<?php

namespace Rose\Session\Storage;

use Rose\Contracts\Session\Storage as StorageContract;
use Rose\Encryption\Encryption;
use Rose\Roots\Application;

abstract class AbstractSessionHandler implements StorageContract
{
    protected array $options;

    public function __construct(
        protected Application $app,
        protected Encryption $encryptor,
        array $options = []
    ) {
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    protected function getDefaultOptions(): array
    {
        return [
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'http_only' => true,
        ];
    }

    protected function serialize($value): string
    {
        return $this->encryptor->encrypt(serialize($value));
    }

    protected function unserialize(string $value)
    {
        return unserialize($this->encryptor->decrypt($value));
    }

}
