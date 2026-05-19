<?php

namespace Drupal\devpanel_marketplace_bar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

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
      // Sử dụng null coalescing operator cho gọn (PHP 7.4+)
      '#default_value' => $config->get('enabled') ?? TRUE,
      '#description' => $this->t('Check to show the DevPanel marketplace alert bar on the site.'),
    ];

    $form['data_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Fetched Data from Site DrupalForge'),
      '#open' => TRUE,
    ];

    // Lấy dữ liệu nằm trong key 'data'
    $data = $config->get('data') ?: [];
    
    if (empty($data)) {
      $form['data_info']['message'] = [
        '#markup' => '<p>' . $this->t('No data has been fetched yet.') . '</p>',
      ];
    }
    else {
      $rows = [];
      foreach ($data as $key => $value) {
        // Xử lý nếu value là mảng (như project, workspace...)
        if (is_array($value) || is_object($value)) {
          $json_string = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
          $display_value = Markup::create('<pre style="margin:0; background:transparent; border:none; padding:0;">' . htmlspecialchars($json_string) . '</pre>');
        }
        // Xử lý giá trị boolean
        elseif (is_bool($value)) {
          $display_value = $value ? $this->t('Yes') : $this->t('No');
        }
        // Xử lý chuỗi, số bình thường
        else {
          $display_value = htmlspecialchars((string) $value);
        }

        $rows[] = [
          $key,
          $display_value,
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

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Chỉ lưu lại thuộc tính 'enabled'. 
    // KHÔNG đụng chạm gì tới key 'data' (nơi chứa dữ liệu của Site A) để tránh ghi đè.
    $this->config('devpanel_marketplace_bar.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
