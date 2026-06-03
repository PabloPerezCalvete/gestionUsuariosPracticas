<?php
namespace App\Controller;

use App\Entity\Usuario1;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface; 

#[Route('/admin/usuarios')]
class UserController extends AbstractController
{
    // LISTADO Y ALTA (Modified)
    #[Route('/', name: 'app_user_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, MailerInterface $mailer): Response
    {
        $user = new Usuario1();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();
            $email = (new Email())
                ->from('admin@localhost')
                ->to($user->getEmail())
                ->subject('Bienvenido')
                ->text('Tu cuenta ha sido creada correctamente');

            $mailer->send($email);
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/index.html.twig', [
           
            'form' => $form->createView(),
        ]);
    }

    //  RUTA: SERVICIO WEB PARA DATATABLES SERVER-SIDE
    #[Route('/server-side-data', name: 'app_user_server_side', methods: ['GET', 'POST'])]
    public function serverSideData(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        // Parámetros obligatorios que envía DataTables vía POST/GET
        $draw = intval($request->get('draw'));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 10));
        $searchArray = $request->get('search');
        $searchValue = $searchArray['value'] ?? '';

        $repository = $em->getRepository(Usuario1::class);

        // 1. Cuenta total de usuarios sin filtros
        $totalRecords = $repository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // 2. Query base para buscar y paginar
        $qb = $repository->createQueryBuilder('u');

        if (!empty($searchValue)) {
            $qb->where('u.email LIKE :search')
               ->setParameter('search', '%' . $searchValue . '%');
        }

        // 3. Cuenta de registros que cumplen el criterio de búsqueda
        $qbCount = clone $qb;
        $totalFiltered = $qbCount->select('count(u.id)')->getQuery()->getSingleScalarResult();

        // 4. Aplicar límites de la página actual
        $qb->setFirstResult($start)
           ->setMaxResults($length);

        $usuarios = $qb->getQuery()->getResult();

        // 5. Mapear los datos al formato JSON plano que requiere DataTables
        $data = [];
        foreach ($usuarios as $u) {
            $urlEditar = $this->generateUrl('app_user_edit', ['id' => $u->getId()]);
            $urlEliminar = $this->generateUrl('app_user_delete', ['id' => $u->getId()]);
            $tokenCsrf = $csrfTokenManager->getToken('delete'.$u->getId())->getValue();

            // Renderizamos los botones directamente para meterlos en la columna "Acciones"
            $botonesAccion = '
                <div class="d-flex gap-2">
                    <a href="'.$urlEditar.'" class="btn btn-sm btn-warning">Editar</a>
                    <form method="post" action="'.$urlEliminar.'" onsubmit="return confirm(\'¿Seguro?\')">
                        <input type="hidden" name="_token" value="'.$tokenCsrf.'">
                        <button class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                </div>';

            $data[] = [
                $u->getEmail(),
                implode(', ', $u->getRoles()),
                $botonesAccion // Esto caerá en la columna "no-sort"
            ];
        }

        return new JsonResponse([
            "draw" => $draw,
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ]);
    }

    // 🌓 GUARDAR PREFERENCIA DE TEMA
    #[Route('/update-theme', name: 'app_user_update_theme', methods: ['POST'])]
    public function updateTheme(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var Usuario1 $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $nuevoTema = $data['theme'] ?? 'light';

        $user->setTheme($nuevoTema);
        $em->flush();

        return new JsonResponse(['success' => true]);
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