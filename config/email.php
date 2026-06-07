<?php
// config/email.php
// Email Configuration

define('MAIL_FROM_EMAIL', 'noreply@sportshop.com');
define('MAIL_FROM_NAME', 'SportShop PRO');

// SMTP Configuration (если используется SMTP)
define('MAIL_SMTP_HOST', 'smtp.gmail.com');
define('MAIL_SMTP_PORT', 587);
define('MAIL_SMTP_USER', ''); // Заполнить в production
define('MAIL_SMTP_PASSWORD', ''); // Заполнить в production
define('MAIL_SMTP_SECURE', 'tls'); // tls или ssl

// Email Templates Configuration
define('MAIL_TEMPLATE_DIR', __DIR__ . '/../templates/emails/');

// Whether to use SMTP or PHP mail() function
define('USE_SMTP', false); // Установить true если настроен SMTP

class Mailer {
    private $from_email;
    private $from_name;
    private $smtp_config;
    
    public function __construct() {
        $this->from_email = MAIL_FROM_EMAIL;
        $this->from_name = MAIL_FROM_NAME;
        
        $this->smtp_config = [
            'host' => MAIL_SMTP_HOST,
            'port' => MAIL_SMTP_PORT,
            'user' => MAIL_SMTP_USER,
            'password' => MAIL_SMTP_PASSWORD,
            'secure' => MAIL_SMTP_SECURE
        ];
    }
    
    /**
     * Отправить email
     * @param string $to - email получателя
     * @param string $subject - тема письма
     * @param string $html - HTML содержимое
     * @param string $text - текстовое содержимое (опционально)
     * @return bool
     */
    public function send($to, $subject, $html, $text = '') {
        // Заголовки
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "Reply-To: {$this->from_email}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Отправляем через PHP mail() функцию
        // В production нужно использовать SMTP или SwiftMailer/PHPMailer
        $success = @mail($to, $subject, $html, $headers);
        
        if ($success) {
            error_log("Email sent to: $to, Subject: $subject");
        } else {
            error_log("Failed to send email to: $to, Subject: $subject");
        }
        
        return $success;
    }
    
    /**
     * Отправить уведомление при изменении статуса заказа
     * @param string $to - email получателя
     * @param array $order - данные заказа
     * @param string $old_status - старый статус
     * @param string $new_status - новый статус
     * @return bool
     */
    public function sendOrderStatusChangeNotification($to, $order, $old_status, $new_status) {
        $statusTexts = [
            'pending' => 'Ожидает обработки',
            'processing' => 'В обработке',
            'completed' => 'Выполнен',
            'cancelled' => 'Отменен'
        ];
        
        $subject = "Статус вашего заказа #" . $order['id'] . " изменился на " . $statusTexts[$new_status];
        
        $statusColors = [
            'pending' => '#FFC107',
            'processing' => '#17A2B8',
            'completed' => '#28A745',
            'cancelled' => '#DC3545'
        ];
        
        $html = $this->loadTemplate('order_status_change.html', [
            'customer_name' => htmlspecialchars($order['username'] ?? 'Покупатель'),
            'order_id' => $order['id'],
            'order_total' => number_format($order['total_amount'], 2),
            'old_status' => htmlspecialchars($statusTexts[$old_status] ?? $old_status),
            'new_status' => htmlspecialchars($statusTexts[$new_status] ?? $new_status),
            'new_status_color' => $statusColors[$new_status] ?? '#666',
            'order_date' => date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now')),
            'year' => date('Y')
        ]);
        
        return $this->send($to, $subject, $html);
    }
    
