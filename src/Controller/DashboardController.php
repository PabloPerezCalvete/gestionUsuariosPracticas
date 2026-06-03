<?php

namespace App\Controller;

use App\Entity\Usuario1;
use App\Entity\Grupo;
use App\Entity\Equipo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        // Contar Total de Usuarios
        $totalUsuarios = $em->getRepository(Usuario1::class)
            ->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        // Contar Total de Grupos
        $totalGrupos = $em->getRepository(Grupo::class)
            ->createQueryBuilder('g')
            ->select('count(g.id)')
            ->getQuery()
            ->getSingleScalarResult();
        // Contar Total de Equipos
        $totalEquipos = $em->getRepository(Equipo::class)
            ->createQueryBuilder('e')
            ->select('count(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('dashboard/index.html.twig', [
            'total_usuarios' => $totalUsuarios,
            'total_grupos'   => $totalGrupos,
            'total_equipos'  => $totalEquipos,
        ]);
    }
}