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

    // Lấy secret key từ settings.php (Bạn nói đã cấu hình biến này bằng giá trị _id)
    $secret_key = Settings::get('webhook_secret_key');
    if (empty($secret_key)) {
      $this->getLogger('devpanel_marketplace_bar')->error('Thiếu cấu hình webhook_secret_key tại Site B.');
      return new JsonResponse(['error' => 'Internal Server Error'], 500);
    }

    // Tính toán và kiểm tra chữ ký HMAC
    $expected_signature = hash_hmac('sha256', $payload_json, $secret_key);
    if (!hash_equals($expected_signature, $signature_header)) {
      $this->getLogger('devpanel_marketplace_bar')->warning('Sai chữ ký Webhook.');
      throw new AccessDeniedHttpException('Invalid Signature.');
    }

    // Decode JSON
    $data = json_decode($payload_json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || empty($data['applicationId'])) {
      throw new BadRequestHttpException('Invalid JSON payload.');
    }

    try {
      // Gọi Config Factory của Drupal để lưu toàn bộ data vào settings
      $config = \Drupal::configFactory()->getEditable('devpanel_marketplace_bar.settings');
      
      // Lấy cấu hình cũ hiện đang có trên Site B
      $existing_data = $config->get('data') ?: [];
      
      // array_merge sẽ giữ nguyên các biến cũ (template_id, enable_cde...) 
      // và chỉ cập nhật/đè các biến mới được gửi sang (như is_purchase)
      $merged_data = array_merge($existing_data, $data);
      
      $config->set('data', $merged_data);
      $config->save();

      $this->getLogger('devpanel_marketplace_bar')->info('Đã cập nhật cấu hình devpanel_marketplace_bar.settings thành công từ Webhook.');

      return new JsonResponse(['status' => 'success', 'message' => 'Config updated'], 200);

    } catch (\Exception $e) {
      $this->getLogger('devpanel_marketplace_bar')->error('Lỗi khi lưu config: ' . $e->getMessage());
      return new JsonResponse(['error' => 'Could not save configuration'], 500);
    }
  }
}