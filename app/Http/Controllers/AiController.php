<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\Campus;
use App\Models\Property;
use App\Services\ClaudeAssistant;
use App\Services\SmartMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class AiController extends Controller
{
    public function index(Request $request)
    {
        $conversations = AiConversation::where('user_id', $request->user()->id)->latest('updated_at')->limit(30)->get();
        $active = $request->filled('c')
            ? AiConversation::where('user_id', $request->user()->id)->with('messages')->findOrFail((int) $request->c)
            : null;

        return view('assistant', [
            'conversations' => $conversations,
            'active' => $active,
            'remaining' => $this->remaining($request),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $conversation = !empty($data['conversation_id'])
            ? AiConversation::where('user_id', $user->id)->findOrFail($data['conversation_id'])
            : null;

        if ($limited = $this->hitLimit($request)) {
            return $limited;
        }

        $conversation ??= AiConversation::create(['user_id' => $user->id, 'title' => Str::limit($data['message'], 60)]);

        $history = $conversation->messages()->latest('id')->limit(12)->get()->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->values()->all();

        $userMessage = $conversation->messages()->create(['role' => 'user', 'content' => $data['message']]);

        return $this->sse(function ($emit) use ($conversation, $history, $data, $userMessage) {
            $emit(['type' => 'meta', 'conversation_id' => $conversation->id]);
            try {
                $reply = ClaudeAssistant::stream(
                    'kostly-chat-' . $conversation->id,
                    ClaudeAssistant::systemPrompt(),
                    $history,
                    $data['message'],
                    fn ($chunk) => $emit(['type' => 'delta', 'text' => $chunk])
                );
            } catch (Throwable $e) {
                $userMessage->delete();
                if (!$conversation->messages()->exists()) {
                    $conversation->delete();
                }
                RateLimiter::decrement($this->limiterKey(request()), 86400);
                throw $e;
            }
            $conversation->messages()->create(['role' => 'assistant', 'content' => $reply]);
            $conversation->touch();
        });
    }

    public function explain(Request $request, int $id)
    {
        $property = Property::approved()->with('campus')->findOrFail($id);

        if ($limited = $this->hitLimit($request)) {
            return $limited;
        }

        $campus = $request->filled('campus_id') ? Campus::find((int) $request->campus_id) : null;
        $match = SmartMatch::score($property, [
            'campus' => $campus,
            'budget' => $request->input('budget'),
            'max_distance_km' => $request->input('max_distance_km'),
            'facilities' => (array) $request->input('facilities', []),
            'available_only' => $request->boolean('available_only'),
            'extras' => $request->input('extras'),
            'total_budget' => $request->input('total_budget'),
        ]);

        $prompt = "Jelaskan dalam maksimal 4 kalimat (tanpa judul, tanpa daftar) kenapa kost [ID {$property->id}] {$property->name} mendapat skor Smart Match {$match['score']}% "
            . "untuk mahasiswa dengan preferensi: kampus " . ($campus?->short_name ?? 'tidak ditentukan')
            . ', anggaran sewa ' . ($request->filled('budget') ? 'Rp' . number_format((int) $request->input('budget'), 0, ',', '.') : 'tidak ditentukan')
            . ', fasilitas wajib: ' . (implode(', ', (array) $request->input('facilities', [])) ?: '-') . ".\n"
            . "Alasan dari sistem: " . implode(' ', $match['reasons']) . "\nCatatan: " . implode(' ', $match['notes']) . "\n"
            . "Sebutkan satu hal yang perlu dicek langsung ke pemilik. Jangan menambah fakta di luar data.";

        return $this->sse(function ($emit) use ($request, $property, $prompt) {
            ClaudeAssistant::stream(
                'kostly-explain-' . $request->user()->id . '-' . $property->id,
                ClaudeAssistant::systemPrompt(),
                [],
                $prompt,
                fn ($chunk) => $emit(['type' => 'delta', 'text' => $chunk]),
                2000
            );
        });
    }

    public function destroy(Request $request, int $id)
    {
        AiConversation::where('user_id', $request->user()->id)->findOrFail($id)->delete();

        return redirect()->route('assistant')->with('success', 'Percakapan dihapus.');
    }

    private function sse(callable $work)
    {
        return response()->stream(function () use ($work) {
            $emit = function (array $payload) {
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };
            try {
                $work($emit);
                $emit(['type' => 'done']);
            } catch (Throwable $e) {
                report($e);
                $emit(['type' => 'error', 'message' => 'Asisten AI sedang tidak tersedia. Coba lagi sebentar lagi.']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function limiterKey(Request $request): string
    {
        return 'ai-daily:' . $request->user()->id;
    }

    private function remaining(Request $request): int
    {
        return RateLimiter::remaining($this->limiterKey($request), (int) config('services.claude.daily_limit'));
    }

    private function hitLimit(Request $request)
    {
        $key = $this->limiterKey($request);
        if (RateLimiter::tooManyAttempts($key, (int) config('services.claude.daily_limit'))) {
            return response()->json(['message' => 'Batas harian pertanyaan AI sudah tercapai. Coba lagi besok.'], 429);
        }
        RateLimiter::hit($key, 86400);

        return null;
    }
}
