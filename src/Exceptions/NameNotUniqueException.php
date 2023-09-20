<?php

namespace IODigital\ABlockLaravel\Exceptions;

use Illuminate\Http\Response;
use IODigital\ABlockPHP\Exceptions\ApplicationException;

class NameNotUniqueException extends ApplicationException
{
    public function status(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function help(): string
    {
        return 'Please use a unique name';
    }

    public function error(): string
    {
        return 'Name is not unique for this entity for this owner';
    }
}
