<?php

namespace App\Controller;

use App\Entity\Grupo;
use App\Form\GrupoType;
use App\Repository\GrupoRepository;
use App\Entity\Usuario1;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/grupo')]
class GrupoController extends AbstractController
{
    #[Route('/', name: 'app_grupo_index', methods: ['GET'])]
    public function index(GrupoRepository $grupoRepository): Response
    {
        return $this->render('grupo/index.html.twig', [
            'grupos' => $grupoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_grupo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $grupo = new Grupo();
        $form = $this->createForm(GrupoType::class, $grupo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Asignar usuarios seleccionados
            foreach ($form->get('users')->getData() as $user) {
                $user->setGrupo($grupo);
            }

            $entityManager->persist($grupo);
            $entityManager->flush();

            return $this->redirectToRoute('app_grupo_index');
        }

        return $this->render('grupo/new.html.twig', [
            'grupo' => $grupo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_grupo_show', methods: ['GET'])]
    public function show(Grupo $grupo): Response
    {
        return $this->render('grupo/show.html.twig', [
            'grupo' => $grupo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_grupo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Grupo $grupo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GrupoType::class, $grupo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Quitar grupo a usuarios actuales
            foreach ($grupo->getUsers() as $user) {
                $user->setGrupo(null);
            }

            // Asignar grupo a los seleccionados
            foreach ($form->get('users')->getData() as $user) {
                $user->setGrupo($grupo);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_grupo_index');
        }

        return $this->render('grupo/edit.html.twig', [
            'grupo' => $grupo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_grupo_delete', methods: ['POST'])]
    public function delete(Request $request, Grupo $grupo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$grupo->getId(), $request->request->get('_token'))) {

            // Quitar grupo a usuarios antes de borrar
            foreach ($grupo->getUsers() as $user) {
                $user->setGrupo(null);
            }

            $entityManager->remove($grupo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_grupo_index');
    }
}