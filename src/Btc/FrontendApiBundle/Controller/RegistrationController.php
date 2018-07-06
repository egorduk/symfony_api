<?php

namespace Btc\InvitationBundle\Controller;

use Btc\UserBundle\Events\TranslatableUserActivityEvent;
use Btc\UserBundle\Events\AccountActivityEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Btc\UserBundle\Form\Type\RegistrationFormType;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\FeeSet;
use Btc\CoreBundle\Entity\UserFeeSet;

class RegistrationController extends Controller
{
    /**
     * @Route("/register/{hash}")
     * @Method({"GET", "POST"})
     * @Template("BtcInvitationBundle:Registration:register.html.twig")
     */
    public function registerAction(Request $request, $hash)
    {
        if (!$promotional = $this->returnPromotional($hash)) {
            return $this->redirect($this->generateUrl('btc_user_registration_register'));
        }
        if ($promotional->isRegistered()) {
            return $this->redirect($this->generateUrl('btc_user_registration_register'));
        }
        $registrationService = $this->get('core.user_registration_service');
        $countries = $this->get('countries')->findAllChoosable();

        $form = $this->createForm(new RegistrationFormType($countries), $user = new User());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $registrationService->initUser($user);
            $registrationService->encryptPassword($user);
            $registrationService->createWallets($em, $user);
            //assign promotional fee set here
            $this->assignPromotionalFeeSet($user);
            $promotional->setRegistered(1);
            $em->persist($promotional);

            $em->persist($user);
            $em->flush();

            $this->get('event_dispatcher')->dispatch(
                AccountActivityEvents::REGISTRATION_COMPLETED,
                new TranslatableUserActivityEvent($user, $request, ['%email%' => $user->getEmail()])
            );

            return $this->redirect($this->generateUrl('btc_user_security_login'));
        }

        return ['form' => $form->createView(), 'hash' => $hash];
    }

    private function returnPromotional($hash)
    {
        $decodedHash = explode(' ', base64_decode($hash));
        if (count($decodedHash) !== 2) {
            return false;
        }
        list($email, $hash) = $decodedHash;
        $em = $this->getDoctrine()->getManager();
        $promotional = $em->getRepository('BtcInvitationBundle:PromotionalEmail')
            ->findOneBy(['email' => $email, 'hash' => $hash]);

        return $promotional;
    }

    private function assignPromotionalFeeSet(User $user)
    {
        $em = $this->getDoctrine()->getManager();
        $defaultFeeSet = $em->getRepository('BtcCoreBundle:FeeSet')
            ->findOneBy(['default' => 1]);
        $defaultFeeSet = $this->revaluateVolumeBasedSet($defaultFeeSet);
        $feeSet = $em->getRepository('BtcCoreBundle:FeeSet')
            ->findOneBy(['name' => 'Starter (50%)']);
        $userFeeSet = new UserFeeSet();
        $userFeeSet->setUser($user);
        $userFeeSet->setFeeSet($feeSet);
        $userFeeSet->setFallbackFeeSet($defaultFeeSet);
        $userFeeSet->setExpiresAt(new \DateTime('+3 months'));
        $em->persist($userFeeSet);
    }

    private function revaluateVolumeBasedSet(FeeSet $feeSet)
    {
        if (!$feeSet->isStandard()) {
            return $feeSet;
        }
        foreach ($feeSet->getChildren() as $child) {
            if (floatval($child->getRule()) === 0.00) {
                return $child;
            }
        }
    }
}
