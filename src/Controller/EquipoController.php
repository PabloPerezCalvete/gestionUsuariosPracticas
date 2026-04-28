<?php

namespace App\Controller;

use App\Entity\Equipo;
use App\Form\EquipoType;
use App\Repository\EquipoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/equipos')]
class EquipoController extends AbstractController
{
    /**
     * Lista todos los equipos y muestra el formulario de alta
     */
    #[Route('/', name: 'app_equipo_index', methods: ['GET', 'POST'])]
    public function index(EquipoRepository $repo, Request $request, EntityManagerInterface $em): Response
    {
        $equipo = new Equipo();
        $form = $this->createForm(EquipoType::class, $equipo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($equipo);
            $em->flush();
            $this->addFlash('success', 'Equipo registrado con éxito.');
            return $this->redirectToRoute('app_equipo_index');
        }

        return $this->render('equipo/index.html.twig', [
            'equipos' => $repo->findAll(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulario de edición
     */
    #[Route('/{id}/editar', name: 'app_equipo_edit', methods: ['GET', 'POST'])]
    public function edit(Equipo $equipo, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EquipoType::class, $equipo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Equipo actualizado correctamente.');
            return $this->redirectToRoute('app_equipo_index');
        }

        return $this->render('equipo/edit.html.twig', [
            'equipo' => $equipo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Acción de borrar (La que te faltaba)
     */
    #[Route('/{id}/borrar', name: 'app_equipo_delete', methods: ['POST'])]
    public function delete(Request $request, Equipo $equipo, EntityManagerInterface $em): Response
    {
        // Verificamos el token CSRF que viene del formulario que me acabas de pasar
        if ($this->isCsrfTokenValid('delete' . $equipo->getId(), $request->request->get('_token'))) {
            $em->remove($equipo);
            $em->flush();
            $this->addFlash('success', 'Equipo eliminado del inventario.');
        }

        return $this->redirectToRoute('app_equipo_index');
    }
}