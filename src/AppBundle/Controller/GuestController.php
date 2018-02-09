<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Guest;

class GuestController extends AdminController
{
    public function createNewGuestEntity()
    {
        $guest = new Guest();
        $guest->setEnabled(true);

        return $guest;
    }

    public function showAppAction()
    {
        $id = $this->request->query->get('id');
        $guest = $this->em->getRepository(Guest::class)->find($id);

        return $this->redirectToRoute('app_main', [
            'guest' => $guest->getId(),
        ]);
    }
}
