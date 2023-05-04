<?php

namespace Drupal\custom_banner\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\NodeType;
use Drupal\group\Entity\GroupInterface;

/**
 * Domain Group Settings Form.
 */
class DomainGroupSettingsForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_default_settings_form';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\group\Entity\GroupInterface|NULL $group
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL): array {
    $node_types = NodeType::loadMultiple();
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('@group_label - Domain Settings', ['@group_label' => $group->label()]),
    ];
    foreach ($node_types as $key => $value) {
      $form['settings'][$key] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Default spotlight for listing %t', ['%t' => $value->label()]),
        '#upload_location' => 'public://banners/',
        '#multiple' => FALSE,
        '#description' => t('Allowed extensions: gif png jpg jpeg'),
        '#upload_validators' => [
          'file_validate_is_image' => [],
          'file_validate_extensions' => ['gif png jpg jpeg'],
          'file_validate_size' => [25600000],
        ],
        '#default_value' => Drupal::state()->get($key . '-' . $group->id()) ? [
          Drupal::state()
            ->get($key . '-' . $group->id())['#media']->field_media_image->__get('target_id'),
        ] : NULL,
      ];
    }
    $form_state->set('group', $group->id());
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;

  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\group\Entity\GroupInterface|NULL $group
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    if ($node_types = NodeType::loadMultiple()) {
      foreach ($node_types as $key => $bundle) {
        $this->setPermanentMedia($key, $form_state);

      }
      $this->messenger()->addStatus('Changes saved');
    }
  }

  /**
   * @param $name
   * @param $form_state
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setPermanentMedia($name, $form_state): void {
    /* Fetch the array of the media stored temporarily in database */
    $media = $form_state->getValue($name);
    if (!empty($media[0])) {
      /* Load the object of the file by fid */
      $file = File::load($media[0]);
      /* Set the status flag permanent of the file object */
      $file->setPermanent();
      /* Save the file in database */
      $file->save();
      $media = Media::create([
        'bundle' => 'image',
        'uid' => Drupal::currentUser()->id(),
        'field_media_image' => [
          'target_id' => $file->id(),
        ],
      ]);
      $media->setName($name)->setPublished()->save();

      $rendred = Drupal::entityTypeManager()
        ->getViewBuilder('media')
        ->view($media, 'teaser');

      Drupal::state()->set($name . '-' . $form_state->get('group'), $rendred);
    }
  }


}
