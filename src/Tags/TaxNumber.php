<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class TaxNumber extends Tag
{
    public function __construct($value)
    {
        parent::__construct(2, $value);
    }
}
