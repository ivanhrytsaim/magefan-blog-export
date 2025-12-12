<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

class Mageshbl_ShopifyMediaPusher
{
    public function execute(string $url, string $data, string $entity) {

        $decodedData = json_decode($data, true);
        $result = [];

        foreach ($decodedData as $item) {
            if (file_exists($item['featured_img'])) {
                $file_path = $item['featured_img'];

                $fields = [
                    'data'   => $data,
                    'old_id' => $item['old_id'],
                    'entity' => str_replace('media_', '', $entity),
                ];

                $boundary = wp_generate_password(24, false);

                $body = '';

                foreach ($fields as $name => $value) {
                    $body .= "--{$boundary}\r\n";
                    $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
                    $body .= "{$value}\r\n";
                }

                $file_contents = file_get_contents($file_path);
                $file_name = basename($file_path);
                $mime_type = mime_content_type($file_path);

                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file_name}\"\r\n";
                $body .= "Content-Type: {$mime_type}\r\n\r\n";
                $body .= $file_contents . "\r\n";
                $body .= "--{$boundary}--\r\n";

                $response = wp_remote_post($url, [
                    'headers' => [
                        'Content-Type' => "multipart/form-data; boundary={$boundary}",
                    ],
                    'body'    => $body,
                    'timeout' => 30,
                ]);

                if (is_wp_error($response)) {
                    $result[] = [
                        'status' => 'error',
                        'message' => $response->get_error_message()
                    ];
                } else {
                    $response_body = wp_remote_retrieve_body($response);
                    $result[] = [
                        'status' => 'success',
                        'response' => $response_body
                    ];
                }
            }
        }

        return (string)json_encode($result);
    }

}