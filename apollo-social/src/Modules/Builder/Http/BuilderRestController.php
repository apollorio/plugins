<?php

namespace Apollo\Modules\Builder\Http;

use Apollo\Modules\Builder\LayoutRepository;
use WP_Error;
use WP_REST_Request;

class BuilderRestController
{
    private LayoutRepository $repository;

    public function __construct(LayoutRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('apollo-social/v1', '/builder/layout', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => [$this, 'canAccess'],
                'args' => [
                    'user_id' => [
                        'type' => 'integer',
                        'required' => false,
                    ],
                ],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'store'],
                'permission_callback' => [$this, 'canAccess'],
                'args' => [
                    'layout' => [
                        'required' => true,
                    ],
                    'user_id' => [
                        'type' => 'integer',
                        'required' => false,
                    ],
                ],
            ],
        ]);
    }

    public function show(WP_REST_Request $request)
    {
        $userId = $this->resolveUserId($request);

        $layout = $this->repository->getLayout($userId);

        return rest_ensure_response([
            'user_id' => $userId,
            'layout' => $layout,
        ]);
    }

    public function store(WP_REST_Request $request)
    {
        $userId = $this->resolveUserId($request);
        $layout = $request->get_param('layout');

        if (is_string($layout)) {
            $decoded = json_decode($layout, true);
            $layout = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($layout)) {
            return new WP_Error('invalid_layout', __('Estrutura de layout inválida', 'apollo-social'), [
                'status' => 400,
            ]);
        }

        $this->repository->saveLayout($userId, $layout);

        return rest_ensure_response([
            'success' => true,
            'layout' => $this->repository->getLayout($userId),
        ]);
    }

    public function canAccess(): bool
    {
        return is_user_logged_in();
    }

    private function resolveUserId(WP_REST_Request $request): int
    {
        $userId = absint($request->get_param('user_id'));

        if ($userId && current_user_can('edit_users')) {
            return $userId;
        }

        return get_current_user_id();
    }
}

