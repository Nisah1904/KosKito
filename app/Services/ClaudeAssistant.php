<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Property;
use RuntimeException;
use Symfony\Component\Process\Process;

class ClaudeAssistant
{
    public static function stream(string $sessionId, string $system, array $history, string $message, callable $onChunk, int $maxTokens = 4000): string
    {
        $process = new Process(
            [config('services.claude.python'), base_path('ai/claude_bridge.py')],
            base_path(),
            ['EMERGENT_LLM_KEY' => config('services.claude.key')],
            json_encode([
                'model' => config('services.claude.model'),
                'session_id' => $sessionId,
                'system' => $system,
                'history' => $history,
                'message' => $message,
                'max_tokens' => $maxTokens,
            ]),
            120
        );

        $process->start();
        $full = '';
        foreach ($process as $type => $data) {
            if ($type === Process::OUT) {
                $full .= $data;
                $onChunk($data);
            }
        }

        if (!$process->isSuccessful() && $full === '') {
            logger()->error('Claude bridge gagal', ['stderr' => $process->getErrorOutput()]);
            throw new RuntimeException('Layanan AI sedang tidak dapat dihubungi.');
        }

        return $full;
    }

    public static function kostContext(): string
    {
        $lines = Property::approved()->with('campus')->orderBy('is_basic_info')->orderBy('price')->limit(80)->get()
            ->map(fn (Property $p) => sprintf(
                '- [ID %d] %s%s | %s | %s | %s | Kampus terdekat: %s | Lokasi: %s | Fasilitas: %s | Listrik: %s',
                $p->id,
                $p->name,
                $p->is_basic_info ? ' (INFO DASAR DARI WEB, belum diverifikasi)' : '',
                $p->typeLabel(),
                $p->priceLabel() . ($p->hasPrice() ? '/bulan' : ''),
                $p->roomsLabel(),
                $p->campus?->short_name ?? '-',
                $p->address ?: $p->location,
                implode(', ', (array) $p->facilities) ?: '-',
                $p->electricity_included ? 'termasuk sewa' : ($p->electricity_fee > 0 ? 'Rp' . number_format((int) $p->electricity_fee, 0, ',', '.') . '/bulan terpisah' : 'belum diketahui')
            ))->implode("\n");

        $campuses = Campus::where('is_active', true)->orderBy('name')->get()
            ->map(fn (Campus $c) => "- {$c->short_name}: {$c->name} ({$c->city})")->implode("\n");

        return "DAFTAR KOST TERVERIFIKASI DI DATABASE KOSTLY:\n" . ($lines ?: '(belum ada listing)') . "\n\nKAMPUS:\n" . $campuses;
    }

    public static function systemPrompt(): string
    {
        return "Kamu adalah Asisten Kostly, membantu mahasiswa mencari kost di Palembang dan sekitarnya. "
            . "Jawab dalam Bahasa Indonesia yang ramah, singkat, dan jelas. "
            . "ATURAN PENTING: hanya rekomendasikan kost dari daftar di bawah; jangan pernah mengarang kost, harga, alamat, atau fasilitas. "
            . "Jika tidak ada yang cocok, katakan jujur dan sarankan melonggarkan kriteria. "
            . "Saat menyebut kost, tulis tautan dengan format [[kost:ANGKA_ID|Nama Kost]], contoh [[kost:8|Kost Cemara]]. "
            . "Jika kost berlabel INFO DASAR DARI WEB, jelaskan bahwa harga/fasilitas belum diverifikasi dan sarankan menghubungi pengelola lewat halaman detail. "
            . "Bedakan Unsri Palembang (Bukit Besar) dan Unsri Indralaya (Ogan Ilir, ±35 km dari Palembang). "
            . "Jarak yang kamu sebut hanya perkiraan; arahkan pengguna ke halaman detail untuk peta. "
            . "Tolak pertanyaan di luar topik kost, kampus, dan biaya hidup mahasiswa dengan sopan.\n\n"
            . self::kostContext();
    }
}
