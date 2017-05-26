<?php

namespace Drupal\similarity_engine\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\similarity_engine\ApiConnector;

/**
 * Provides a simple form for testing results from the Similarity Engine API.
 */
class SimilaritySandboxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'similrity_engine_sandbox_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['text'] = [
      '#type' => 'textarea',
      '#title' => t('Text to send'),
      '#required' => TRUE,
      '#description' => 'Must be at least n(?) characters long - not sure exactly, but the server throws a 500 if it\'s too short :)',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Get similar documents!'),
    ];

    if ($text = $form_state->getValue('text')) {
      $output = $this->renderResults($text);
      $form['results'] = [
        '#title' => 'Results',
        '#type' => 'markup',
        '#markup' => '<h2>Similar existing petitions:</h2>' . $output,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    drupal_set_message('Submitted');
  }

  /**
   * Gets markup for nodes related to the provided text.
   *
   * @param string $text
   *   The text to send to the Similarity Engine.
   *
   * @return null|string
   *   Render markup for petition nodes.
   */
  protected function renderResults($text) {
    $similarity_engine = new ApiConnector();
    $similar = $similarity_engine->getSimilar($text);
    $output = NULL;
    foreach ($similar as $similar_node) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $build = $view_builder->view($similar_node, 'teaser');
      $output .= render($build);
    }
    return $output;
  }

}
