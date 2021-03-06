<?php

namespace SocialData\Connector\Facebook\Builder;

use Carbon\Carbon;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\GraphNodes\GraphEdge;
use Facebook\GraphNodes\GraphNode;
use SocialData\Connector\Facebook\Client\FacebookClient;
use SocialData\Connector\Facebook\Model\EngineConfiguration;
use SocialData\Connector\Facebook\Model\FeedConfiguration;
use SocialData\Connector\Facebook\QueryBuilder\FacebookQueryBuilder;
use SocialDataBundle\Connector\SocialPostBuilderInterface;
use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use SocialDataBundle\Exception\BuildException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialPostBuilder implements SocialPostBuilderInterface
{
    /**
     * @var FacebookClient
     */
    protected $facebookClient;

    /**
     * @param FacebookClient $facebookClient
     */
    public function __construct(FacebookClient $facebookClient)
    {
        $this->facebookClient = $facebookClient;
    }

    /**
     * {@inheritdoc}
     */
    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        $engineConfiguration = $buildConfig->getEngineConfiguration();
        $feedConfiguration = $buildConfig->getFeedConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        if (!$feedConfiguration instanceof FeedConfiguration) {
            return;
        }

        if (empty($feedConfiguration->getPageId())) {
            throw new BuildException('Invalid Page ID given');
        }

        $fqb = new FacebookQueryBuilder();

        $fields = [
            'id',
            'message',
            'story',
            'full_picture',
            'permalink_url',
            'created_time',
            'attachments',
            'status_type',
            'is_published'
        ];

        $limit = is_numeric($feedConfiguration->getLimit()) ? $feedConfiguration->getLimit() : 50;

        $posts = $fqb
            ->edge('posts')
            ->fields($fields)
            ->limit($limit);

        $queryBuilder = $fqb
            ->node($feedConfiguration->getPageId())
            ->fields([$posts]);

        $resolver->setDefaults([
            'facebookQueryBuilder' => $queryBuilder
        ]);

        $resolver->setRequired(['facebookQueryBuilder']);
        $resolver->addAllowedTypes('facebookQueryBuilder', [FacebookQueryBuilder::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(FetchData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $engineConfiguration = $buildConfig->getEngineConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        $client = $this->facebookClient->getClient($engineConfiguration);

        /** @var FacebookQueryBuilder $fqbRequest */
        $fqbRequest = $options['facebookQueryBuilder'];

        $url = $fqbRequest->asEndpoint();

        try {
            $response = $client->get($url, $engineConfiguration->getAccessToken());
        } catch (FacebookResponseException $e) {
            throw new BuildException(sprintf('graph error: %s [endpoint: %s]', $e->getMessage(), $url));
        } catch (FacebookSDKException $e) {
            throw new BuildException(sprintf('facebook SDK error: %s [endpoint: %s]', $e->getMessage(), $url));
        }

        $graphEdge = $response->getGraphNode()->getField('posts');

        if (!$graphEdge instanceof GraphEdge) {
            return;
        }

        if (count($graphEdge) === 0) {
            return;
        }

        $items = [];

        /** @var GraphNode $item */
        foreach ($graphEdge as $item) {
            $items[] = $item->asArray();
        }

        $data->setFetchedEntities($items);
    }

    /**
     * {@inheritdoc}
     */
    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    /**
     * {@inheritdoc}
     */
    public function filter(FilterData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $element = $data->getTransferredData();

        if (!is_array($element)) {
            return;
        }

        if (isset($element['is_published']) && $element['is_published'] === false) {
            return;
        }

        // @todo: check if feed has some filter (filter for hashtag for example)

        $data->setFilteredElement($element);
        $data->setFilteredId($element['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    /**
     * {@inheritdoc}
     */
    public function transform(TransformData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $element = $data->getTransferredData();
        $socialPost = $data->getSocialPostEntity();

        if (!is_array($element)) {
            return;
        }

        if ($element['created_time'] instanceof \DateTime) {
            $creationTime = Carbon::instance($element['created_time']);
        } else {
            $creationTime = Carbon::now();
        }

        $socialPost->setSocialCreationDate($creationTime);
        $socialPost->setContent($element['message']);
        $socialPost->setUrl($element['permalink_url']);
        $socialPost->setPosterUrl($element['full_picture']);

        $data->setTransformedElement($socialPost);
    }
}
