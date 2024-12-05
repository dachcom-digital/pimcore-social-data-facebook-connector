<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace SocialData\Connector\Facebook\Controller\Admin;

use Carbon\Carbon;
use League\OAuth2\Client\Token\AccessToken;
use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use SocialData\Connector\Facebook\Client\FacebookClient;
use SocialData\Connector\Facebook\Model\EngineConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Controller\Admin\Traits\ConnectResponseTrait;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FacebookController extends AdminAbstractController
{
    use ConnectResponseTrait;

    public function __construct(
        protected FacebookClient $facebookClient,
        protected EnvironmentServiceInterface $environmentService,
        protected ConnectorServiceInterface $connectorService
    ) {
    }

    public function connectAction(Request $request): Response
    {
        try {
            $connectorDefinition = $this->getConnectorDefinition();
            $connectorEngineConfig = $this->getConnectorEngineConfig($connectorDefinition);
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        $definitionConfiguration = $connectorDefinition->getDefinitionConfiguration();
        $provider = $this->facebookClient->getClient($connectorEngineConfig);

        $authUrl = $provider->getAuthorizationUrl([
            'scope' => $definitionConfiguration['api_connect_permission'],
        ]);

        $request->getSession()->set('FBRLH_oauth2state_social_data', $provider->getState());

        return $this->redirect($authUrl);
    }

    public function checkAction(Request $request): Response
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        if (!$request->query->has('state') || $request->query->get('state') !== $request->getSession()->get('FBRLH_oauth2state_social_data')) {
            return $this->buildConnectErrorResponse(400, 'general_error', 'missing state', 'Required param state missing from persistent data.');
        }

        $provider = $this->facebookClient->getClient($connectorEngineConfig);

        try {
            $defaultToken = $provider->getAccessToken('authorization_code', ['code' => $request->query->get('code')]);
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'token access error', $e->getMessage());
        }

        if (!$defaultToken instanceof AccessToken) {
            $message = 'Could not generate access token';
            if ($request->query->has('error_message')) {
                $message = $request->query->get('error_message');
            }

            return $this->buildConnectErrorResponse(500, 'general_error', 'invalid access token', $message);
        }

        try {
            $accessToken = $provider->getLongLivedAccessToken($defaultToken);
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'long lived access token error', $e->getMessage());
        }

        $connectorEngineConfig->setAccessToken($accessToken->getToken(), true);

        try {
            // @todo: really? Dispatch the /me/accounts request to make the user token finally ever lasting.
            $response = $this->facebookClient->makeCall('/me/accounts?fields=access_token', 'GET', $connectorEngineConfig);
        } catch (\Throwable $e) {
            // we don't need to fail here.
            // in worst case this means only we don't have a never expiring token
        }

        try {
            $accessTokenMetadata = $this->facebookClient->makeCall('/debug_token', 'GET', $connectorEngineConfig, ['input_token' => $accessToken->getToken()]);
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'debug token fetch error', $e->getMessage());
        }

        $expiresAt = null;
        if (isset($accessTokenMetadata['data']['expires_at'])) {
            $expiresAt = $accessTokenMetadata['data']['expires_at'] === 0 ? null : \DateTime::createFromFormat('U', $accessTokenMetadata['data']['expires_at']);
        }

        $connectorEngineConfig->setAccessTokenExpiresAt($expiresAt, true);

        // now set page tokens
        $pageTokens = $this->facebookClient->makeCall('/me/accounts?fields=name,access_token', 'GET', $connectorEngineConfig);

        $pages = [];
        foreach ($pageTokens['data'] ?? [] as $page) {
            $pages[] = [
                'id'          => $page['id'] ?? null,
                'name'        => $page['name'] ?? null,
                'accessToken' => $page['access_token'] ?? null,
            ];
        }

        $connectorEngineConfig->setPages($pages);

        $this->connectorService->updateConnectorEngineConfiguration('facebook', $connectorEngineConfig);

        return $this->buildConnectSuccessResponse();
    }

    public function feedConfigAction(Request $request): JsonResponse
    {
        $feedConfig = [];

        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        if ($connectorEngineConfig->hasPages()) {
            $feedConfig['pages'] = array_values(array_map(static function (array $page) {
                return ['key' => $page['name'] ?? $page['id'], 'value' => $page['id']];
            }, $connectorEngineConfig->getPages()));
        }

        return $this->adminJson([
            'success' => true,
            'data'    => $feedConfig
        ]);
    }

    public function debugTokenAction(Request $request): JsonResponse
    {
        $pageId = $request->query->get('pageId', null);

        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        if ($pageId !== null && $connectorEngineConfig->hasPages()) {
            $accessToken = $connectorEngineConfig->getPageConfig($pageId, 'accessToken');
        } else {
            $accessToken = $connectorEngineConfig->getAccessToken();
        }

        if (empty($accessToken)) {
            return $this->adminJson(['error' => true, 'message' => 'acccess token is empty']);
        }

        try {
            $accessTokenMetadata = $this->facebookClient->makeCall('/debug_token', 'GET', $connectorEngineConfig, ['input_token' => $accessToken]);
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        $normalizedData = [];

        if (isset($accessTokenMetadata['data'])) {
            foreach ($accessTokenMetadata['data'] as $rowKey => $rowValue) {
                switch ($rowKey) {
                    case 'expires_at':
                    case 'data_access_expires_at':
                        if ($rowValue === 0) {
                            $normalizedData[$rowKey] = 'Never';
                        } else {
                            $normalizedData[$rowKey] = Carbon::parse($rowValue)->toDayDateTimeString();
                        }

                        break;
                    case 'issued_at':
                        $normalizedData[$rowKey] = Carbon::parse($rowValue)->toDayDateTimeString();

                        break;
                    default:
                        $normalizedData[$rowKey] = $rowValue;
                }
            }
        }

        return $this->adminJson([
            'success' => true,
            'data'    => $normalizedData
        ]);
    }

    protected function getConnectorDefinition(): ConnectorDefinitionInterface
    {
        $connectorDefinition = $this->connectorService->getConnectorDefinition('facebook', true);

        if (!$connectorDefinition->engineIsLoaded()) {
            throw new HttpException(400, 'Engine is not loaded.');
        }

        return $connectorDefinition;
    }

    protected function getConnectorEngineConfig(ConnectorDefinitionInterface $connectorDefinition): EngineConfiguration
    {
        $connectorEngineConfig = $connectorDefinition->getEngineConfiguration();
        if (!$connectorEngineConfig instanceof EngineConfiguration) {
            throw new HttpException(400, 'Invalid facebook configuration. Please configure your connector "facebook" in backend first.');
        }

        return $connectorEngineConfig;
    }
}
