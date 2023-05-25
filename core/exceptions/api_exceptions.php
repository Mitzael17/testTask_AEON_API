<?php

namespace core\exceptions;

class api_exceptions extends \Exception
{

    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);

        exit(json_encode([
            'status' => 'error',
            'message' => $message
        ]));

    }

}