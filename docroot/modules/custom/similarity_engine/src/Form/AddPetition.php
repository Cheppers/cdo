<?php

namespace Drupal\similarity_engine\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class AddPetition extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'add_petition';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$form_state->has('step')) {
      $form_state->set('step', 1);
    }
    if ($form_state->get('step') === 1) {
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
      ];
      $form['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
      ];
    }
    elseif ($form_state->get('step') === 2) {
      $form['similar_items'] = [
        '#type' => 'markup',
        '#markup' => '<h2>Have you seen these?</h2>' . $form_state->get('similar_items'),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create petition'),
    ];
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') === 1) {
      $similarItems = \Drupal::service('similarity_engine.api_connector')
        ->getSimilar($form_state->getValue('title') . $form_state->getValue('body'));
      $output = '';
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      foreach ($similarItems as $similarItem) {
        $build = $view_builder->view($similarItem, 'teaser');
        $output .= '<div>' . render($build) . '</div>';
      }
      $node = Node::create([
        'type' => 'petition',
        'title' => $form_state->getValue('title'),
      ]);
      $node->set('body', [
        'value' => $form_state->getValue('body'),
        'format' => 'rich_text'
      ]);
      $form_state->set('node', $node);
      $form_state->set('similar_items', $output);
      $form_state->set('step', 2);
      $form_state->setRebuild();
    }
    else {
      $node = $form_state->get('node');
      $node->save();
      \Drupal::service('similarity_engine.api_connector')
        ->putDocument($form_state->getValue('title') . $form_state->getValue('body'), $node->uuid());
      drupal_set_message($this->t('Here is your petition, start gaining support for it.'));
      $form_state->setRedirect('entity.node.canonical',['node' => $node->id()]);
    }
  }
}