@extends('layouts.app')

@section('title', 'Asisten AI — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">ASISTEN AI · CLAUDE</div>
        <h1 class="page-title">Tanya kost dengan bahasa sehari-hari.</h1>
        <p class="page-description">Asisten hanya menjawab dari listing yang sudah disetujui di database Kostly — tidak mengarang kost, harga, atau alamat. Tetap cek detail dan hubungi pemilik sebelum booking.</p>
    </div>
</section>

<section class="section">
    <div class="ai-layout">
        <aside class="ai-history" data-testid="ai-history">
            <a href="{{ route('assistant') }}" class="primary-button ai-new-chat" data-testid="ai-new-chat">+ Percakapan baru</a>
            <div class="section-label">RIWAYAT</div>
            @forelse($conversations as $conv)
                <div class="ai-history-item {{ $active?->id === $conv->id ? 'is-active' : '' }}">
                    <a href="{{ route('assistant', ['c' => $conv->id]) }}" data-testid="ai-conversation-{{ $conv->id }}">{{ $conv->title }}</a>
                    <form method="POST" action="{{ route('assistant.destroy', $conv->id) }}" onsubmit="return confirm('Hapus percakapan ini?')">@csrf @method('DELETE')<button type="submit" aria-label="Hapus percakapan" data-testid="ai-delete-{{ $conv->id }}">×</button></form>
                </div>
            @empty
                <p class="ai-muted">Belum ada percakapan.</p>
            @endforelse
        </aside>

        <div class="ai-chat planner-card" id="aiChat" data-send-url="{{ route('assistant.send') }}" data-conversation-id="{{ $active?->id }}" data-detail-base="{{ url('/kost') }}" data-testid="ai-chat">
            <div class="ai-messages" id="aiMessages" data-testid="ai-messages">
                @if(!$active || $active->messages->isEmpty())
                    <div class="ai-empty" id="aiEmpty" data-testid="ai-empty">
                        <span class="empty-state-mark">✳</span>
                        <p>Contoh pertanyaan:</p>
                        <div class="ai-suggestions">
                            @foreach(['Kost putri dekat Unsri Bukit di bawah 1 juta?', 'Kost campur ada AC dan WiFi dekat UIN Raden Fatah?', 'Beda Unsri Palembang dan Indralaya untuk cari kost?'] as $i => $s)
                                <button type="button" class="ghost-button ai-suggestion" data-testid="ai-suggestion-{{ $i }}">{{ $s }}</button>
                            @endforeach
                        </div>
                    </div>
                @else
                    @foreach($active->messages as $m)
                        <div class="ai-bubble ai-{{ $m->role }}" data-raw="{{ $m->content }}">{{ $m->content }}</div>
                    @endforeach
                @endif
            </div>

            <form class="ai-input" id="aiForm" data-testid="ai-form">
                <textarea id="aiInput" rows="2" maxlength="1000" placeholder="Tulis pertanyaanmu tentang kost…" required data-testid="ai-input"></textarea>
                <button type="submit" class="primary-button" id="aiSend" data-testid="ai-send">Kirim</button>
            </form>
            <small class="ai-muted" data-testid="ai-remaining">Sisa kuota hari ini: <span id="aiRemaining">{{ $remaining }}</span> pertanyaan · Ditenagai Claude</small>
        </div>
    </div>
</section>

@endsection
