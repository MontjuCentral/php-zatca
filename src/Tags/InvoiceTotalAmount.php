<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class InvoiceTotalAmount extends Tag
{
    public function __construct($value)
    {
        parent::__construct(4, $value);
    }
}
