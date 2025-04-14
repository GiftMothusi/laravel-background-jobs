<?php

namespace App\Exceptions;

use Exception;

class BackgroundJobException extends Exception
{
    /**
     * The class name.
     *
     * @var string
     */
    protected $class;

    /**
     * The method name.
     *
     * @var string
     */
    protected $method;

    /**
     * The parameters.
     *
     * @var array
     */
    protected $params;

    /**
     * Create a new background job exception.
     *
     * @param string $message
     * @param string $class
     * @param string $method
     * @param array $params
     * @param int $code
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct(
        string $message,
        string $class,
        string $method,
        array $params = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * Get the class name.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the parameters.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
