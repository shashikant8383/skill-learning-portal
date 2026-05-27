<?php

namespace Drupal\skill_user\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects anonymous visitors to login when protected content is requested.
 */
final class AccessDeniedSubscriber implements EventSubscriberInterface {

  /**
   * Creates an access denied subscriber.
   */
  public function __construct(
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Redirects anonymous access denied requests to the login page.
   */
  public function onException(ExceptionEvent $event): void {
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    if (!$event->getThrowable() instanceof AccessDeniedHttpException) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if (str_starts_with($path, '/user/login') || str_starts_with($path, '/user/register')) {
      return;
    }

    $login_url = Url::fromRoute('user.login', [], [
      'query' => [
        'destination' => ltrim($request->getRequestUri(), '/'),
      ],
    ])->toString();

    $event->setResponse(new RedirectResponse($login_url));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::EXCEPTION => ['onException', 50],
    ];
  }

}
