<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Image;
use App\Entity\Category;
use App\Entity\ProductStatus;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    // 🧩 INDEX — Liste des produits avec filtre Catégorie + Statut
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupération des filtres
        $selectedCategoryId = $request->query->get('category');
        $selectedStatusLabel = $request->query->get('status');

        // Récupération de toutes les catégories et statuts
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $statuses = $entityManager->getRepository(ProductStatus::class)->findAll();

        // Construction des critères de recherche
        $criteria = [];

        if ($selectedCategoryId) {
            $criteria['category'] = $selectedCategoryId;
        }

        if ($selectedStatusLabel) {
            // Trouver l’objet ProductStatus correspondant au label
            $statusEntity = $entityManager->getRepository(ProductStatus::class)
                ->findOneBy(['label' => $selectedStatusLabel]);

            if ($statusEntity) {
                $criteria['status'] = $statusEntity;
            }
        }

        // Recherche des produits filtrés
        $products = $productRepository->findBy($criteria);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'statuses' => $statuses,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedStatus' => $selectedStatusLabel,
        ]);
    }

    // 🧩 NEW — Ajout d’un produit avec upload d’image
    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du fichier image
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads',
                    $newFilename
                );

                $image = new Image();
                $image->setUrl('/uploads/' . $newFilename);
                $image->setProduct($product);
                $entityManager->persist($image);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', '✅ Produit ajouté avec succès !');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    // 🧩 SHOW — Affichage du détail d’un produit
    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    // 🧩 EDIT — Modification d’un produit
    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads',
                    $newFilename
                );

                $image = new Image();
                $image->setUrl('/uploads/' . $newFilename);
                $image->setProduct($product);
                $entityManager->persist($image);
            }

            $entityManager->flush();
            $this->addFlash('success', '✅ Produit modifié avec succès !');

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    // 🧩 DELETE — Suppression d’un produit
    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', '🗑️ Produit supprimé avec succès !');
        }

        return $this->redirectToRoute('app_product_index');
    }
}
