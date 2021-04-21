<?php

namespace App\Controller;

use App\Service\Redmine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/project/{id}", name="api_project")
     */
    public function project($id, Redmine $redmine): Response
    {
        $employers = $redmine->getEmployers($id);
        return JsonResponse::fromJsonString(
            json_encode($employers, JSON_UNESCAPED_UNICODE)
        );
    }
}
