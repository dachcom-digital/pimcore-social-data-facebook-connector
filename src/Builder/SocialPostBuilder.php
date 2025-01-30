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

namespace SocialData\Connector\Facebook\Builder;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
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
    public function __construct(protected FacebookClient $facebookClient)
    {
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
            'facebookQueryBuilder' => $queryBuilder,
            'pageId'               => $feedConfiguration->getPageId(),
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
        $pageId = $options['pageId'];

        $query = $fqbRequest->asEndpoint();

        try {
            $response = $this->facebookClient->makeGraphCall($query, $engineConfiguration, $pageId);
        } catch (\Throwable $e) {
            throw new BuildException(sprintf('graph error: %s [endpoint: %s]', $e->getMessage(), $query));
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
        $element = $data->getTransferredData();
        $socialPost = $data->getSocialPostEntity();

        if (!is_array($element)) {
            return;
        }

        if ($element['created_time'] instanceof \DateTime) {
            $creationTime = Carbon::instance($element['created_time']);
        } else {
            try {
                $creationTime = Carbon::parse($element['created_time']);
            } catch (InvalidFormatException) {
                $creationTime = Carbon::now();
            }
        }

        $socialPost->setSocialCreationDate($creationTime);
        $socialPost->setContent($element['message'] ?? null);
        $socialPost->setUrl($element['permalink_url']);
        $socialPost->setPosterUrl($element['full_picture'] ?? null);

        $data->setTransformedElement($socialPost);
    }
}
