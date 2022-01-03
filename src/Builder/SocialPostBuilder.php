<?php

namespace SocialData\Connector\Facebook\Builder;

use Carbon\Carbon;
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
    protected FacebookClient $facebookClient;

    public function __construct(FacebookClient $facebookClient)
    {
        $this->facebookClient = $facebookClient;
    }

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

    public function fetch(FetchData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $engineConfiguration = $buildConfig->getEngineConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        /** @var FacebookQueryBuilder $fqbRequest */
        $fqbRequest = $options['facebookQueryBuilder'];

        $url = $fqbRequest->asEndpoint();

        try {
            $response = $this->facebookClient->makeGraphCall($url, $engineConfiguration);
        } catch (\Throwable $e) {
            throw new BuildException(sprintf('graph error: %s [endpoint: %s]', $e->getMessage(), $url));
        }

        if (!is_array($response)) {
            return;
        }

        if (!isset($response['posts']['data'])) {
            return;
        }

        $items = $response['posts']['data'];
        if (!is_array($items)) {
            return;
        }

        if (count($items) === 0) {
            return;
        }

        $data->setFetchedEntities($items);
    }

    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

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

    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

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
