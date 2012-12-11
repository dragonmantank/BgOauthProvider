<?php

namespace BgOauthProvider;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\ControllerManager;


class Module implements AutoloaderProviderInterface, ServiceProviderInterface
{

    public function onBootstrap(MvcEvent $event)
    {
        $app = $event->getApplication();
        $events = $app->getEventManager();
        $sm = $app->getServiceManager();

        $events->attach($sm->get('BgOauthProvider\RouteGuard'));
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'BgOauthProvider\OauthService' => function ($serviceLocator) {
                    return new \BgOauthProvider\Service\Oauth(
                        $serviceLocator->get('doctrine.entitymanager.orm_default')
                    );
                },
                'BgOauthProvider\AppService' => function ($serviceLocator) {
                    return new \BgOauthProvider\Service\App(
                        $serviceLocator->get('doctrine.entitymanager.orm_default')
                    );
                },
                'BgOauthProvider\OauthProvider' => function ($serviceLocator) {
                    return new \BgOauthProvider\Oauth\Provider(
                        $serviceLocator->get('BgOauthProvider\OauthService'),
                        $serviceLocator->get('BgOauthProvider\AppService'),
                        $serviceLocator->get('BgOauthProvider\TokenEntity')
                    );
                },
                'BgOauthProvider\Acl' => function ($serviceLocator) {
                    $config = $serviceLocator->get('Configuration');
                    $aclConfig = array_key_exists('BgOauthAcl', $config) ? $config['BgOauthAcl'] : array();

                    return new \BgOauthProvider\Acl($aclConfig);
                },
                'BgOauthProvider\RouteGuard' => function ($serviceLocator) {
                    return new \BgOauthProvider\Guard\Route(
                        $serviceLocator->get('BgOauthProvider\Acl'),
                        $serviceLocator->get('BgOauthProvider\OauthProvider')
                    );
                },
            ),
            'invokables' => array(
                'BgOauthProvider\TokenEntity' => 'BgOauthProvider\Entity\Token',
            ),
            'shared' => array(
                'BgOauthProvider\TokenEntity' => false,
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'BgOauthProvider\Controller\Oauth' => function(ControllerManager $controllerManager) {
                    $serviceManager = $controllerManager->getServiceLocator();

                    $controller = new \BgOauthProvider\Controller\OauthController();
                    $controller->setOauthService($serviceManager->get('BgOauthProvider\OauthService'));
                    $controller->setOauthProvider($serviceManager->get('BgOauthProvider\OauthProvider'));

                    return $controller;
                }
            ),
        );
    }
}
