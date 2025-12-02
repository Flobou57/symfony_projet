<?php

namespace App\Controller;

use App\Form\SearchProductType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchProductType::class);
        $form->handleRequest($request);

        $data = $form->getData() ?? [];
        $query = $data['query'] ?? '';
        $category = $data['category'] ?? null;

        return $this->render('search/index.html.twig', [
            'form' => $form,
            'initialQuery' => $query,
            'initialCategoryId' => $category?->getId(),
        ]);
    }
}
