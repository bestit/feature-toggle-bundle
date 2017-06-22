<?php

namespace BestIt\FeatureToggleBundle\Listener;

use BestIt\FeatureToggleBundle\Annotations\Feature;
use BestIt\FeatureToggleBundle\Manager\FeatureManagerInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AnnotationSubscriber
 *
 * @author Michel Chowanski <chowanski@bestit-online.de>
 * @package BestIt\FeatureToggleBundle\Listener
 */
class AnnotationSubscriber implements EventSubscriberInterface
{
    /**
     * Annotation reader
     *
     * @var Reader
     */
    private $reader;

    /**
     * The feature manager
     *
     * @var FeatureManagerInterface
     */
    private $manager;

    /**
     * FeatureListener constructor.
     *
     * @param Reader $reader
     * @param FeatureManagerInterface $manager
     */
    public function __construct(Reader $reader, FeatureManagerInterface $manager)
    {
        $this->reader = $reader;
        $this->manager = $manager;
    }

    /**
     * Filter on controller / method
     *
     * @param FilterControllerEvent $event
     *
     * @return void
     * @throws NotFoundHttpException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $class = ClassUtils::getClass($controller[0]);

        try {
            $object = new ReflectionClass($class);
        } catch (ReflectionException $exception) {
            throw new NotFoundHttpException('Unable to reflect class');
        }

        foreach ($this->reader->getClassAnnotations($object) as $annotation) {
            if ($annotation instanceof Feature) {
                if (!$this->manager->isActive($annotation->name)) {
                    throw new NotFoundHttpException();
                }
            }
        }

        $method = $object->getMethod($controller[1]);
        foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof Feature) {
                if (!$this->manager->isActive($annotation->name)) {
                    throw new NotFoundHttpException();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
