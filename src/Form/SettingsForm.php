<?php

namespace Drupal\devpanel_marketplace_bar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\devpanel_marketplace_bar\Helper\ApiHelper;

class SettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['devpanel_marketplace_bar.settings'];
  }

  public function getFormId() {
    return 'devpanel_marketplace_bar_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('devpanel_marketplace_bar.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Marketplace Bar'),
      '#default_value' => $config->get('enabled') !== NULL ? $config->get('enabled') : TRUE,
      '#description' => $this->t('Check to show the DevPanel marketplace alert bar on the site.'),
    ];

    $form['data_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Fetched Data'),
      '#open' => TRUE,
    ];

    $data = $config->get('data') ?: [];
    if (empty($data)) {
      $form['data_info']['message'] = [
        '#markup' => '<p>' . $this->t('No data has been fetched yet.') . '</p>',
      ];
    }
    else {
      $rows = [];
      foreach ($data as $key => $value) {
        $rows[] = [
          $key,
          is_bool($value) ? ($value ? 'Yes' : 'No') : htmlspecialchars($value),
        ];
      }
      $form['data_info']['table'] = [
        '#type' => 'table',
        '#header' => [$this->t('Key'), $this->t('Value')],
        '#rows' => $rows,
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];
    $form['actions']['refetch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Re-fetch API Data'),
      '#submit' => ['::submitForm', '::refetchData'],
      '#button_type' => 'secondary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('devpanel_marketplace_bar.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public function refetchData(array &$form, FormStateInterface $form_state) {
    if (ApiHelper::fetchAndSaveData()) {
      $this->messenger()->addStatus($this->t('Successfully re-fetched API data from DrupalForge.'));
    }
    else {
      $this->messenger()->addError($this->t('Failed to fetch API data. Check logs for more information.'));
    }
  }

}
