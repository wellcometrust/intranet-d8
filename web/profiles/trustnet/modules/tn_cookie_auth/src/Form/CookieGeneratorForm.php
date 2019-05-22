<?php

namespace Drupal\tn_cookie_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Firebase\JWT\JWT;

/**
 * Trustnet cookie generator.
 */
class CookieGeneratorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tn_cookie_auth_cookie_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    dpm($_COOKIE);
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The email address.'),
      '#default_value' => '',
      '#description' => $this->t("The email address of the user you want to spoof."),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
    $messenger->addMessage($this->generateToken($form_state->getValue('email'), '5b229b595ccf4'));
  }

  /**
   * Generate token.
   *
   * @param string $email
   *   The email address of the user to generate a token for.
   * @param bool $user_id
   *   Optional user id of the user to generate a token for.
   *
   * @return string
   *   A signed JWT token.
   */
  private function generateToken($email, $user_id = FALSE) {
    if (!$user_id) {
      $user_id = uniqid();
    }
    $data = [
      'email' => $email,
      'userId' => $user_id,
    ];

    return JWT::encode($data, Settings::get('tn_cookie_auth_jwt_secret_key'));
  }

}
