<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class InvoiceDigitalSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(7, $value);
    }
}
