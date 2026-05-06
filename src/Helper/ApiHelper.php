<?php

namespace Drupal\devpanel_marketplace_bar\Helper;

class ApiHelper {

  public static function fetchAndSaveData() {
    $app_id = getenv('DP_APP_ID');
    if (!$app_id) {
      \Drupal::logger('devpanel_marketplace_bar')->warning('DP_APP_ID is empty.');
      return FALSE;
    }

    $hostname = getenv('DP_HOSTNAME');
    $current_env = 'dev';
    if ($hostname) {
      $parts = explode('-', $hostname);
      $current_env = $parts[0] ?? 'dev';
    }

    switch ($current_env) {
      case 'local':
      case 'docksal':
        $base_proxy_url = 'https://drupal-forge.docksal.site:8444';
        break;
      case 'stage':
      case 'staging':
        $base_proxy_url = 'https://stage.drupalforge.org';
        break;
      case 'prod':
      case 'production':
      case 'www':
        $base_proxy_url = 'https://www.drupalforge.org';
        break;
      case 'dev':
      default:
        $base_proxy_url = 'https://dev.drupalforge.org';
        break;
    }

    $proxy_url = $base_proxy_url . '/api/internal/alert-app-info?app_id=' . $app_id;
    $base_platform_url = $base_proxy_url . '/app/purchase/';
    
    $client = \Drupal::httpClient();
    try {
      $response = $client->request('GET', $proxy_url, [
        'headers' => [
          'X-DrupalForge-Auth' => 'DF-Alert-v1-8x92nd81bs',
        ],
        'timeout' => 5,
      ]);

      if ($response->getStatusCode() == 200) {
        $body = $response->getBody()->getContents();
        $api_data = json_decode($body, TRUE) ?: [];

        $submissionId = $api_data['submissionId'] ?? 'submissionId';
        $templateId = $api_data['templateId'] ?? 'templateId';
        $showBuyNow = !empty($api_data['showBuyNow']);

        $safe_data = [
          'appName' => $api_data['appName'] ?? 'My Application',
          'subId' => $submissionId,
          'email' => $api_data['email'] ?? '',
          'buyLink' => $base_platform_url . $submissionId . '/' . $templateId,
          'showBuyNow' => $showBuyNow,
        ];

        // Save to config.
        \Drupal::configFactory()->getEditable('devpanel_marketplace_bar.settings')
          ->set('data', $safe_data)
          ->save();

        return TRUE;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('devpanel_marketplace_bar')->error('Failed to fetch API data: @message', ['@message' => $e->getMessage()]);
    }

    return FALSE;
  }
}
