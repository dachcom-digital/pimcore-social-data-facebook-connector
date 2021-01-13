<?php

namespace SocialData\Connector\Facebook\Controller\Admin;

use Carbon\Carbon;
use Facebook\Exceptions\FacebookSDKException;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use SocialData\Connector\Facebook\Client\FacebookClient;
use SocialData\Connector\Facebook\Model\EngineConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Controller\Admin\Traits\ConnectResponseTrait;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class FacebookController extends AdminController
{
    use ConnectResponseTrait;

    /**
     * @var FacebookClient
     */
    protected $facebookClient;

    /**
     * @var EnvironmentServiceInterface
     */
    protected $environmentService;

    /**
     * @var ConnectorServiceInterface
     */
    protected $connectorService;

    /**
     * @param FacebookClient              $facebookClient
     * @param EnvironmentServiceInterface $environmentService
     * @param ConnectorServiceInterface   $connectorService
     */
    public function __construct(
        FacebookClient $facebookClient,
        EnvironmentServiceInterface $environmentService,
        ConnectorServiceInterface $connectorService
    ) {
        $this->facebookClient = $facebookClient;
        $this->environmentService = $environmentService;
        $this->connectorService = $connectorService;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     *
     * @throws FacebookSDKException
     */
    public function connectAction(Request $request)
    {
        try {
            $connectorDefinition = $this->getConnectorDefinition();
            $connectorEngineConfig = $this->getConnectorEngineConfig($connectorDefinition);
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        $fb = $this->facebookClient->getClient($connectorEngineConfig);

        $helper = $fb->getRedirectLoginHelper();
        $definitionConfiguration = $connectorDefinition->getDefinitionConfiguration();

        $callbackUrl = $this->generateUrl('social_data_connector_facebook_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $loginUrl = $helper->getLoginUrl($callbackUrl, $definitionConfiguration['api_connect_permission']);

        return $this->redirect($loginUrl);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function checkAction(Request $request)
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        $fb = $this->facebookClient->getClient($connectorEngineConfig);
        $helper = $fb->getRedirectLoginHelper();

        if (!$accessToken = $helper->getAccessToken()) {
            if ($helper->getError()) {
                return $this->buildConnectErrorResponse($helper->getErrorCode(), $helper->getError(), $helper->getErrorReason(), $helper->getErrorDescription());
            }

            return $this->buildConnectErrorResponse(500, 'general_error', 'invalid access token', $request->query->get('error_message', 'Unknown Error'));
        }

        try {
            $accessToken = $fb->getOAuth2Client()->getLongLivedAccessToken($accessToken);
        } catch (FacebookSDKException $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'long lived access token error', $e->getMessage());
        }

        try {
            // @todo: really? Dispatch the /me/accounts request to make the user token finally ever lasting.
            $response = ($fb->get('/me/accounts?fields=access_token', $accessToken->getValue()))->getDecodedBody();
        } catch (FacebookSDKException $e) {
            // we don't need to fail here.
            // in worst case this means only we don't have a never expiring token
        }

        $accessTokenMetadata = $fb->getOAuth2Client()->debugToken($accessToken->getValue());

        $expiresAt = null;
        if ($accessTokenMetadata->getExpiresAt() instanceof \DateTime) {
            $expiresAt = $accessTokenMetadata->getExpiresAt();
        }

        $connectorEngineConfig->setAccessToken($accessToken->getValue(), true);
        $connectorEngineConfig->setAccessTokenExpiresAt($expiresAt, true);
        $this->connectorService->updateConnectorEngineConfiguration('facebook', $connectorEngineConfig);

        return $this->buildConnectSuccessResponse();
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function debugTokenAction(Request $request)
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        $token = $connectorEngineConfig->getAccessToken();

        if (empty($token)) {
            return $this->adminJson(['error' => true, 'message' => 'acccess token is empty']);
        }

        try {
            $fb = $this->facebookClient->getClient($connectorEngineConfig);
            $accessTokenMetadata = $fb->getOAuth2Client()->debugToken($token);
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        $serializer = new Serializer([new PropertyNormalizer(), new ObjectNormalizer()]);

        $normalizedData = $serializer->normalize($accessTokenMetadata, 'array', [
            AbstractNormalizer::CALLBACKS => [
                'metadata' => function ($data) {
                    if (isset($data['expires_at']) && $data['expires_at'] instanceof \DateTime) {
                        $data['expires_at'] = Carbon::parse($data['expires_at'])->toDayDateTimeString();
                    } elseif (isset($data['expires_at']) && $data['expires_at'] === 0) {
                        $data['expires_at'] = 'Never';
                    }

                    if (isset($data['issued_at']) && $data['issued_at'] instanceof \DateTime) {
                        $data['issued_at'] = Carbon::parse($data['issued_at'])->toDayDateTimeString();
                    }

                    if (isset($data['data_access_expires_at']) && !empty($data['data_access_expires_at'])) {
                        $data['data_access_expires_at'] = Carbon::createFromTimestamp($data['data_access_expires_at'])->toDayDateTimeString();
                    } elseif (isset($data['data_access_expires_at']) && $data['data_access_expires_at'] === 0) {
                        $data['data_access_expires_at'] = 'Never';
                    }

                    return $data;
                }
            ]
        ]);

        return $this->adminJson([
            'success' => true,
            'data'    => isset($normalizedData['metadata']) ? $normalizedData['metadata'] : []
        ]);
    }

    /**
     * @return ConnectorDefinitionInterface
     */
    protected function getConnectorDefinition()
    {
        $connectorDefinition = $this->connectorService->getConnectorDefinition('facebook', true);

        if (!$connectorDefinition->engineIsLoaded()) {
            throw new HttpException(400, 'Engine is not loaded.');
        }

        return $connectorDefinition;
    }

    /**
     * @param ConnectorDefinitionInterface $connectorDefinition
     *
     * @return EngineConfiguration
     */
    protected function getConnectorEngineConfig(ConnectorDefinitionInterface $connectorDefinition)
    {
        $connectorEngineConfig = $connectorDefinition->getEngineConfiguration();
        if (!$connectorEngineConfig instanceof EngineConfiguration) {
            throw new HttpException(400, 'Invalid facebook configuration. Please configure your connector "facebook" in backend first.');
        }

        return $connectorEngineConfig;
    }
}
