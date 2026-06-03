<?php

namespace App\Controller;

use App\Entity\Grupo;
use App\Form\GrupoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

// 🌟 CORRECCIÓN CRÍTICA: Soporta tanto enlaces en singular como en plural de forma nativa
#[Route(['/grupo', '/grupos'])]
class GrupoController extends AbstractController
{
    /**
     * Lista todos los grupos (La tabla nacerá vacía para delegar en DataTables AJAX)
     */
    #[Route('/', name: 'app_grupo_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $grupo = new Grupo();
        $form = $this->createForm(GrupoType::class, $grupo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($grupo);
            $em->flush();
            $this->addFlash('success', 'Grupo creado con éxito.');
            return $this->redirectToRoute('app_grupo_index');
        }

        return $this->render('grupo/index.html.twig', [
            // ❌ Eliminada la carga total de grupos para proteger el rendimiento del servidor
            'form' => $form->createView(),
        ]);
    }

    /**
     * 🌟 SERVICIO WEB PARA DATATABLES SERVER-SIDE
     */
    #[Route('/server-side-data', name: 'app_grupo_server_side', methods: ['GET', 'POST'])]
    public function serverSideData(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $draw = intval($request->get('draw'));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 10));
        $searchArray = $request->get('search');
        $searchValue = $searchArray['value'] ?? '';

        $repository = $em->getRepository(Grupo::class);

        // 1. Cuenta total de registros existentes sin aplicar ningún filtro
        $totalRecords = $repository->createQueryBuilder('g')
            ->select('count(g.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // 2. Query independiente para contar registros que coinciden con la búsqueda
        $qbCount = $repository->createQueryBuilder('g')->select('count(g.id)');
        if (!empty($searchValue)) {
            $qbCount->where('g.nombre LIKE :search OR g.descripcion LIKE :search')
                    ->setParameter('search', '%' . $searchValue . '%');
        }
        $totalFiltered = $qbCount->getQuery()->getSingleScalarResult();

        // 3. Query principal para extraer los registros paginados del segmento solicitado
        $qb = $repository->createQueryBuilder('g');
        if (!empty($searchValue)) {
            $qb->where('g.nombre LIKE :search OR g.descripcion LIKE :search')
               ->setParameter('search', '%' . $searchValue . '%');
        }
        $qb->setFirstResult($start)->setMaxResults($length);
        $grupos = $qb->getQuery()->getResult();

        // 4. Transformar los objetos en la estructura JSON plana que espera DataTables
        $data = [];
        foreach ($grupos as $g) {
            $urlVer = $this->generateUrl('app_grupo_show', ['id' => $g->getId()]);
            $urlEditar = $this->generateUrl('app_grupo_edit', ['id' => $g->getId()]);

            // Mapeamos de forma segura la relación de usuarios vinculados (ManyToMany)
            $usuariosBadges = '<div class="d-flex flex-wrap gap-1">';
            if (count($g->getUsers()) > 0) {
                foreach ($g->getUsers() as $usuario) {
                    $usuariosBadges .= '<span class="badge bg-info text-dark">' . htmlspecialchars($usuario->getEmail()) . '</span>';
                }
            } else {
                $usuariosBadges .= '<span class="text-muted fst-italic">Sin usuarios</span>';
            }
            $usuariosBadges .= '</div>';

            // Estructuración de botones de fila con comillas externas simples seguras
            $botonesAccion = '<div class="d-flex gap-1">' .
                '<a href="' . $urlVer . '" class="btn btn-info btn-sm">Ver</a>' .
                '<a href="' . $urlEditar . '" class="btn btn-warning btn-sm">Editar</a>' .
            '</div>';

            $data[] = [
                $g->getId(),
                htmlspecialchars($g->getNombre()),
                htmlspecialchars($g->getDescripcion() ?? ''),
                $usuariosBadges,
                $botonesAccion
            ];
        }

        return new JsonResponse([
            "draw" => $draw,
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ]);
    }

    /**
     * Muestra el detalle de un grupo específico
     */
    #[Route('/{id}', name: 'app_grupo_show', methods: ['GET'])]
    public function show(Grupo $grupo): Response
    {
        return $this->render('grupo/show.html.twig', [
            'grupo' => $grupo,
        ]);
    }

    /**
     * Formulario de edición de un grupo
     */
    #[Route('/{id}/editar', name: 'app_grupo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Grupo $grupo, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(GrupoType::class, $grupo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Grupo actualizado con éxito.');
            return $this->redirectToRoute('app_grupo_index');
        }

        return $this->render('grupo/edit.html.twig', [
            'grupo' => $grupo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Eliminar un grupo de la base de datos
     */
    #[Route('/{id}', name: 'app_grupo_delete', methods: ['POST'])]
    public function delete(Request $request, Grupo $grupo, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$grupo->getId(), $request->request->get('_token'))) {
            $em->remove($grupo);
            $em->flush();
            $this->addFlash('success', 'Grupo eliminado con éxito.');
        }

        return $this->redirectToRoute('app_grupo_index');
    }
}