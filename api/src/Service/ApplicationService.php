<?php

// src/Service/BRPService.php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ApplicationService
{
    private $params;
    private $cache;
    private $session;
    private $flashBagInterface;
    private $request;
    private $commonGroundService;
    private $requestService;

    public function __construct(ParameterBagInterface $params, CacheInterface $cache, SessionInterface $session, FlashBagInterface $flash, RequestStack $requestStack, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->cash = $cache;
        $this->session = $session;
        $this->flash = $flash;
        $this->request = $requestStack->getCurrentRequest();
        $this->commonGroundService = $commonGroundService;
    }

    /*
     * Get a single resource from a common ground componant
     */
    public function getVariables()
    {
        $variables = [];
        if ($this->params->get('app_id')) {
            $variables['application'] = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $this->params->get('app_id')]);
            $variables['defaultConfiguration'] = $variables['application']['defaultConfiguration'];
            $variables['organization'] = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $variables['application']['organization']['id']]);
        }
//        var_dump($variables);

        // Lets handle the loading of a product is we have one
        $resource = $this->request->get('resource');
        if ($resource || $resource = $this->request->query->get('resource')) {
            $variables['resource'] = $this->commonGroundService->getResource($resource);
        }

        // Lets handle a posible login
        $bsn = $this->request->get('bsn');
        if ($bsn || $bsn = $this->request->query->get('bsn')) {
            $user = $this->commonGroundService->getResource(['component' => 'brp', 'type' => 'ingeschrevenpersonen', 'id' => $bsn]);
            $this->session->set('user', $user);
        }
        $kvk = $this->request->get('kvk');
        if ($kvk || $kvk = $this->request->query->get('kvk')) {
            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://api.kvk.nl',
                // You can set any number of default request options.
                'timeout' => 2.0,
            ]);

            $response = $client->request('GET', '/api/v2/testsearch/companies?q=test&mainBranch=true&branch=false&branchNumber=' . $kvk);
            $company = json_decode($response->getBody()->getContents(), true);
            $this->session->set('company', $company['data']['items'][0]);
        }
        $variables['user'] = $this->session->get('user');
        $variables['company'] = $this->session->get('company');

        // Let handle posible request creation
        $requestType = $this->request->request->get('requestType');
//    	if($requestType || $requestType=  $this->request->query->get('requestType')){
//
//    		$requestParent = $this->request->request->get('requestParent');
//    		if(!$requestParent){ $requestParent =  $this->request->query->get('requestParent');}
//
//    		$requestType = $this->commonGroundService->getResource($requestType);
//    		$request = $this->requestService->createFromRequestType($requestType, $requestParent);
//
//    		// Validate current reqoust type
//
//    		$requestType = $this->requestService->checkRequestType($request, $requestType);
//
//            $this->session->set('requestType', $requestType);
//            if($request != null)
//            {
//                $this->session->set('request', $request);
//                /* @todo translation */
//                $this->flash->add('success', 'Verzoek voor ' . $requestType['name'] . ' opgestart');
//            }
//            else{
//                $this->flash->add('failure', 'Kon geen verzoek voor '. $requestType['name']. ' opstarten, omdat er al een verzoek voor '.$requestType['name'].' actief is');
//            }
//    	}

        // Lets handle the loading of a request
        $request = $this->request->request->get('request');
        if ($request || $request = $this->request->query->get('request')) {
            $request = $this->commonGroundService->getResource($request);
            $requestType = $this->commonGroundService->getResource($request['request_type'], [], true);

            // Validate current reqoust type
            $requestType = $this->requestService->checkRequestType($request, $requestType);

            $this->session->set('request', $request);
            $this->session->set('requestType', $requestType);

            /* @todo translation */
            $this->flash->add('success', 'Verzoek voor ' . $requestType['name'] . ' ingeladen');
        }

        if ($this->session->get('request')) {
            $variables['request'] = $this->commonGroundService->getResource($this->session->get('request')['@id']);
        }

        $variables['requestType'] = $this->session->get('requestType');

        return $variables;
    }
}
