<?php

namespace Drupal\devpanel_marketplace_bar\Helper;

class ApiHelper {

  public static function fetchAndSaveData() {
    $app_id = getenv('DP_APP_ID');
    
    if (!$app_id) {
      \Drupal::logger('devpanel_marketplace_bar')->warning('DP_APP_ID is empty.');
      return FALSE;
    }

    $git_branch = getenv('DP_GIT_BRANCH');

    switch ($git_branch) {
      case 'main':
        $base_proxy_url = 'https://www.drupalforge.org';
        $dp_base_url = 'https://console.devpanel.com';
        break;
      default:
        $base_proxy_url = 'https://stage.drupalforge.org';
        $dp_base_url = 'https://alpha.devpanel.com';
        break;
    }

    $proxy_url = $base_proxy_url . '/api/internal/alert-app-info?app_id=' . $app_id;
    
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

        // Store raw API data. buyLink and isPaid are computed at render time
        // from env vars so they always reflect the current environment state.
        $safe_data = [
          'appName' => $api_data['appName'] ?? 'My Application',
          'subId' => $submissionId,
          'templateId' => $templateId,
          'email' => $api_data['email'] ?? '',
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
