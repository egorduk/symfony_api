<?php

namespace Btc\TransferBundle\Controller;

trait TransferAccessControllerExtension
{
    /**
     * Gives redirect response in case transfer needs extra verification
     * or null if it is ok
     *
     * @return \Symfony\Component\HttpFoundation\Response || null
     */
    private function redirectResponseIfNoTransferAccess(\Btc\CoreBundle\Entity\Bank $bank)
    {
        $user = $this->getUser();
        switch ($bank->getPaymentMethod()) {
            case "wire":
                if (!$user->isVerified()) {
                    $this->flashFailure("access_denied.wire");
                    return new \Symfony\Component\HttpFoundation\RedirectResponse(
                        $this->generateUrl('btc_account_verify_verify')
                    );
                }
                break;
        }

        return null;
    }
}
