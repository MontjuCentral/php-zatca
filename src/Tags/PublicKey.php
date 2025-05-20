<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class PublicKey extends Tag
{
    public function __construct($value)
    {
        parent::__construct(8, $value);
    }
}
