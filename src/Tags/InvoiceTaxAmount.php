<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class InvoiceTaxAmount extends Tag
{
    public function __construct($value)
    {
        parent::__construct(5, $value);
    }
}
