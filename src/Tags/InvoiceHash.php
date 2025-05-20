<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class InvoiceHash extends Tag
{
    public function __construct($value)
    {
        parent::__construct(6, $value);
    }
}
