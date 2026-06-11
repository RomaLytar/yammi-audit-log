<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Application\Playground\MethodCatalog;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Infrastructure\Playground\PlaygroundExecutor;

/** @internal */
final class PlaygroundController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly MethodCatalog $catalog,
        private readonly PlaygroundExecutor $executor,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(): View
    {
        return $this->view->make('audit-log::playground', ['methods' => $this->catalog->all()]);
    }

    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method' => 'required|string',
            'args' => 'sometimes|array',
        ]);

        $method = $this->catalog->find(is_string($validated['method']) ? $validated['method'] : '');

        if ($method === null) {
            return new JsonResponse(['error' => 'Unknown method.'], 404);
        }

        $args = $request->input('args');

        try {
            $result = $this->executor->execute($method->key, is_array($args) ? $args : []);

            return new JsonResponse(['ok' => true, 'result' => $result]);
        } catch (InvalidAuditData $exception) {
            return new JsonResponse(['ok' => false, 'error' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            $this->logger->error('Playground execution failed: '.$exception->getMessage(), ['exception' => $exception]);

            return new JsonResponse(['ok' => false, 'error' => 'Execution failed — see the application log.'], 500);
        }
    }
}
