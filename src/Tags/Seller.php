<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class Seller extends Tag
{
    public function __construct($value)
    {
        parent::__construct(1, $value);
    }
}
