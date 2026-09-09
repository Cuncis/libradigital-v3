<?php

namespace Database\Seeders;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A single realistic demo invitation, content adapted from a reference
 * wedding-invitation site (image URLs are hotlinked from that source —
 * fine for a demo, replace with real uploads for anything customer-facing).
 */
class DemoInvitationSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'test@example.com')->first()
            ?? User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

        Invitation::query()->updateOrCreate(
            ['slug' => 'raka-dinda'],
            [
                'user_id' => $owner->id,
                'title' => 'Raka & Dinda',
                'host_name' => 'Raka & Dinda',
                'venue' => 'Masjid Al-Falah, Yogyakarta',
                'event_date' => '2025-12-20 08:00:00',
                'status' => 'published',
                'published_at' => now(),
                'is_custom_build' => false,
                'content' => $this->content(),
            ]
        );
    }

    protected function content(): array
    {
        return [
            'rows' => [
                $this->row([
                    $this->widget('heading', ['content' => 'Wedding Invitation', 'level' => 'p']),
                ]),
                $this->row([
                    $this->widget('hero', [
                        'heading' => 'Raka & Dinda',
                        'subheading' => 'Sabtu, 20 Desember 2025',
                        'description' => 'We invite you to celebrate our wedding',
                        'background_image' => 'https://libradigital.id/wp-content/uploads/2025/08/img-005.jpg',
                        'alignment' => 'center',
                        'height' => '70vh',
                    ]),
                ]),
                $this->row([
                    $this->widget('blockquote', [
                        'quote' => 'Dan di antara tanda-tanda kekuasaan-Nya diciptakan-Nya untukmu pasangan hidup dari jenismu sendiri supaya kamu dapat ketenangan hati dan dijadikannya kasih sayang di antara kamu.',
                        'attribution' => 'Q.S. Ar-Rum: 21',
                    ]),
                ]),
                $this->row([
                    $this->widget('countdown', [
                        'title' => 'Menuju Hari Bahagia Kami',
                        'target_date' => '2025-12-20 08:00:00',
                    ]),
                ]),
                $this->row([
                    $this->widget('person', [
                        'name' => 'Raka Wicaksono',
                        'role' => 'Putra Pertama dari Keluarga Bapak Sutrisno & Ibu Ratna',
                        'photo' => 'https://libradigital.id/wp-content/uploads/2025/08/Picture1-1.png',
                    ]),
                ], [
                    $this->widget('person', [
                        'name' => 'Dinda Kumala Sari',
                        'role' => 'Putri Keempat dari Keluarga Bapak Bambang Hadi & Ibu Siti Aminah',
                        'photo' => 'https://libradigital.id/wp-content/uploads/2025/08/Picture2-1.png',
                    ]),
                ], spans: [6, 6]),
                $this->row([
                    $this->widget('heading', ['content' => 'Our Moment', 'level' => 'h2']),
                ]),
                $this->row([
                    $this->widget('gallery', [
                        'images' => [
                            'https://libradigital.id/wp-content/uploads/2025/08/img-001.jpg',
                            'https://libradigital.id/wp-content/uploads/2025/08/img-002.jpg',
                            'https://libradigital.id/wp-content/uploads/2025/08/img-004.jpg',
                            'https://libradigital.id/wp-content/uploads/2025/08/img-005.jpg',
                            'https://libradigital.id/wp-content/uploads/2025/08/img-006.jpg',
                            'https://libradigital.id/wp-content/uploads/2025/08/img-007.jpg',
                        ],
                    ]),
                ]),
                $this->row([
                    $this->widget('love-story-timeline', [
                        'events' => [
                            ['date' => '08.00 WIB', 'title' => 'Akad Nikah', 'description' => 'Masjid Al-Falah — Jl. Diponegoro No. 12, Yogyakarta'],
                            ['date' => '10.00 WIB', 'title' => 'Resepsi', 'description' => 'Gedung Graha Saba Buana — Jl. Letjen Suprapto No. 45, Yogyakarta'],
                        ],
                    ]),
                ]),
                $this->row([
                    $this->widget('map', [
                        'address' => 'Masjid Al-Falah, Jl. Diponegoro No. 12, Yogyakarta',
                    ]),
                ]),
                $this->row([
                    $this->widget('gift-info', [
                        'title' => 'Hadiah Pernikahan',
                        'intro_text' => "Kehadiran Anda merupakan sebuah do'a serta rasa syukur bagi kami. Namun jika memberi adalah bentuk kasih sayang, kado dapat dikirim secara cashless melalui:",
                        'accounts' => [
                            ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Raka Wicaksono'],
                            ['bank_name' => 'DANA', 'account_number' => '081234567890', 'account_holder' => 'Dinda Ayu'],
                        ],
                    ]),
                ]),
                $this->row([
                    $this->widget('rsvp-form', [
                        'title' => 'Konfirmasi Kehadiran',
                        'submit_label' => 'Kirim Konfirmasi',
                    ]),
                ]),
                $this->row([
                    $this->widget('heading', ['content' => "Terima kasih atas do'a & kehadiran Anda", 'level' => 'h3']),
                ]),
                $this->row([
                    $this->widget('heading', ['content' => 'Raka & Dinda', 'level' => 'h1']),
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $widgetsA
     * @param  array<int, array<string, mixed>>|null  $widgetsB
     * @param  array<int, int>  $spans
     * @return array<string, mixed>
     */
    protected function row(array $widgetsA, ?array $widgetsB = null, array $spans = [12]): array
    {
        $columns = [
            [
                'id' => 'col_'.Str::random(8),
                'span' => ['sm' => 12, 'md' => 12, 'lg' => $spans[0], 'xl' => $spans[0]],
                'settings' => [],
                'widgets' => $widgetsA,
            ],
        ];

        if ($widgetsB !== null) {
            $columns[] = [
                'id' => 'col_'.Str::random(8),
                'span' => ['sm' => 12, 'md' => 12, 'lg' => $spans[1] ?? 12, 'xl' => $spans[1] ?? 12],
                'settings' => [],
                'widgets' => $widgetsB,
            ];
        }

        return [
            'id' => 'row_'.Str::random(8),
            'settings' => [],
            'columns' => $columns,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function widget(string $type, array $data): array
    {
        return [
            'id' => 'widget_'.Str::random(8),
            'type' => $type,
            'data' => $data,
        ];
    }
}
