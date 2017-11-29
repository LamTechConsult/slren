<?php

namespace Drupal\media_entity_slideshare\Plugin\MediaEntity\Type;

use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ZendService\SlideShare\SlideShare as ZendSlideShare;

/**
 * Provides media type plugin for SlideShare.
 *
 * @MediaType(
 *   id = "slideshare",
 *   label = @Translation("SlideShare"),
 *   description = @Translation("Provides business logic and metadata for SlideShare.")
 * )
 */
class SlideShare extends MediaTypeBase {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * API key.
   *
   * @var string $apiKey
   */
  protected $apiKey;

  /**
   * Shared secret.
   *
   * @var string $sharedSecret
   */
  protected $sharedSecret;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory->get('media_entity.settings'));

    $this->configFactory = $config_factory;
    $this->apiKey = $config_factory->get('media_entity_slideshare.settings')->get('api_key');
    $this->sharedSecret = $config_factory->get('media_entity_slideshare.settings')->get('shared_secret');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    // Iframe.
    '@src="//(www\.){0,1}slideshare\.net/slideshow/embed_code/key/(?<secretkey>[a-z0-9_-]+)@i' => 'secretkey',
    // Link.
    '@((http|https):){0,1}//(www\.){0,1}slideshare\.net/(?<login>[a-z0-9_-]+)/(?<shortcode>[a-z0-9_-]+)@i' => 'shortcode',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (!$this->getApiSettings()) {
      drupal_set_message(Link::createFromRoute($this->t('You must fill your settings.'), 'media_entity_slideshare.admin_form'), 'error');
    }

    $options = [];
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'string_long', 'link'];
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores SlideShare embed code or URL. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function attachConstraints(MediaInterface $media) {
    parent::attachConstraints($media);

    if (isset($this->configuration['source_field'])) {
      $source_field_name = $this->configuration['source_field'];
      if ($media->hasField($source_field_name)) {
        /** @var \Drupal\Core\Field\FieldItemInterface $embed_code */
        foreach ($media->get($source_field_name) as &$embed_code) {
          /** @var \Drupal\Core\TypedData\DataDefinitionInterface $typed_data */
          $typed_data = $embed_code->getDataDefinition();
          $typed_data->addConstraint('SlideShareEmbedCode');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/slideshare.png';
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'shortcode' => $this->t('SlideShare shortcode'),
      'login' => $this->t('Login'),
      'Location' => $this->t('Location'),
      'Tags' => $this->t('Tags'),
      'RelatedSlideshowIds' => $this->t('Related slideshow ids'),
      'Filename' => $this->t('Filename'),
      'Id' => $this->t('Id'),
      'EmbedCode' => $this->t('Embed code'),
      'ThumbnailUrl' => $this->t('Thumbnail URL'),
      'ThumbnailSmallUrl' => $this->t('Thumbnail small URL'),
      'Title' => $this->t('Title'),
      'Description' => $this->t('Description'),
      'Status' => $this->t('Status'),
      'StatusDescription' => $this->t('Status description'),
      'PermaLink' => $this->t('Permalink'),
      'NumViews' => $this->t('Number of views'),
      'NumDownloads' => $this->t('Number of downloads'),
      'NumComments' => $this->t('Number of comments'),
      'NumFavorites' => $this->t('Number of favorites'),
      'NumSlides' => $this->t('Number of slides'),
      'Username' => $this->t('Username'),
      'Created' => $this->t('Created'),
      'Updated' => $this->t('Updated'),
      'Language' => $this->t('Language'),
      'Format' => $this->t('Format'),
      'Download' => $this->t('Download'),
      'DownloadUrl' => $this->t('Download URL'),
      'SlideshowEmbedUrl' => $this->t('Slideshow embed URL'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $matches = $this->matchRegexp($media);
    $slideshow = NULL;

    if (isset($matches['secretkey'])) {
      drupal_set_message(t("SlideShare don't allow an API whith secret key..."), 'warning');
      return;
    }
    elseif (isset($matches['shortcode']) && isset($matches['login'])) {
      try {
        /** @var \ZendService\SlideShare\SlideShare $slideshare */
        $slideshare = new ZendSlideShare($this->apiKey, $this->sharedSecret);

        /** @var \ZendService\SlideShare\SlideShow $slideshow */
        $slideshow = $slideshare->getSlideShowByUrl("http://www.slideshare.net/{$matches['login']}/{$matches['shortcode']}");
      }
      catch (RuntimeException $exception) {
        watchdog_exception(__CLASS__, $exception);
      }
      catch (HttpRuntimeException $exception) {
        watchdog_exception(__CLASS__, $exception);
      }

      if ($name == 'shortcode') {
        return $matches['shortcode'];
      }
      elseif ($name == 'login') {
        return $matches['login'];
      }
    }

    if ($slideshow === NULL) {
      return NULL;
    }

    return $slideshow->{"get$name"}();
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = [];

    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        foreach (static::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, $media->{$source_field}->{$property_name}, $matches)) {
            return $matches;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * To know if API settings are filling.
   *
   * @return bool
   *   True if settings are filling or false if not.
   */
  protected function getApiSettings() {
    /** @var \Drupal\Core\Config\ImmutableConfig $settings */
    $settings = $this->configFactory->get('media_entity_slideshare.settings');

    if ($settings->get('api_key') && $settings->get('shared_secret')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    // Retrieve the title of this SlideShare.
    $title = $this->getField($media, 'Title');

    if ($title) {
      return $title;
    }

    return parent::getDefaultName($media);
  }

}
