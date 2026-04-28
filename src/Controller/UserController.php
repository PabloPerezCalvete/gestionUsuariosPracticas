<?php
namespace App\Controller;

use App\Entity\Usuario1;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/usuarios')]
class UserController extends AbstractController
{
    // LISTADO Y ALTA (Create)
    #[Route('/', name: 'app_user_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new Usuario1();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/index.html.twig', [
            'users' => $em->getRepository(Usuario1::class)->findAll(),
            'form' => $form->createView(),
        ]);
    }

    // MODIFICACIÓN (Update)
    #[Route('/{id}/editar', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Usuario1 $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            }
            $em->flush();
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView()]);
    }

    // BAJA (Delete)
    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, Usuario1 $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
        }
        return $this->redirectToRoute('app_user_index');
    }
}