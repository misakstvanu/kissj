<?php declare(strict_types = 1);

namespace kissj\Middleware;

use kissj\Middleware\BaseMiddleware;
use kissj\User\User;
use kissj\Event\Event;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as ResponseHandler;
use Sentry\State\Hub;
use Sentry\State\Scope;

final class SentryContextMiddleware extends BaseMiddleware {

    public function __construct(
        private Hub $hub,
    ) {}

    public function process(Request $request, ResponseHandler $handler): Response
    {
        // Add user info
        /** @var User|null */
        $user = $request->getAttribute('user');

        if ($user instanceof User) {
            $this->hub->configureScope(function (Scope $scope) use ($user): void {
                $scope->setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                ]);
            });
        }

        // Add Event info
        /** @var Event|null */
        $event = $request->getAttribute('event');

        if ($event instanceof Event) {
            $this->hub->configureScope(function (Scope $scope) use ($event): void {
                $scope->setContext('event', [
                    'id' => $event->id,
                    'slug' => $event->slug,
                    'name' => $event->readableName,
                ]);
            });
        }

        return $handler->handle($request);
    }

}