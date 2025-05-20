<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class InvoiceDate extends Tag
{
    public function __construct($value)
    {
        parent::__construct(3, $value);
    }
}
