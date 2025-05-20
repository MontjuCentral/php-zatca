<?php

namespace Montju\Zatca\Tags;

use Montju\Zatca\Tag;

class CertificateSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(9, $value);
    }
}
