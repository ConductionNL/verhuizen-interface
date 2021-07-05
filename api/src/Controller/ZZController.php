<?php

// src/Controller/ZZController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\ApplicationService;

//use App\Service\RequestService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use function GuzzleHttp\Promise\all;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The ZZ test handles any calls that have not been picked up by another test, and wel try to handle the slug based against the wrc.
 *
 * Class ZZController
 *
 * @Route("/")
 */
class ZZController extends AbstractController
{
    /**
     * @Route("/", name="app_default_index")
     * @Route("/{slug}", requirements={"slug"=".+"}, name="slug")
     * @Template
     */
    public function indexAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];
        $processes = $commonGroundService->getResourceList(['component' => 'ptc', 'type' => 'process_types'])['hydra:member'];
        foreach ($processes as $process) {
            if ($process['name'] == 'Aanvragen begrafenis') {
                $variables['process'] = $process;
            }
        }

        return $variables;
    }
}
