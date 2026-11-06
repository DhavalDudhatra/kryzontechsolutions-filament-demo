<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'template_key' => 'welcome',
                'template_name' => 'Welcome email',
                'email_subject' => 'Welcome to {{ app.name }}, {{ user.name }}',
                'email_body' => <<<'HTML'
                    <h2>Welcome, {{ user.name }}</h2>
                    <p>Your {{ app.name }} administrator account is ready. Sign in with <strong>{{ user.email }}</strong> to manage products, categories and your team.</p>
                    <p>Two-factor authentication is required for every administrator account. If you have not enrolled yet, the sign-in page guides you through the setup.</p>
                    <p>Change your password the first time you sign in, and keep this email for reference.</p>
                    HTML,
            ],
            [
                'template_key' => 'email_verification',
                'template_name' => 'Email verification',
                'email_subject' => 'Verify your {{ app.name }} email address',
                'email_body' => <<<'HTML'
                    <h2>Verify your email address</h2>
                    <p>Hi {{ user.name }},</p>
                    <p>Please confirm that <strong>{{ user.email }}</strong> belongs to you. This keeps your account and the notifications you receive secure.</p>
                    <p>The verification link is valid for 60 minutes. If it has expired, request a new one from the sign-in page.</p>
                    <p>If you did not create this account, you can safely ignore this email.</p>
                    HTML,
            ],
            [
                'template_key' => 'password_reset',
                'template_name' => 'Password reset',
                'email_subject' => 'Reset your {{ app.name }} password',
                'email_body' => <<<'HTML'
                    <h2>Reset your password</h2>
                    <p>Hi {{ user.name }},</p>
                    <p>We received a request to reset the password for the account registered as <strong>{{ user.email }}</strong>.</p>
                    <p>Use the button below to choose a new password. The link is valid for 60 minutes and can only be used once.</p>
                    <p>If you did not request a new password, no action is needed &mdash; your current password stays active.</p>
                    HTML,
            ],
            [
                'template_key' => 'security_alert',
                'template_name' => 'Security alert',
                'email_subject' => 'Security alert for your {{ app.name }} account',
                'email_body' => <<<'HTML'
                    <h2>Security alert</h2>
                    <p>Hi {{ user.name }},</p>
                    <p>A change was made to your {{ app.name }} account: <strong>{{ event }}</strong>.</p>
                    <p>If this was not you, review your account straight away and contact your administrator.</p>
                    HTML,
            ],
            [
                'template_key' => 'announcement',
                'template_name' => 'Announcement',
                'email_subject' => 'News from {{ app.name }}',
                'email_body' => <<<'HTML'
                    <h2>What&rsquo;s new</h2>
                    <p>Hi {{ user.name }},</p>
                    <p>We have an update to share with you. The latest news from the {{ app.name }} team is waiting for you.</p>
                    <p>Thanks for being part of {{ app.name }}.</p>
                    HTML,
            ],
            [
                'template_key' => 'order_confirmation',
                'template_name' => 'Order confirmation',
                'email_subject' => 'Order {{ order.number }} confirmed',
                'email_body' => <<<'HTML'
                    <h2>Thanks for your order, {{ customer.name }}</h2>
                    <p>We have received order <strong>{{ order.number }}</strong>.</p>
                    <p><strong>Items:</strong> {{ order.items }}</p>
                    <p>Subtotal: ${{ order.subtotal }}<br>Shipping: ${{ order.shipping }}<br><strong>Total: ${{ order.total }}</strong></p>
                    <p>Payment status: {{ order.payment_status }}</p>
                    <p>We will send another update when your order status changes.</p>
                    HTML,
            ],
            [
                'template_key' => 'order_status_update',
                'template_name' => 'Order status update',
                'email_subject' => 'Order {{ order.number }} is now {{ order.status }}',
                'email_body' => <<<'HTML'
                    <h2>Your order status changed</h2>
                    <p>Hi {{ customer.name }},</p>
                    <p>Order <strong>{{ order.number }}</strong> is now <strong>{{ order.status }}</strong>.</p>
                    <p><strong>Items:</strong> {{ order.items }}</p>
                    <p>Subtotal: {{ order.subtotal }}<br>Shipping: {{ order.shipping }}<br><strong>Total: {{ order.total }}</strong></p>
                    <p>Payment status: {{ order.payment_status }}</p>
                    <p>Questions about this order? Reply to this email and our team will help.</p>
                    HTML,
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                ['template_key' => $data['template_key']],
                $data
            );
        }
    }
}
