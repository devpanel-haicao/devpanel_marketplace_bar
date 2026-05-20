<?php

namespace Drupal\devpanel_marketplace_bar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DevpanelWebhookController extends ControllerBase {

  public function receive(Request $request) {
    $payload_json = $request->getContent();
    $signature_header = $request->headers->get('X-Webhook-Signature');

    if (empty($payload_json) || empty($signature_header)) {
      throw new BadRequestHttpException('Missing payload or signature.');
    }

    // Get secret key from settings.php.
    $secret_key = Settings::get('webhook_secret_key');
    if (empty($secret_key)) {
      $this->getLogger('devpanel_marketplace_bar')->error('Missing webhook_secret_key setting.');
      return new JsonResponse(['error' => 'Internal Server Error'], 500);
    }

    // Verify Signature HMAC.
    $expected_signature = hash_hmac('sha256', $payload_json, $secret_key);
    if (!hash_equals($expected_signature, $signature_header)) {
      $this->getLogger('devpanel_marketplace_bar')->warning('Signature Webhook incorrect.');
      throw new AccessDeniedHttpException('Invalid Signature.');
    }

    // Decode JSON
    $data = json_decode($payload_json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || empty($data['applicationId'])) {
      throw new BadRequestHttpException('Invalid JSON payload.');
    }

    try {
      // Save data to settings.
      $config = \Drupal::configFactory()->getEditable('devpanel_marketplace_bar.settings');
      
      // Get current settings.
      $existing_data = $config->get('data') ?: [];
      
      // Update setting variables.
      $merged_data = array_merge($existing_data, $data);
      
      $config->set('data', $merged_data);
      $config->save();

      $this->getLogger('devpanel_marketplace_bar')->info('Updated devpanel_marketplace_bar.settings from DrupalForge Webhook.');

      return new JsonResponse(['status' => 'success', 'message' => 'Config updated'], 200);

    } catch (\Exception $e) {
      $this->getLogger('devpanel_marketplace_bar')->error('Error when config saving: ' . $e->getMessage());
      return new JsonResponse(['error' => 'Could not save configuration'], 500);
    }
  }
}