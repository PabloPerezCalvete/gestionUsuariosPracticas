<?php

namespace App\Controller;

use App\Entity\Usuario1;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; // Correcto para 6.4
use Symfony\Component\Security\Http\Attribute\IsGranted;

class Security2faController extends AbstractController
{
    #[Route('/perfil/2fa/activar', name: 'app_2fa_activar')]
    #[IsGranted('ROLE_USER')]
    public function activar2fa(
        EntityManagerInterface $em, 
        TotpAuthenticatorInterface $totpAuthenticator
    ): Response {
        /** @var Usuario1 $user */
        $user = $this->getUser();

        if (!$user->getTotpSecret()) {
            $secret = $totpAuthenticator->generateSecret();
            $user->setTotpSecret($secret);
            $em->flush();
        }

        $qrContent = $totpAuthenticator->getQRContent($user);
        $qrCode = new QrCode($qrContent);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode, null, null, ['size' => 250, 'margin' => 10]);
        $qrCodeBase64 = $result->getDataUri();

        return $this->render('security2fa/activar.html.twig', [
            'qrCode' => $qrCodeBase64,
            'secret' => $user->getTotpSecret()
        ]);
    }

   
    #[Route('/perfil/2fa/desactivar', name: 'app_2fa_desactivar')]
    #[IsGranted('ROLE_USER')]
    public function desactivar2fa(EntityManagerInterface $em): Response
    {
        /** @var Usuario1 $user */
        $user = $this->getUser();

        if ($user) {
            $user->setTotpSecret(null);
            $em->flush();
            $this->addFlash('success', 'El 2FA ha sido desactivado.');
        }

        return $this->redirectToRoute('app_user_profile');
    }
}