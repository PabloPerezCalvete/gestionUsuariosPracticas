<?php

namespace App\Controller;

use App\Entity\Equipo;
use App\Form\EquipoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/equipos')]
class EquipoController extends AbstractController
{
    /**
     * Lista todos los equipos (La tabla nacerá vacía y cargará por AJAX)
     */
    #[Route('/', name: 'app_equipo_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
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
            'form' => $form->createView(),
        ]);
    }

    /**
     * 🌟 SERVICIO WEB PARA DATATABLES SERVER-SIDE
     */
    #[Route('/server-side-data', name: 'app_equipo_server_side', methods: ['GET', 'POST'])]
    public function serverSideData(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $draw = intval($request->get('draw'));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 10));
        $searchArray = $request->get('search');
        $searchValue = $searchArray['value'] ?? '';

        $repository = $em->getRepository(Equipo::class);

        // 1. Cuenta total sin filtros
        $totalRecords = $repository->createQueryBuilder('e')
            ->select('count(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // 2. Query independiente para contar registros FILTRADOS
        $qbCount = $repository->createQueryBuilder('e')->select('count(e.id)');
        if (!empty($searchValue)) {
            $qbCount->where('e.marca LIKE :search OR e.modelo LIKE :search OR e.numeroSerie LIKE :search OR e.tipo LIKE :search')
                    ->setParameter('search', '%' . $searchValue . '%');
        }
        $totalFiltered = $qbCount->getQuery()->getSingleScalarResult();

        // 3. Query para obtener los datos específicos y paginados
        $qb = $repository->createQueryBuilder('e');
        if (!empty($searchValue)) {
            $qb->where('e.marca LIKE :search OR e.modelo LIKE :search OR e.numeroSerie LIKE :search OR e.tipo LIKE :search')
               ->setParameter('search', '%' . $searchValue . '%');
        }
        $qb->setFirstResult($start)->setMaxResults($length);
        $equipos = $qb->getQuery()->getResult();

        // 4. Transformar los objetos en el JSON plano que requiere DataTables
        $data = [];
        foreach ($equipos as $e) {
            $urlEditar = $this->generateUrl('app_equipo_edit', ['id' => $e->getId()]);
            $urlEliminar = $this->generateUrl('app_equipo_delete', ['id' => $e->getId()]);
            $tokenCsrf = $csrfTokenManager->getToken('delete' . $e->getId())->getValue();

            // Formateamos las etiquetas HTML de forma segura con comillas simples externas
            $estadoBadge = '<span class="badge bg-secondary">' . htmlspecialchars($e->getEstado()) . '</span>';

            $propietario = $e->getPropietario() 
                ? '<span class="text-primary fw-bold">' . htmlspecialchars($e->getPropietario()->getEmail()) . '</span>'
                : '<span class="text-muted fst-italic">Disponible (Stock)</span>';

            // El bloque de botones corregido sin rupturas de cadena
            $botonesAccion = '<div class="d-flex gap-2">' .
                '<a href="' . $urlEditar . '" class="btn btn-sm btn-warning">Editar</a>' .
                '<form method="post" action="' . $urlEliminar . '" onsubmit="return confirm(\'¿Seguro que quieres eliminar este equipo?\');">' .
                    '<input type="hidden" name="_token" value="' . $tokenCsrf . '">' .
                    '<button class="btn btn-sm btn-danger">Borrar</button>' .
                '</form>' .
            '</div>';

            $data[] = [
                htmlspecialchars($e->getMarca()),
                htmlspecialchars($e->getModelo()),
                '<code>' . htmlspecialchars($e->getNumeroSerie()) . '</code>',
                htmlspecialchars($e->getTipo()),
                $estadoBadge,
                $propietario,
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
     * Acción de borrar
     */
    #[Route('/{id}/borrar', name: 'app_equipo_delete', methods: ['POST'])]
    public function delete(Request $request, Equipo $equipo, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $equipo->getId(), $request->request->get('_token'))) {
            $em->remove($equipo);
            $em->flush();
            $this->addFlash('success', 'Equipo eliminado del inventario.');
        }

        return $this->redirectToRoute('app_equipo_index');
    }
}