<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UserAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_users')]
    public function index(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $userRepository->createQueryBuilder('u')
            ->orderBy('u.email', 'ASC')
            ->getQuery();

        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_user_delete')]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Impossible de supprimer un administrateur.');
            return $this->redirectToRoute('app_admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', '✅ Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('app_admin_users');
    }
}