    /**
     * Отправить уведомление о новом заказе покупателю
     * @param string $to - email получателя
     * @param array $order - данные заказа
     * @param array $items - товары в заказе
     * @return bool
     */
    public function sendOrderConfirmation($to, $order, $items = []) {
        $subject = "Ваш заказ #" . $order['id'] . " успешно создан";
        
        $itemsHtml = '';
        $total = 0;
        if (!empty($items)) {
            foreach ($items as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $total += $itemTotal;
                $itemsHtml .= sprintf(
                    '<tr><td>%s</td><td style="text-align:center;">%d</td><td style="text-align:right;">€%.2f</td><td style="text-align:right;">€%.2f</td></tr>',
                    htmlspecialchars($item['name']),
                    $item['quantity'],
                    $item['price'],
                    $itemTotal
                );
            }
        }
        
        $html = $this->loadTemplate('order_confirmation.html', [
            'customer_name' => htmlspecialchars($order['username'] ?? 'Покупатель'),
            'order_id' => $order['id'],
            'order_date' => date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now')),
            'items_html' => $itemsHtml,
            'subtotal' => number_format($order['total_amount'], 2),
            'total' => number_format($order['total_amount'], 2),
            'delivery_address' => htmlspecialchars($order['delivery_address'] ?? 'Адрес доставки не указан'),
            'phone' => htmlspecialchars($order['phone'] ?? '-'),
            'year' => date('Y')
        ]);
        
        return $this->send($to, $subject, $html);
    }
    
    /**
     * Отправить уведомление администратору о новом заказе
     * @param string $admin_email - email администратора
     * @param array $order - данные заказа
     * @param array $items - товары в заказе
     * @return bool
     */
    public function sendAdminNewOrderNotification($admin_email, $order, $items = []) {
        $subject = "🔔 Новый заказ #" . $order['id'];
        
        $itemsHtml = '';
        $total = 0;
        if (!empty($items)) {
            foreach ($items as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $total += $itemTotal;
                $itemsHtml .= sprintf(
                    '<tr><td>%s</td><td style="text-align:center;">%d</td><td style="text-align:right;">€%.2f</td></tr>',
                    htmlspecialchars($item['name']),
                    $item['quantity'],
                    $itemTotal
                );
            }
        }
        
        $html = $this->loadTemplate('admin_new_order.html', [
            'order_id' => $order['id'],
            'customer_name' => htmlspecialchars($order['username'] ?? 'Неизвестный покупатель'),
            'customer_email' => htmlspecialchars($order['email'] ?? '-'),
            'customer_phone' => htmlspecialchars($order['phone'] ?? '-'),
            'order_date' => date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now')),
            'items_html' => $itemsHtml,
            'total' => number_format($order['total_amount'], 2),
            'delivery_address' => htmlspecialchars($order['delivery_address'] ?? '-'),
            'year' => date('Y')
        ]);
        
        return $this->send($admin_email, $subject, $html);
    }
    
    /**
     * Загрузить HTML шаблон email
     * @param string $template_name - имя шаблона (например: order_status_change.html)
     * @param array $vars - переменные для подстановки
     * @return string
     */
    private function loadTemplate($template_name, $vars = []) {
        $template_path = MAIL_TEMPLATE_DIR . $template_name;
        
        if (!file_exists($template_path)) {
            // Если шаблон не найден, вернуть стандартный формат
            return $this->getDefaultTemplate($vars);
        }
        
        ob_start();
        extract($vars);
        include $template_path;
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Получить стандартный шаблон письма
     * @param array $vars - переменные
     * @return string
     */
    private function getDefaultTemplate($vars = []) {
        $name = isset($vars['customer_name']) ? $vars['customer_name'] : 'Покупатель';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомление от SportShop</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ff6b6b; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
        .button { display: inline-block; background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ SportShop PRO</h1>
        </div>
        <div class="content">
            <p>Привет, <strong>{$name}</strong>!</p>
            <p>Спасибо за ваше письмо. Мы скоро свяжемся с вами.</p>
            <p>С уважением,<br>Команда SportShop PRO</p>
        </div>
        <div class="footer">
            <p>&copy; {$vars['year']} SportShop PRO. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}

?>
