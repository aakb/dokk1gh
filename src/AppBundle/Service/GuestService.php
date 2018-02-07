<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

use AppBundle\Entity\Guest;

class GuestService
{
    public function isValid(Guest $guest)
    {
        return true;
    }

    public function canRequestCode(Guest $guest)
    {
        return true;
    }
}
