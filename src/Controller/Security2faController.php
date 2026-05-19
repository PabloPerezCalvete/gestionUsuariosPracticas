<?php

namespace App\Controller;

use App\Entity\Usuario1;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class Security2faController extends AbstractController
{
    #[Route('/perfil/2fa/activar', name: 'app_2fa_activar')]
    #[IsGranted('ROLE_USER')] // Solo usuarios logueados pueden entrar
    public function activar2fa(
        EntityManagerInterface $em, 
        TotpAuthenticatorInterface $totpAuthenticator
    ): Response {
        /** @var Usuario1 $user */
        $user = $this->getUser();

        // 1. Si el usuario no tiene clave secreta TOTP, se la generamos automáticamente
        if (!$user->getTotpSecret()) {
            $secret = $totpAuthenticator->generateSecret();
            $user->setTotpSecret($secret);
            $em->flush();
        }

        // 2. Generamos la URL con los datos que leerá la app móvil (Google Authenticator)
        $qrContent = $totpAuthenticator->getQRContent($user);

        // 3. Instanciamos el QrCode con la nueva sintaxis (usando new)
        $qrCode = new QrCode($qrContent);
        
        // 4. Configuramos las opciones de renderizado dentro del PngWriter
        $writer = new PngWriter();
        $result = $writer->write(
            $qrCode,
            null, // Logo (null para ninguno)
            null, // Etiqueta de texto inferior (null para ninguna)
            [
                'size' => 250,
                'margin' => 10
            ]
        );

        // 5. Convertimos la imagen generada a Base64 para incrustarla en el HTML
        $qrCodeBase64 = $result->getDataUri();

        return $this->render('security2fa/activar.html.twig', [
            'qrCode' => $qrCodeBase64,
            'secret' => $user->getTotpSecret()
        ]);
    }
}