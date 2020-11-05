<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/test/json' => [[['_route' => 'test_json', 'rateLimiterQuota' => 100, 'rateLimiterTTL' => 60, '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testJsonAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/json/limited' => [[['_route' => 'test_json_limited', 'rateLimiterQuota' => 1, 'rateLimiterTTL' => 100, '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testJsonAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/html' => [[['_route' => 'test_html', 'someDefault' => 'value', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testHtmlAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/error/html/internal-server-error' => [[['_route' => 'test_error_html_internal', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testInternalServerErrorHtmlAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/error/html/bad-request' => [[['_route' => 'test_error_html_bad_request', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testBadRequestErrorHtmlAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/error/html/unauthorized' => [[['_route' => 'test_error_html_unauthorized', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testUnauthorizedErrorHtmlAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/error/html/forbidden' => [[['_route' => 'test_error_html_forbidden', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testForbiddenErrorHtmlAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/error/html/service-unavailable' => [[['_route' => 'test_error_html_service_unavailable', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testServiceUnavailableErrorHtmlAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/error/html/conflict' => [[['_route' => 'test_error_html_conflict', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testConflictErrorHtmlAction'], null, ['GET' => 0], null, false, false, null]],
        '/test/error/action-not-exists' => [[['_route' => 'test_error_action_not_exists', 'someDefault' => 'value', '_controller' => 'Eureka\\Kernel\\Http\\Tests\\Mock\\TestController::testErrorHtmlActionNotExists'], null, ['GET' => 0], null, false, false, null]],
    ],
    [ // $regexpList
    ],
    [ // $dynamicRoutes
    ],
    null, // $checkCondition
];
